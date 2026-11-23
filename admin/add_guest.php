<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/guests.php';

$user = require_role(['admin']);
if (!is_full_admin($user)) {
    http_response_code(403);
    exit('Forbidden.');
}
require_post_with_csrf();

header('Cache-Control: no-store');

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if (!isset($_FILES['guest_json'])) {
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $ageGroup = normalize_age_group($_POST['age_group'] ?? '');

    if (!valid_name($firstName) || !valid_name($lastName) || $ageGroup === null) {
        json_response(['success' => false, 'message' => 'Please enter a valid first name, last name and age.'], 422);
    }

    try {
        $codes = create_guest($firstName, $lastName, $ageGroup);
        $png = render_ticket_png(['first_name' => $firstName, 'last_name' => $lastName] + $codes);
    } catch (Throwable $ex) {
        error_log('add_guest: ' . $ex->getMessage());
        json_response(['success' => false, 'message' => 'The ticket could not be created.'], 500);
    }

    json_response([
        'success' => true,
        'ticket_image' => 'data:image/png;base64,' . base64_encode($png),
        'fallback_code' => $codes['fallback_code'],
    ]);
}

$upload = $_FILES['guest_json'];
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $upload['size'] > 1024 * 1024) {
    flash('error', 'Upload failed. The file must be a JSON file below 1 MB.');
    redirect('admin/');
}

$guests = json_decode((string) file_get_contents($upload['tmp_name']), true);
if (!is_array($guests) || !array_is_list($guests) || count($guests) === 0 || count($guests) > 2000) {
    flash('error', 'Invalid JSON. Expected a list of 1 to 2000 guests.');
    redirect('admin/');
}

$zipPath = tempnam(sys_get_temp_dir(), 'tickets_');
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
    flash('error', 'Could not create the ZIP archive.');
    redirect('admin/');
}

$created = 0;
$skipped = [];

foreach ($guests as $index => $guest) {
    $guest = is_array($guest) ? $guest : [];
    $firstName = trim((string) ($guest['first_name'] ?? ''));
    $lastName = trim((string) ($guest['last_name'] ?? ''));
    $ageGroup = normalize_age_group($guest['age'] ?? $guest['age_group'] ?? '');
    $guardian = $ageGroup === 'minor' ? mb_substr(trim((string) ($guest['guardian_name'] ?? '')), 0, 201) : '';

    if (!valid_name($firstName) || !valid_name($lastName) || $ageGroup === null) {
        $skipped[] = 'Entry #' . ($index + 1) . ': invalid name or age';
        continue;
    }

    try {
        $codes = create_guest($firstName, $lastName, $ageGroup, $guardian);
        $png = render_ticket_png(['first_name' => $firstName, 'last_name' => $lastName] + $codes);
    } catch (Throwable $ex) {
        error_log('add_guest bulk: ' . $ex->getMessage());
        $skipped[] = 'Entry #' . ($index + 1) . ': could not be saved';
        continue;
    }

    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '', $firstName . '_' . $lastName) ?: 'guest';
    $zip->addFromString($safeName . '_' . $codes['fallback_code'] . '.png', $png);
    $created++;
}

if ($skipped !== []) {
    $zip->addFromString('_skipped_entries.txt', implode(PHP_EOL, $skipped));
}
$zip->close();

if ($created === 0) {
    unlink($zipPath);
    flash('error', 'No valid guests found in the file.');
    redirect('admin/');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="tickets_' . date('Ymd_His') . '.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
