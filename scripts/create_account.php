<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require __DIR__ . '/../includes/bootstrap.php';

$options = getopt('', ['username:', 'role:', 'password:', 'developer']);
$username = $options['username'] ?? '';
$role = $options['role'] ?? 'admin';

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) || !in_array($role, ['admin', 'security'], true)) {
    fwrite(STDERR, "Usage: php scripts/create_account.php --username=NAME [--role=admin|security] [--password=PW] [--developer]\n");
    exit(1);
}

$password = $options['password'] ?? '';
if ($password === '') {
    fwrite(STDOUT, 'Password (min. 10 characters): ');
    $password = trim((string) fgets(STDIN));
}
if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

db()->prepare('INSERT INTO accounts (username, password_hash, role, is_developer) VALUES (?, ?, ?, ?)')
    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, isset($options['developer']) ? 1 : 0]);

fwrite(STDOUT, "Created {$role} account '{$username}'.\n");
