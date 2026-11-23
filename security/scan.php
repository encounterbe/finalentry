<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/guests.php';

$user = require_role(['admin', 'security']);
require_post_with_csrf();

function finish(array $result): never
{
    $_SESSION['scan_result'] = $result;
    redirect('security/');
}

$action = (string) ($_POST['action'] ?? '');

if ($action === 'lookup') {
    $qrCode = trim((string) ($_POST['qr_code'] ?? ''));
    $fallbackCode = trim((string) ($_POST['fallback_code'] ?? ''));

    if ($qrCode !== '') {
        if (!preg_match('/^[a-f0-9]{20,64}$/', $qrCode)) {
            finish(['type' => 'error', 'message' => 'Unknown QR code.']);
        }
        $stmt = db()->prepare('SELECT * FROM guests WHERE qr_code = ?');
        $stmt->execute([$qrCode]);
    } elseif (preg_match('/^\d{4}$/', $fallbackCode)) {
        if (too_many_attempts('fallback-lookup', (string) $user['id'], 30, 60)) {
            finish(['type' => 'error', 'message' => 'Too many manual lookups. Please wait a minute.']);
        }
        record_attempt('fallback-lookup', (string) $user['id']);
        $stmt = db()->prepare('SELECT * FROM guests WHERE fallback_code = ?');
        $stmt->execute([$fallbackCode]);
    } else {
        finish(['type' => 'error', 'message' => 'Please scan a ticket or enter a 4-digit fallback code.']);
    }

    $guest = $stmt->fetch();
    if (!$guest) {
        finish(['type' => 'error', 'message' => 'No guest found for this ticket.']);
    }
    if ($guest['blocked']) {
        finish(['type' => 'error', 'message' => 'Access denied: this guest is blocked.']);
    }
    if ($guest['checked_in']) {
        finish(['type' => 'warning', 'message' => 'This guest has already been admitted.']);
    }

    finish([
        'type' => 'found',
        'guest' => [
            'id' => (int) $guest['id'],
            'name' => $guest['first_name'] . ' ' . $guest['last_name'],
            'age' => age_label($guest['age_group']),
        ],
        'rules' => entry_rules($guest),
    ]);
}

$guestId = filter_input(INPUT_POST, 'guest_id', FILTER_VALIDATE_INT);
if (!$guestId || !in_array($action, ['admit', 'deny'], true)) {
    finish(['type' => 'error', 'message' => 'Invalid action.']);
}

if ($action === 'admit') {
    $stmt = db()->prepare('UPDATE guests SET checked_in = 1, checked_in_at = NOW() WHERE id = ? AND checked_in = 0 AND blocked = 0');
    $stmt->execute([$guestId]);
    finish($stmt->rowCount() === 1
        ? ['type' => 'success', 'message' => 'Entry granted.']
        : ['type' => 'warning', 'message' => 'Guest was already admitted or blocked in the meantime.']);
}

db()->prepare('UPDATE guests SET blocked = 1 WHERE id = ? AND checked_in = 0')->execute([$guestId]);
finish(['type' => 'error', 'message' => 'Entry denied. The guest has been blocked.']);
