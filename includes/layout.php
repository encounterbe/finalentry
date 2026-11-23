<?php

declare(strict_types=1);

function page_header(string $title, string $bodyClass = 'bg-gray-50 text-gray-800 font-sans', array $styles = []): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> – <?= e(config('app.name')) ?></title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <?php foreach ($styles as $style): ?>
  <link rel="stylesheet" href="<?= e(url($style)) ?>">
  <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <?php
}

function page_footer(array $scripts = []): void
{
    ?>
  <footer class="text-center text-sm text-gray-500 mt-12 py-4 border-t">
    <?= e(config('app.name')) ?> &middot; ticketing &amp; entry control
  </footer>
  <?php foreach ($scripts as $script): ?>
  <script src="<?= e(str_starts_with($script, 'https://') ? $script : url($script)) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
    <?php
}

function logout_button(): string
{
    return '<form method="POST" action="' . e(url('auth/logout.php')) . '">' . csrf_field()
        . '<button type="submit" class="text-red-600 font-semibold hover:underline text-sm">Log out</button></form>';
}

function render_flashes(): void
{
    foreach (take_flashes() as $flash) {
        $class = $flash['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
        echo '<div class="mb-4 p-3 rounded ' . $class . '">' . e($flash['message']) . '</div>';
    }
}
