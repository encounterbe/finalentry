<?php

declare(strict_types=1);

function time_reached(string $configKey): bool
{
    return new DateTimeImmutable() >= new DateTimeImmutable((string) config($configKey));
}

function handle_developer_unlock(array $user): string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['developer_unlock'])) {
        return '';
    }
    require_post_with_csrf();
    if (!is_developer($user)) {
        return 'Only developer accounts can unlock this early.';
    }
    $_SESSION['developer_unlock'] = true;
    return '';
}

function developer_unlocked(): bool
{
    return !empty($_SESSION['developer_unlock']);
}

function developer_unlock_form(string $error): string
{
    $html = '<form method="POST" class="mt-6">' . csrf_field()
        . '<button type="submit" name="developer_unlock" value="1" class="text-blue-600 hover:underline text-sm font-medium">'
        . 'Developer? Unlock early</button></form>';
    if ($error !== '') {
        $html .= '<p class="text-red-600 text-sm font-semibold mt-4">' . e($error) . '</p>';
    }
    return $html;
}

function format_event_time(string $configKey): string
{
    return (new DateTimeImmutable((string) config($configKey)))->format('F j \a\t H:i');
}
