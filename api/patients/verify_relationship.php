<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/permissions.php';

    // Only administrators can verify relationships.
    requirePermission(['Administrator']);

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid request.'
        ]);
    }

    $relationshipId = filter_var(
        $input['relationship_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $decision = $input['decision'] ?? '';
    $notes = trim($input['notes'] ?? '');

    if (
        !$relationshipId ||
        !in_array($decision, ['Verified', 'Rejected'], true) ||
        $notes === '' ||
        strlen($notes) > 2000
    ) {
        respond(422, [
            'success' => false,
            'message' => 'Valid relationship, decision and notes are required.'
        ]);
    }

    $pdo->beginTransaction();

    // Lock the relationship to prevent conflicting decisions.
    $check = $pdo->prepare(
        "SELECT verification_status
         FROM patient_relationships
         WHERE relationship_id = :id
         FOR UPDATE"
    );

    $check->execute([':id' => $relationshipId]);
    $relationship = $check->fetch();

    if (!$relationship) {
        $pdo->rollBack();
        respond(404, [
            'success' => false,
            'message' => 'Relationship not found.'
        ]);
    }

    if ($relationship['verification_status'] !== 'Pending') {
        $pdo->rollBack();
        respond(409, [
            'success' => false,
            'message' => 'This relationship has already been reviewed.'
        ]);
    }

    $sql = "
        UPDATE patient_relationships
        SET
            verification_status = :status,
            verified = :verified,
            verified_by = :staff_id,
            verified_at = NOW(),
            verification_notes = :notes
        WHERE relationship_id = :id
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':status' => $decision,
        ':verified' => $decision === 'Verified' ? 1 : 0,
        ':staff_id' => $_SESSION['staff_id'],
        ':notes' => $notes,
        ':id' => $relationshipId
    ]);

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Relationship marked as ' . $decision . '.'
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to process verification.'
    ]);
}
