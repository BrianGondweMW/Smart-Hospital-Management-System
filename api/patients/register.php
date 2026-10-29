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
    header('Allow: POST');
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../includes/permissions.php';

requirePermission(['Administrator', 'Receptionist']);

$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$dob = $_POST['date_of_birth'] ?? '';
$gender = $_POST['gender'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);

if (
    $first === '' ||
    $last === '' ||
    strlen($first) > 100 ||
    strlen($last) > 100 ||
    strlen($phone) > 30 ||
    !$date ||
    $date->format('Y-m-d') !== $dob ||
    $date > new DateTimeImmutable('today') ||
    !in_array($gender, ['Male', 'Female', 'Other'], true)
) {
    respond(422, [
        'success' => false,
        'message' => 'Please provide valid patient details.'
    ]);
}

try {
    $sql = "INSERT INTO patients
        (patient_code, first_name, last_name,
         date_of_birth, gender, phone, address)
        VALUES
        (:code, :first, :last, :dob,
         :gender, :phone, :address)";

    $stmt = $pdo->prepare($sql);

    // Retry if an extremely unlikely ID collision occurs.
    for ($attempt = 0; $attempt < 3; $attempt++) {
      $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$randomCode = '';

for ($i = 0; $i < 6; $i++) {
    $randomCode .= $characters[
        random_int(0, strlen($characters) - 1)
    ];
}

$code = 'SHMS-' . $randomCode;

        try {
            $stmt->execute([
                ':code' => $code,
                ':first' => $first,
                ':last' => $last,
                ':dob' => $dob,
                ':gender' => $gender,
                ':phone' => $phone,
                ':address' => $address
            ]);

            respond(201, [
                'success' => true,
                'message' => 'Patient registered successfully.',
                'patient_code' => $code
            ]);

        } catch (PDOException $e) {
            // MySQL duplicate-key error.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
    }

    respond(409, [
        'success' => false,
        'message' => 'Unable to generate a unique ID. Retry.'
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Server error. Please try again.'
    ]);
}