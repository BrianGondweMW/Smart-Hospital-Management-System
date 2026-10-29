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

// Start a secure PHP session.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/SHMS/',
    'secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

try {
    require_once __DIR__ . '/../../config/database.php';

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

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (
        !is_string($email) ||
        !is_string($password) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {
        respond(422, [
            'success' => false,
            'message' => 'Enter a valid email and password.'
        ]);
    }

    // Retrieve staff details and role.
    $sql = "
        SELECT
            s.staff_id,
            s.hospital_id,
            s.full_name,
            s.email,
            s.password_hash,
            s.is_active,
            r.role_name
        FROM staff s
        INNER JOIN roles r
            ON s.role_id = r.role_id
        WHERE s.email = :email
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);

    $staff = $stmt->fetch();

    if (
        !$staff ||
        !$staff['is_active'] ||
        !password_verify(
            $password,
            $staff['password_hash']
        )
    ) {
        respond(401, [
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);
    }

    // Prevent session fixation.
    session_regenerate_id(true);

    $_SESSION['staff_id'] = (int) $staff['staff_id'];
    $_SESSION['hospital_id'] = (int) $staff['hospital_id'];
    $_SESSION['staff_name'] = $staff['full_name'];
    $_SESSION['role'] = $staff['role_name'];
    $_SESSION['logged_in'] = true;

    respond(200, [
        'success' => true,
        'message' => 'Login successful.',
        'staff' => [
            'name' => $staff['full_name'],
            'role' => $staff['role_name']
        ]
    ]);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Unable to process login.'
    ]);
}
