<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/guests.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/security_gate.php';

$user = require_role(['admin', 'security']);
$unlockError = handle_developer_unlock($user);

if (!time_reached('event.leave_list_opens_at') && !developer_unlocked()) {
    page_header('List not available yet', 'bg-gray-50 flex justify-center items-center min-h-screen p-4');
    ?>
  <div class="bg-white shadow-md rounded-lg p-8 max-w-lg text-center">
    <h1 class="text-3xl font-bold mb-4 text-gray-900">Not yet!</h1>
    <p class="text-gray-700 text-lg mb-6">
      The list of guests who have to leave opens on <strong><?= e(format_event_time('event.leave_list_opens_at')) ?></strong>.
    </p>
    <a href="./" class="text-blue-600 hover:underline text-lg">&larr; Back to the dashboard</a>
    <?= developer_unlock_form($unlockError) ?>
  </div>
</body>
</html>
    <?php
    exit;
}

$counts = guest_counts();
$guests = guests_to_send_home();

page_header('Minors who have to leave', 'bg-white text-gray-900 p-6');
?>
  <a href="./" class="text-blue-600 hover:underline inline-block mb-6">&larr; Back</a>

  <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6" role="alert">
    <strong>Attention:</strong> these guests are under 18, have no parental consent and their guardian is not inside.
    They have to leave by <?= e(config('event.minor_curfew')) ?>.
  </div>

  <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-100 p-4 rounded-xl shadow">
      <p class="text-sm text-gray-500">Expected guests</p>
      <p class="text-2xl font-bold"><?= $counts['total'] ?></p>
    </div>
    <div class="bg-gray-100 p-4 rounded-xl shadow">
      <p class="text-sm text-gray-500">Guests inside</p>
      <p class="text-2xl font-bold"><?= $counts['present'] ?></p>
    </div>
  </section>

  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left border border-gray-300">
      <thead class="bg-gray-200">
        <tr>
          <th class="px-4 py-2 border">First name</th>
          <th class="px-4 py-2 border">Last name</th>
          <th class="px-4 py-2 border">Guardian</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($guests as $guest): ?>
        <tr class="border-t">
          <td class="px-4 py-2"><?= e($guest['first_name']) ?></td>
          <td class="px-4 py-2"><?= e($guest['last_name']) ?></td>
          <td class="px-4 py-2"><?= e($guest['guardian_name'] ?? '–') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($guests === []): ?>
        <tr><td colspan="3" class="text-center py-4">Nobody has to leave.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php page_footer();
