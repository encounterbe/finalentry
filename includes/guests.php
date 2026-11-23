<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const AGE_GROUPS = ['minor' => 'Under 18', 'adult' => '18+'];

function age_label(string $ageGroup): string
{
    return AGE_GROUPS[$ageGroup] ?? 'Unknown';
}

function normalize_age_group(mixed $value): ?string
{
    $value = strtolower(trim((string) $value));
    return match ($value) {
        'minor', 'under 18', 'under18', 'u18', 'u. 18', 'u.18', 'unter 18' => 'minor',
        'adult', '18+', 'over 18', 'o18', 'ü. 18', 'ü.18', 'über 18' => 'adult',
        default => null,
    };
}

function generate_unique_codes(): array
{
    $pdo = db();
    $check = $pdo->prepare('SELECT 1 FROM guests WHERE fallback_code = ? LIMIT 1');
    for ($i = 0; $i < 50; $i++) {
        $fallback = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $check->execute([$fallback]);
        if (!$check->fetchColumn()) {
            return [bin2hex(random_bytes(16)), $fallback];
        }
    }
    throw new RuntimeException('No free fallback code available.');
}

function create_guest(string $firstName, string $lastName, string $ageGroup, string $guardianName = ''): array
{
    [$qrCode, $fallbackCode] = generate_unique_codes();
    db()->prepare(
        'INSERT INTO guests (first_name, last_name, age_group, guardian_name, qr_code, fallback_code)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$firstName, $lastName, $ageGroup, $guardianName !== '' ? $guardianName : null, $qrCode, $fallbackCode]);

    return ['qr_code' => $qrCode, 'fallback_code' => $fallbackCode];
}

function valid_name(string $name): bool
{
    return $name !== '' && mb_strlen($name) <= 100;
}

function entry_rules(array $guest): array
{
    if ($guest['age_group'] === 'adult') {
        return ['band' => 'Gold wristband', 'alcohol' => 'Guest may drink alcohol.', 'curfew' => false];
    }

    $rules = (bool) $guest['alcohol_allowed']
        ? ['band' => 'Silver wristband', 'alcohol' => 'Only drinks below 15% ABV.']
        : ['band' => 'Red wristband', 'alcohol' => 'No alcohol for the whole event.'];
    $rules['curfew'] = !(bool) $guest['has_parental_consent'];

    return $rules;
}

function qr_png(string $data, int $scale, int $margin = 2): string
{
    $previous = error_reporting(error_reporting() & ~E_DEPRECATED);
    try {
        require_once APP_ROOT . '/lib/phpqrcode/qrlib.php';
        $rows = QRencode::factory(QR_ECLEVEL_H, 1, 0)->encode($data);
    } finally {
        error_reporting($previous);
    }

    $modules = count($rows);
    $size = ($modules + 2 * $margin) * $scale;
    $img = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);

    foreach ($rows as $y => $row) {
        for ($x = 0; $x < $modules; $x++) {
            if ((ord($row[$x]) & 1) === 1) {
                $px = ($x + $margin) * $scale;
                $py = ($y + $margin) * $scale;
                imagefilledrectangle($img, $px, $py, $px + $scale - 1, $py + $scale - 1, $black);
            }
        }
    }

    ob_start();
    imagepng($img);
    imagedestroy($img);
    return (string) ob_get_clean();
}

function render_ticket_png(array $guest): string
{
    $qr = imagecreatefromstring(qr_png($guest['qr_code'], 10));
    $width = 1000;
    $height = 420;
    $img = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($img, 255, 255, 255);
    $dark = imagecolorallocate($img, 30, 41, 59);
    $muted = imagecolorallocate($img, 100, 116, 139);
    imagefill($img, 0, 0, $white);
    imagefilledrectangle($img, 0, 0, $width, 12, $dark);

    $qrSize = 360;
    imagecopyresized($img, $qr, 30, 40, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
    imagedestroy($qr);

    $x = 430;
    draw_scaled_text($img, config('app.name'), $x, 60, 3, $dark);
    draw_scaled_text($img, config('event.title'), $x, 120, 2, $muted);
    draw_scaled_text($img, $guest['first_name'] . ' ' . $guest['last_name'], $x, 180, 3, $dark);
    draw_scaled_text($img, 'Fallback code', $x, 270, 2, $muted);
    draw_scaled_text($img, $guest['fallback_code'], $x, 305, 5, $dark);

    ob_start();
    imagepng($img);
    imagedestroy($img);
    return (string) ob_get_clean();
}

function draw_scaled_text(GdImage $target, string $text, int $x, int $y, int $scale, int $color): void
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
    $font = 5;
    $w = max(1, imagefontwidth($font) * strlen($text));
    $h = imagefontheight($font);
    $tmp = imagecreatetruecolor($w, $h);
    imagesavealpha($tmp, true);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefill($tmp, 0, 0, $transparent);
    $rgb = imagecolorsforindex($target, $color);
    imagestring($tmp, $font, 0, 0, $text, imagecolorallocate($tmp, $rgb['red'], $rgb['green'], $rgb['blue']));
    imagecopyresampled($target, $tmp, $x, $y, 0, 0, $w * $scale, $h * $scale, $w, $h);
    imagedestroy($tmp);
}

function guests_to_send_home(): array
{
    return db()->query(
        "SELECT g.first_name, g.last_name, g.guardian_name
         FROM guests g
         WHERE g.age_group = 'minor'
           AND g.checked_in = 1
           AND g.has_parental_consent = 0
           AND NOT EXISTS (
               SELECT 1 FROM guests guardian
               WHERE guardian.checked_in = 1
                 AND guardian.age_group = 'adult'
                 AND g.guardian_name IS NOT NULL
                 AND CONCAT(guardian.first_name, ' ', guardian.last_name) = g.guardian_name
           )
         ORDER BY g.last_name, g.first_name"
    )->fetchAll();
}

function guest_counts(): array
{
    $row = db()->query('SELECT COUNT(*) AS total, COALESCE(SUM(checked_in), 0) AS present FROM guests')->fetch();
    return ['total' => (int) $row['total'], 'present' => (int) $row['present']];
}
