<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

function mask_email(string $email): string
{
    [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $visible = mb_substr($name, 0, 2);
    return $visible . str_repeat('*', max(mb_strlen($name) - 2, 3)) . '@' . $domain;
}

$verified = null;
$mismatchHint = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $fallbackCode = trim((string) ($_POST['fallback_code'] ?? ''));
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    unset($_SESSION['ticket_guest_id'], $_SESSION['ticket_ready']);

    if (too_many_attempts('ticket-claim', client_ip(), 10, 15 * 60)) {
        flash('error', 'Too many attempts. Please try again in 15 minutes.');
        redirect('ticket/');
    }

    if (!preg_match('/^\d{4}$/', $fallbackCode) || $firstName === '' || $lastName === '') {
        flash('error', 'Please fill in all fields.');
        redirect('ticket/');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        flash('error', 'Please enter a valid email address.');
        redirect('ticket/');
    }

    $stmt = db()->prepare('SELECT id, first_name, last_name, email FROM guests WHERE fallback_code = ? AND blocked = 0');
    $stmt->execute([$fallbackCode]);
    $guest = $stmt->fetch();

    $matches = $guest
        && mb_strtolower($guest['first_name']) === mb_strtolower($firstName)
        && mb_strtolower($guest['last_name']) === mb_strtolower($lastName);

    if (!$matches) {
        record_attempt('ticket-claim', client_ip());
        flash('error', 'Fallback code and name do not match any ticket.');
        redirect('ticket/');
    }

    session_regenerate_id(true);
    $_SESSION['ticket_guest_id'] = (int) $guest['id'];

    if ($guest['email'] === null) {
        db()->prepare('UPDATE guests SET email = ? WHERE id = ?')->execute([$email, $guest['id']]);
    } elseif (strcasecmp($guest['email'], $email) !== 0) {
        $_SESSION['ticket_mismatch_hint'] = mask_email($guest['email']);
        redirect('ticket/');
    }

    $_SESSION['ticket_ready'] = true;
    $_SESSION['ticket_first_name'] = $guest['first_name'];
    redirect('ticket/');
}

$ready = !empty($_SESSION['ticket_ready']);
$mismatchHint = $_SESSION['ticket_mismatch_hint'] ?? '';
unset($_SESSION['ticket_mismatch_hint']);

page_header('Ticket check', 'bg-gray-50 min-h-screen flex flex-col', ['assets/css/ticket.css']);
?>
<header class="flex justify-between items-center px-6 py-4 bg-white shadow-sm sticky top-0 z-10">
  <a href="<?= e(url('')) ?>" class="text-2xl font-bold text-slate-700"><?= e(config('app.name')) ?></a>
  <a href="<?= e(url('login/')) ?>" class="text-slate-600 hover:text-slate-900 font-semibold">Log in</a>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-10">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8 sm:p-12">
    <h2 class="text-3xl font-extrabold text-slate-900 mb-8 text-center">Ticket check</h2>

    <?php render_flashes(); ?>

    <?php if ($mismatchHint !== ''): ?>
    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded text-sm leading-relaxed">
      This ticket is already linked to another email address starting with <strong><?= e($mismatchHint) ?></strong>.<br>
      <a href="change_email.php" class="underline">Change the email address</a>
    </div>
    <?php endif; ?>

    <?php if ($ready): ?>
    <div id="sendState" data-endpoint="send.php" data-csrf="<?= e(csrf_token()) ?>" class="flex flex-col items-center gap-4">
      <span class="loader"></span>
      <p class="mt-4 text-slate-600">Sending your digital ticket by email&hellip;</p>
    </div>
    <div id="sendSuccess" class="hidden text-center px-6">
      <div class="success-animation">
        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
          <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
          <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
        </svg>
      </div>
      <h3 class="text-2xl font-semibold text-green-700 mb-2">Hi <?= e($_SESSION['ticket_first_name'] ?? '') ?>,</h3>
      <p class="text-slate-700 mb-6">your ticket has been validated and is on its way to your inbox.</p>
      <p class="mt-8 text-slate-600">See you there!<br><?= e(config('app.name')) ?></p>
    </div>
    <?php else: ?>
    <form id="step1" novalidate>
      <label for="fallback_code" class="block font-semibold mb-1 text-slate-800">Fallback code</label>
      <p class="text-sm text-slate-500 mb-4">You find it on your ticket next to <strong>Fallback code</strong>.</p>
      <input type="text" id="fallback_code" inputmode="numeric" maxlength="4" pattern="\d{4}" required autocomplete="off"
             class="w-full rounded border border-gray-300 px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-slate-700">
      <button type="submit" class="w-full bg-slate-700 hover:bg-slate-900 text-white font-semibold py-3 rounded">Continue</button>
    </form>

    <form id="step2" method="POST" class="hidden">
      <?= csrf_field() ?>
      <input type="hidden" name="fallback_code" id="hidden_fallback_code">
      <label for="first_name" class="block font-semibold mb-1 text-slate-800">First name</label>
      <input type="text" id="first_name" name="first_name" required maxlength="100" autocomplete="given-name"
             class="w-full rounded border border-gray-300 px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-slate-700">
      <label for="last_name" class="block font-semibold mb-1 text-slate-800">Last name</label>
      <input type="text" id="last_name" name="last_name" required maxlength="100" autocomplete="family-name"
             class="w-full rounded border border-gray-300 px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-slate-700">
      <label for="email" class="block font-semibold mb-1 text-slate-800">Email</label>
      <input type="email" id="email" name="email" required maxlength="255" autocomplete="email"
             class="w-full rounded border border-gray-300 px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-slate-700">
      <button type="submit" class="w-full bg-slate-700 hover:bg-slate-900 text-white font-semibold py-3 rounded">Check ticket</button>
    </form>
    <?php endif; ?>
  </div>
</main>
<?php page_footer(['ticket/ticket.js']);
