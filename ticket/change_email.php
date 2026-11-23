<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$guestId = $_SESSION['ticket_guest_id'] ?? null;
if (!$guestId) {
    flash('error', 'Please verify your ticket first.');
    redirect('ticket/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Please enter a valid email address.';
    } else {
        db()->prepare('UPDATE guests SET email = ? WHERE id = ?')->execute([$email, $guestId]);
        unset($_SESSION['ticket_guest_id'], $_SESSION['ticket_ready']);
        flash('success', 'Email address updated. Please enter your details again to receive your ticket.');
        redirect('ticket/');
    }
}

page_header('Change email', 'bg-gray-50 min-h-screen flex flex-col');
?>
<header class="flex justify-between items-center px-6 py-4 bg-white shadow-sm">
  <h1 class="text-2xl font-bold text-slate-700"><?= e(config('app.name')) ?></h1>
  <a href="./" class="text-slate-600 hover:text-slate-900 font-semibold">Back</a>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-10">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8 sm:p-12">
    <h2 class="text-2xl font-bold text-slate-900 mb-6 text-center">Change email address</h2>
    <?php if ($error !== ''): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-6">
      <?= csrf_field() ?>
      <label for="email" class="block font-semibold text-slate-800">New email address</label>
      <input type="email" name="email" id="email" required maxlength="255"
             class="w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-700">
      <button type="submit" class="w-full bg-slate-700 hover:bg-slate-900 text-white font-semibold py-3 rounded">Update email</button>
    </form>
  </div>
</main>
<?php page_footer();
