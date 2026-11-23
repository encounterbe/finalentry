<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

require_role(['admin']);
require_post_with_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'Invalid guest.');
    redirect('admin/guests/');
}

db()->prepare('DELETE FROM guests WHERE id = ?')->execute([$id]);

flash('success', 'Guest deleted.');
redirect('admin/guests/');
