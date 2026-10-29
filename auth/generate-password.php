<?php

$password = 'Admin@2026';

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

echo "Generated Password Hash: ";
echo htmlspecialchars($hashedPassword);
