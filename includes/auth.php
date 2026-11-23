<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (isset($_SESSION['user_id'])) {
            $stmt = db()->prepare(
                'SELECT id, username, role, guest_list_only, is_developer, introduction_done FROM accounts WHERE id = ?'
            );
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function require_role(array $allowedRoles): array
{
    $user = current_user();
    if ($user === null || !in_array($user['role'], $allowedRoles, true)) {
        if (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            http_response_code(401);
            exit;
        }
        redirect('login/');
    }
    return $user;
}

function is_full_admin(array $user): bool
{
    return $user['role'] === 'admin' && !(bool) $user['guest_list_only'];
}

function is_developer(array $user): bool
{
    return (bool) $user['is_developer'];
}

function dashboard_for(string $role): string
{
    return $role === 'admin' ? 'admin/' : 'security/';
}
