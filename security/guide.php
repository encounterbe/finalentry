<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role(['admin', 'security']);
$mustConfirm = $user['role'] === 'security' && !$user['introduction_done'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();
    if (($_POST['agree'] ?? '') === '1') {
        db()->prepare('UPDATE accounts SET introduction_done = 1 WHERE id = ?')->execute([$user['id']]);
        redirect('security/');
    }
    $error = 'Please confirm that you have read the whole guide.';
}

$curfew = e(config('event.minor_curfew'));

page_header('Security guide', 'bg-gray-50 text-gray-800 font-sans');
?>
  <header class="bg-blue-100 shadow-md">
    <div class="max-w-4xl mx-auto px-6 py-10 text-center">
      <h1 class="text-4xl font-extrabold text-blue-900 mb-2">Security entry guide</h1>
      <p class="text-blue-800 text-lg max-w-xl mx-auto">Step by step through guest entry &ndash; simple, clear and safe.</p>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-6 py-10 space-y-16">
    <?php if (!$mustConfirm): ?>
    <a href="./" class="text-blue-600 hover:underline">&larr; Back</a>
    <?php endif; ?>

    <section>
      <h2 class="text-3xl font-bold border-b-4 border-blue-700 pb-2 mb-6">Quick start</h2>
      <ol class="list-decimal list-inside space-y-3 text-lg max-w-3xl">
        <li><strong>Scan the ticket:</strong> tap &ldquo;Scan ticket&rdquo; and hold the QR code steady in front of the camera.</li>
        <li><strong>Check the result:</strong> make sure the guest may enter.</li>
        <li><strong>Hand out the wristband:</strong> gold, silver or red, as shown on screen.</li>
        <li><strong>Confirm:</strong> tap &ldquo;Grant entry&rdquo;, or deny entry if necessary.</li>
        <li><strong>Next:</strong> get ready for the next guest.</li>
      </ol>
      <p class="text-sm text-gray-600 italic mt-4">Tip: if the scanner does not react, close it and open it again. You can always type the 4-digit fallback code instead.</p>
    </section>

    <section>
      <h2 class="text-3xl font-bold border-b-4 border-blue-700 pb-2 mb-8">Scan results</h2>

      <div class="bg-green-50 border-l-8 border-green-600 rounded-lg shadow p-6 mb-8 max-w-3xl">
        <h3 class="text-xl font-semibold text-green-800 mb-3">Guest found</h3>
        <ul class="list-disc list-inside text-green-700 space-y-1">
          <li>Check the ID</li>
          <li>Hand out the matching wristband</li>
          <li>Tap &ldquo;Grant entry&rdquo;</li>
        </ul>
        <p class="mt-4 text-green-900 text-sm">Guests under 18 without parental consent have to leave by <?= $curfew ?>.</p>
      </div>

      <div class="bg-yellow-50 border-l-8 border-yellow-500 rounded-lg shadow p-6 mb-8 max-w-3xl">
        <h3 class="text-xl font-semibold text-yellow-800 mb-3">Already admitted</h3>
        <p class="text-yellow-900">The ticket was already used. Contact the organizers before letting anyone in.</p>
      </div>

      <div class="bg-red-50 border-l-8 border-red-600 rounded-lg shadow p-6 mb-8 max-w-3xl">
        <h3 class="text-xl font-semibold text-red-800 mb-3">Unknown ticket</h3>
        <p class="text-red-900">The code is invalid. No entry &ndash; inform the organizers.</p>
      </div>

      <div class="bg-red-100 border-l-8 border-red-700 rounded-lg shadow p-6 mb-8 max-w-3xl">
        <h3 class="text-xl font-semibold text-red-900 mb-3">Guest blocked</h3>
        <p class="text-red-900">The organizers have blocked this person. No entry. Stay friendly.</p>
      </div>
    </section>

    <section class="p-6 bg-white rounded-lg shadow-md space-y-6">
      <h2 class="text-3xl font-bold text-blue-900">Important rules</h2>
      <article>
        <h3 class="text-2xl font-semibold mb-2">1. Guests under 18</h3>
        <p class="text-lg">The scanner shows whether a minor has parental consent. Minors without consent may enter but must leave by <?= $curfew ?> unless their guardian is present.</p>
      </article>
      <article>
        <h3 class="text-2xl font-semibold mb-2">2. Shortly before midnight</h3>
        <p class="text-lg">A banner links to the list of minors who have to leave. Open it and ask those guests to leave politely.</p>
      </article>
      <article>
        <h3 class="text-2xl font-semibold mb-2">3. When in doubt</h3>
        <p class="text-lg">No entry when in doubt, stay calm, no discussions at the door &ndash; call the organizers.</p>
      </article>
    </section>

    <?php if ($mustConfirm): ?>
    <section class="max-w-xl mx-auto p-6 bg-white rounded-lg shadow-md">
      <form method="POST" class="space-y-6">
        <?= csrf_field() ?>
        <label class="flex items-center">
          <input type="checkbox" name="agree" value="1" class="h-5 w-5">
          <span class="ml-3 text-lg font-medium text-gray-700">I have read the whole guide and understand the rules.</span>
        </label>
        <?php if ($error !== ''): ?>
        <p class="text-red-600 font-semibold"><?= e($error) ?></p>
        <?php endif; ?>
        <button type="submit" class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md">I agree</button>
      </form>
    </section>
    <?php endif; ?>
  </main>
<?php page_footer();
