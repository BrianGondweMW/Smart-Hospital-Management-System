<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

try {
    require_once __DIR__ . '/../../config/database.php';

   require_once __DIR__ . '/../../includes/permissions.php';

requirePermission(['Administrator']);

    $sql = "
        SELECT
            r.relationship_id,
            r.relationship_type,
            r.created_at,

            p1.patient_code AS patient_code,
            p1.first_name AS patient_first_name,
            p1.last_name AS patient_last_name,

            p2.patient_code AS related_code,
            p2.first_name AS related_first_name,
            p2.last_name AS related_last_name

        FROM patient_relationships r

        INNER JOIN patients p1
            ON r.patient_id = p1.patient_id

        INNER JOIN patients p2
            ON r.related_patient_id = p2.patient_id

        WHERE r.verified = 0

        ORDER BY r.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $relationships = $stmt->fetchAll();

    respond(200, [
        'success' => true,
        'relationships' => $relationships
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to retrieve pending relationships.'
    ]);
}
