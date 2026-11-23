<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/guests.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/security_gate.php';

$user = require_role(['admin', 'security']);

if ($user['role'] === 'security' && !$user['introduction_done']) {
    redirect('security/guide.php');
}

$unlockError = handle_developer_unlock($user);
$dashboardOpen = time_reached('event.security_dashboard_opens_at') || developer_unlocked();

if (!$dashboardOpen) {
    page_header('Security dashboard', 'bg-gray-100 min-h-screen flex items-center justify-center p-4');
    ?>
  <div class="bg-white shadow-md rounded-lg p-6 sm:p-8 w-full max-w-lg text-center">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Hi there!</h1>
    <p class="text-gray-800 mb-4">Great that you are preparing early.</p>
    <p class="text-gray-700 mb-6">The security dashboard opens on <strong><?= e(format_event_time('event.security_dashboard_opens_at')) ?></strong>.</p>
    <a href="guide.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded shadow">Open the guide</a>
    <?= developer_unlock_form($unlockError) ?>
  </div>
</body>
</html>
    <?php
    exit;
}

$counts = guest_counts();
$result = $_SESSION['scan_result'] ?? null;
unset($_SESSION['scan_result']);
$leaveListOpen = time_reached('event.leave_list_opens_at');

$styles = [
    'success' => 'bg-green-100 border border-green-400 text-green-700',
    'warning' => 'bg-yellow-100 border border-yellow-400 text-yellow-700',
    'error' => 'bg-red-100 border border-red-400 text-red-700',
];

page_header('Ticket scanner', 'bg-white text-gray-900 font-sans p-5 min-h-screen flex flex-col', ['assets/css/scanner.css']);
?>
<?php if ($leaveListOpen): ?>
<div class="sticky top-0 z-40 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-4 shadow text-center">
  <strong class="block text-lg mb-1">List available</strong>
  <a href="leave_list.php" class="underline font-semibold text-blue-800">Open the list of minors who have to leave</a>
</div>
<?php endif; ?>

<header class="flex justify-between items-center mb-6">
  <h1 class="text-2xl font-bold leading-tight max-w-xs"><?= e(config('app.name')) ?> Ticket Scanner</h1>
  <div class="flex items-center gap-4">
    <a href="guide.php" class="text-sm text-blue-600 hover:underline">Guide</a>
    <?php if ($user['role'] === 'admin'): ?>
    <a href="../admin/" class="text-sm text-blue-600 hover:underline">Admin</a>
    <?php endif; ?>
    <?= logout_button() ?>
  </div>
</header>

<main class="flex-grow max-w-md mx-auto w-full space-y-8">
  <section class="grid grid-cols-2 gap-4">
    <div class="bg-gray-100 p-5 rounded-xl shadow text-center">
      <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Expected</p>
      <p class="text-4xl font-extrabold"><?= $counts['total'] ?></p>
    </div>
    <div class="bg-gray-100 p-5 rounded-xl shadow text-center">
      <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Inside</p>
      <p class="text-4xl font-extrabold"><?= $counts['present'] ?></p>
    </div>
  </section>

  <section id="resultContainer">
    <?php if ($result && $result['type'] === 'found'): $rules = $result['rules']; ?>
    <div class="bg-gray-100 border border-gray-400 px-6 py-4 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-2">Guest found</h2>
      <p><strong>Name:</strong> <?= e($result['guest']['name']) ?></p>
      <p><strong>Age group:</strong> <?= e($result['guest']['age']) ?></p>
      <hr class="my-2">
      <p><strong>Wristband:</strong> <?= e($rules['band']) ?></p>
      <p><strong>Alcohol:</strong> <?= e($rules['alcohol']) ?></p>
      <?php if ($rules['curfew']): ?>
      <p class="mt-2 text-red-700 font-semibold">No parental consent: the guest may enter but has to leave by <?= e(config('event.minor_curfew')) ?>.</p>
      <?php endif; ?>
      <form method="POST" action="scan.php" class="mt-4 flex gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="guest_id" value="<?= (int) $result['guest']['id'] ?>">
        <button type="submit" name="action" value="admit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Grant entry</button>
        <button type="submit" name="action" value="deny" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Deny entry</button>
      </form>
      <p class="mt-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 rounded text-sm">
        <strong>Reminder:</strong> always check an ID to confirm the guest's identity.
      </p>
    </div>
    <?php elseif ($result): ?>
    <div class="<?= $styles[$result['type']] ?? $styles['error'] ?> px-4 py-3 rounded"><?= e($result['message']) ?></div>
    <?php endif; ?>
  </section>

  <section>
    <button id="openScannerBtn" type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded shadow text-lg">
      Scan ticket
    </button>
  </section>

  <section>
    <form method="POST" action="scan.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="lookup">
      <input type="text" name="fallback_code" maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off" required
             placeholder="Fallback code (4 digits)"
             class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 rounded shadow text-lg">Check code</button>
    </form>
  </section>

  <?php if (!$leaveListOpen): ?>
  <section class="bg-yellow-50 border-l-8 border-yellow-400 rounded-lg p-5 shadow-md text-center">
    <h2 class="text-xl font-semibold mb-3 text-yellow-800">About the minors list</h2>
    <p class="text-gray-800 mb-2">
      The list of minors without parental consent opens on <strong><?= e(format_event_time('event.leave_list_opens_at')) ?></strong>.
      For legal reasons these guests have to leave by <?= e(config('event.minor_curfew')) ?>.
    </p>
  </section>
  <?php endif; ?>
</main>

<div id="scannerOverlay" class="fixed inset-0 bg-black bg-opacity-90 flex flex-col items-center justify-center p-4 hidden z-50">
  <div class="bg-white rounded-lg p-4 w-full max-w-md shadow-lg relative">
    <button id="closeScannerBtn" type="button" class="absolute top-3 right-4 text-gray-700 hover:text-gray-900 text-4xl font-bold" aria-label="Close scanner">&times;</button>
    <div id="reader" class="w-full"></div>
    <div class="flex justify-center mt-4 gap-4">
      <button id="switchCameraBtn" type="button" class="bg-gray-300 hover:bg-gray-400 px-5 py-2 rounded text-sm">Switch camera</button>
      <button id="toggleFlashBtn" type="button" class="bg-gray-300 hover:bg-gray-400 px-5 py-2 rounded text-sm">Flash on/off</button>
    </div>
  </div>
</div>

<form id="qrForm" method="POST" action="scan.php" class="hidden">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="lookup">
  <input type="hidden" name="qr_code" id="qrCodeInput">
</form>

<?php page_footer(['https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', 'security/js/scanner.js']);
