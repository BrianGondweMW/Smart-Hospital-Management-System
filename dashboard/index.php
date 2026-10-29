<?php
declare(strict_types=1);

// Start the staff session.
ini_set('session.use_strict_mode', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/SHMS/',
    'secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Prevent unauthorized access.
if (
    empty($_SESSION['logged_in']) ||
    empty($_SESSION['staff_id'])
) {
    header('Location: ../auth/login.html');
    exit;
}

header('Cache-Control: no-store');

$staffName = $_SESSION['staff_name'] ?? 'Staff Member';
$staffRole = $_SESSION['role'] ?? 'Unknown';

function escape(string $value): string {
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Staff Dashboard | SHMS</title>

    <link rel="stylesheet"
          href="../assets/css/dashboard.css">
</head>

<body>

<header class="topbar">
    <div>
        <h1>SHMS</h1>
        <p>Smart Hospital Management System</p>
    </div>

    <div class="staff-info">
        <strong><?= escape($staffName) ?></strong>
        <span><?= escape($staffRole) ?></span>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h2>Welcome, <?= escape($staffName) ?></h2>
        <p>
            You are signed in as
            <?= escape($staffRole) ?>.
        </p>
    </section>

    <section class="dashboard-grid">

        <div class="dashboard-card">
            <h3>Patient Registration</h3>
            <p>Register new patients electronically.</p>
            <a href="../patients/register.html">
                Open Registration
            </a>
        </div>

        <div class="dashboard-card">
            <h3>Patient Search</h3>
            <p>Find existing patient records.</p>
            <a href="../patients/search.html">
                Search Patients
            </a>
        </div>

        <div class="dashboard-card">
            <h3>Family Linking</h3>
            <p>Connect patients to family members.</p>
            <a href="../patients/family.html">
                Manage Family Links
            </a>
        </div>

        <div class="dashboard-card">
            <h3>Family Verification</h3>
            <p>Review pending family relationships.</p>
            <a href="../patients/verify-family.html">
                Review Relationships
            </a>
        </div>

        <div class="dashboard-card">
            <h3>Family-Based Search</h3>
            <p>Locate patients through verified relatives.</p>
            <a href="../patients/family-search.html">
                Find Through Family
            </a>
        </div>

    </section>

    <p class="security-note">
        Demonstration dashboard — patient access permissions
        are still being developed.
    </p>

</main>

</body>
</html>
