<?php
declare(strict_types=1);

function requireStaffLogin(): void
{
    if (session_status() === PHP_SESSION_NONE) {

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
    }

    if (
        empty($_SESSION['logged_in']) ||
        empty($_SESSION['staff_id'])
    ) {
        header('Content-Type: application/json');
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Please log in to continue.'
        ]);

        exit;
    }
}
