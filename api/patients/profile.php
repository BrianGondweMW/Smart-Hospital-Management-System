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

    $id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$id) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid patient ID.'
        ]);
    }

    $sql = "
        SELECT patient_id, patient_code,
               first_name, last_name,
               date_of_birth, gender,
               phone, address, created_at
        FROM patients
        WHERE patient_id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    $patient = $stmt->fetch();

    if (!$patient) {
        respond(404, [
            'success' => false,
            'message' => 'Patient not found.'
        ]);
    }

    respond(200, [
        'success' => true,
        'patient' => $patient
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to load patient profile.'
    ]);
}
