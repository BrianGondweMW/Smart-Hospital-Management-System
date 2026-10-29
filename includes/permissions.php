<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

function requirePermission(array $allowedRoles): void
{
    requireStaffLogin();

    $staffRole = $_SESSION['role'] ?? '';

    if (!in_array($staffRole, $allowedRoles, true)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Access denied. You do not have permission.'
        ]);

        exit;
    }
}
