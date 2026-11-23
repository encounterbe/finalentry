<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_post_with_csrf();

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$throttleKey = client_ip() . '|' . $username;

if (too_many_attempts('login', $throttleKey, 5, 15 * 60) || too_many_attempts('login-ip', client_ip(), 30, 15 * 60)) {
    flash('error', 'Too many failed attempts. Please wait 15 minutes.');
    redirect('login/');
}

$stmt = db()->prepare('SELECT id, password_hash, role FROM accounts WHERE username = ?');
$stmt->execute([$username]);
$account = $stmt->fetch();

$dummyHash = '$2y$12$/LWjAeEKxW96ur4eez2oJOgOmvVNvQwiHM5k0NttvedXu3yxIg7PG';
$valid = password_verify($password, $account['password_hash'] ?? $dummyHash) && $account !== false;

if (!$valid) {
    record_attempt('login', $throttleKey);
    record_attempt('login-ip', client_ip());
    flash('error', 'Invalid username or password.');
    redirect('login/');
}

clear_attempts('login', $throttleKey);

if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
    db()->prepare('UPDATE accounts SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $account['id']]);
}

session_regenerate_id(true);
$_SESSION = ['user_id' => (int) $account['id'], 'last_activity' => time()];

redirect(dashboard_for($account['role']));
