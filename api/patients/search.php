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

    $search = trim($_GET['q'] ?? '');

    if (mb_strlen($search) < 2 ||
        mb_strlen($search) > 100) {
        respond(422, [
            'success' => false,
            'message' => 'Enter 2 to 100 characters.'
        ]);
    }

    $sql = "
        SELECT patient_id, patient_code,
               first_name, last_name,
               date_of_birth, gender
        FROM patients
        WHERE patient_code LIKE :code
           OR first_name LIKE :first
           OR last_name LIKE :last
        ORDER BY last_name, first_name
        LIMIT 30
    ";

    $stmt = $pdo->prepare($sql);
    $pattern = '%' . $search . '%';

    $stmt->execute([
        ':code' => $pattern,
        ':first' => $pattern,
        ':last' => $pattern
    ]);

    respond(200, [
        'success' => true,
        'patients' => $stmt->fetchAll()
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to search patients.'
    ]);
}
