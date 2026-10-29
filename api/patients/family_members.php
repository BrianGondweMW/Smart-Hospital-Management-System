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

requirePermission([
    'Administrator',
    'Receptionist',
    'Doctor',
    'Nurse'
]);

    $patientId = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$patientId) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid patient ID.'
        ]);
    }

    $sql = "
        SELECT
            r.relationship_id,
            CASE
    WHEN r.patient_id = :direction_id
        THEN r.relationship_type
    WHEN r.relationship_type IN ('Mother', 'Father')
        THEN 'Child'
    WHEN r.relationship_type = 'Child'
        THEN 'Parent'
    ELSE r.relationship_type
END AS relationship_type,
r.verified,
            p.patient_id,
            p.patient_code,
            p.first_name,
            p.last_name
        FROM patient_relationships r
        JOIN patients p
          ON p.patient_id =
            CASE
                WHEN r.patient_id = :patient1
                THEN r.related_patient_id
                ELSE r.patient_id
            END
        WHERE r.patient_id = :patient2
           OR r.related_patient_id = :patient3
        ORDER BY p.first_name, p.last_name
    ";

    $stmt = $pdo->prepare($sql);

   $stmt->execute([
    ':direction_id' => $patientId,
    ':patient1' => $patientId,
    ':patient2' => $patientId,
    ':patient3' => $patientId
]);

    $members = $stmt->fetchAll();

    // Reverse relationship names when viewing
    // the family link from the other direction.
    foreach ($members as &$member) {
        $member['verified'] =
            (bool) $member['verified'];
    }
    unset($member);

    respond(200, [
        'success' => true,
        'family_members' => $members
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to load family members.'
    ]);
}
