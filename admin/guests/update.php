<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/guests.php';

require_role(['admin']);
require_post_with_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$ageGroup = normalize_age_group($_POST['age_group'] ?? '');

if (!$id || !valid_name($firstName) || !valid_name($lastName) || $ageGroup === null) {
    flash('error', 'Invalid input.');
    redirect('admin/guests/');
}

db()->prepare(
    'UPDATE guests SET first_name = ?, last_name = ?, age_group = ?, has_parental_consent = ?, alcohol_allowed = ? WHERE id = ?'
)->execute([
    $firstName,
    $lastName,
    $ageGroup,
    isset($_POST['has_parental_consent']) ? 1 : 0,
    isset($_POST['alcohol_allowed']) ? 1 : 0,
    $id,
]);

flash('success', 'Guest updated.');
redirect('admin/guests/');
