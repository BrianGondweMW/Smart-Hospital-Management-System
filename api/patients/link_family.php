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

requirePermission([
    'Administrator',
    'Receptionist'
]);

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid request data.'
        ]);
    }

    $patientCode = strtoupper(trim(
        $input['patient_id'] ?? ''
    ));

    $relatedCode = strtoupper(trim(
        $input['related_patient_id'] ?? ''
    ));

    $relationship = $input['relationship_type'] ?? '';

    $allowed = [
        'Mother', 'Father', 'Child', 'Guardian'
    ];

    if (
        $patientCode === '' ||
        $relatedCode === '' ||
        !in_array($relationship, $allowed, true)
    ) {
        respond(422, [
            'success' => false,
            'message' => 'Please enter valid relationship details.'
        ]);
    }

    if ($patientCode === $relatedCode) {
        respond(422, [
            'success' => false,
            'message' => 'A patient cannot be linked to themselves.'
        ]);
    }

    // Find the two patients by their public IDs.
    $stmt = $pdo->prepare(
        "SELECT patient_id FROM patients
         WHERE patient_code = :code"
    );

    $stmt->execute([':code' => $patientCode]);
    $patientId = $stmt->fetchColumn();

    $stmt->execute([':code' => $relatedCode]);
    $relatedId = $stmt->fetchColumn();

    if (!$patientId || !$relatedId) {
        respond(404, [
            'success' => false,
            'message' => 'One or both patient IDs were not found.'
        ]);
    }

    // Check for an existing relationship in either direction.
    $check = $pdo->prepare(
        "SELECT relationship_id
         FROM patient_relationships
         WHERE
            (patient_id = :p1 AND related_patient_id = :r1)
            OR
            (patient_id = :r2 AND related_patient_id = :p2)
         LIMIT 1"
    );

    $check->execute([
        ':p1' => $patientId,
        ':r1' => $relatedId,
        ':r2' => $relatedId,
        ':p2' => $patientId
    ]);

    if ($check->fetch()) {
        respond(409, [
            'success' => false,
            'message' => 'These patients already have a family link.'
        ]);
    }

    // New relationships remain unverified.
    $insert = $pdo->prepare(
        "INSERT INTO patient_relationships
            (patient_id, related_patient_id,
             relationship_type, verified)
         VALUES
            (:patient, :related, :relationship, 0)"
    );

    $insert->execute([
        ':patient' => $patientId,
        ':related' => $relatedId,
        ':relationship' => $relationship
    ]);

    respond(201, [
        'success' => true,
        'message' => 'Family link created. Verification pending.'
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to create the family relationship.'
    ]);
}
