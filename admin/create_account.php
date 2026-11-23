<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role(['admin']);
require_post_with_csrf();

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = (string) ($_POST['role'] ?? '');
$guestListOnly = ($_POST['guest_list_only'] ?? '') === '1' ? 1 : 0;

if (!in_array($role, ['admin', 'security'], true)) {
    flash('error', 'Invalid role.');
    redirect('admin/');
}

if ($role === 'admin' && !is_full_admin($user)) {
    flash('error', 'You are not allowed to create admin accounts.');
    redirect('admin/');
}

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    flash('error', 'Username must be 3–50 characters: letters, digits and underscores only.');
    redirect('admin/');
}

if (strlen($password) < 10 || strlen($password) > 200) {
    flash('error', 'Password must be at least 10 characters long.');
    redirect('admin/');
}

try {
    db()->prepare('INSERT INTO accounts (username, password_hash, role, guest_list_only) VALUES (?, ?, ?, ?)')
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $role === 'admin' ? $guestListOnly : 0]);
    flash('success', 'Account created.');
} catch (PDOException $ex) {
    if (($ex->errorInfo[1] ?? null) === 1062) {
        flash('error', 'This username is already taken.');
    } else {
        error_log('create_account: ' . $ex->getMessage());
        flash('error', 'Internal server error.');
    }
}

redirect('admin/');
