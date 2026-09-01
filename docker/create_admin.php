<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only be run from the CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

$options = getopt('', ['name:', 'email:', 'password:', 'role::']);
$name = trim($options['name'] ?? '');
$email = trim($options['email'] ?? '');
$password = $options['password'] ?? getenv('ADMIN_PASSWORD') ?: '';
$role = trim($options['role'] ?? 'superadmin');

if ($password === '') {
    fwrite(STDOUT, "Password: ");
    $password = trim((string)fgets(STDIN));
}

if (
    $name === ''
    || mb_strlen($name) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !in_array($role, ['agent', 'admin', 'superadmin'], true)
) {
    fwrite(STDERR, "A valid name and email are required.\n");
    exit(1);
}

if (mb_strlen($password) < 12) {
    fwrite(STDERR, "The password must contain at least 12 characters.\n");
    exit(1);
}

try {
    $stmt = $pdo->prepare('INSERT INTO users_admin (name, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    fwrite(STDOUT, "Administrator created with role: {$role}.\n");
} catch (Throwable $e) {
    error_log('Unable to create administrator: ' . $e->getMessage());
    fwrite(STDERR, "Unable to create administrator. Verify that the email is not already registered.\n");
    exit(1);
}
