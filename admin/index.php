<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/guests.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role(['admin']);
$fullAdmin = is_full_admin($user);

$counts = guest_counts();
$roleCounts = db()->query('SELECT role, COUNT(*) AS n FROM accounts GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);

page_header('Admin dashboard', 'bg-white text-gray-800 font-sans p-4');
?>
<header class="flex justify-between items-center mb-6">
  <h1 class="text-2xl font-bold leading-tight max-w-xs"><?= e(config('app.name')) ?> Admin Dashboard</h1>
  <?= logout_button() ?>
</header>

<?php render_flashes(); ?>

<section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-gray-100 p-4 rounded-xl shadow">
    <p class="text-sm text-gray-500">Expected guests</p>
    <p class="text-2xl font-bold"><?= $counts['total'] ?></p>
  </div>
  <div class="bg-gray-100 p-4 rounded-xl shadow">
    <p class="text-sm text-gray-500">Guests inside</p>
    <p class="text-2xl font-bold"><?= $counts['present'] ?></p>
  </div>
  <div class="bg-gray-100 p-4 rounded-xl shadow">
    <p class="text-sm text-gray-500">Security staff</p>
    <p class="text-2xl font-bold"><?= (int) ($roleCounts['security'] ?? 0) ?></p>
  </div>
  <?php if ($fullAdmin): ?>
  <div class="bg-gray-100 p-4 rounded-xl shadow">
    <p class="text-sm text-gray-500">Admins</p>
    <p class="text-2xl font-bold"><?= (int) ($roleCounts['admin'] ?? 0) ?></p>
  </div>
  <?php endif; ?>
</section>

<section class="mb-8">
  <h2 class="text-xl font-semibold mb-2">Guests</h2>
  <div class="grid md:grid-cols-2 gap-4">
    <?php if ($fullAdmin): ?>
    <button type="button" data-open="guestAddOverlay" class="bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">Add guest</button>
    <?php endif; ?>
    <a href="guests/" class="bg-green-500 text-white p-3 rounded-lg text-center hover:bg-green-600">Manage guest list</a>
  </div>
</section>

<section class="mb-8">
  <h2 class="text-xl font-semibold mb-2">User accounts</h2>
  <div class="grid md:grid-cols-2 gap-4">
    <?php if ($fullAdmin): ?>
    <button type="button" data-open="adminAddOverlay" class="bg-purple-500 text-white p-3 rounded-lg hover:bg-purple-600">Add admin</button>
    <?php endif; ?>
    <button type="button" data-open="securityAddOverlay" class="bg-yellow-500 text-white p-3 rounded-lg hover:bg-yellow-600">Add security staff</button>
  </div>
</section>

<?php if ($fullAdmin): ?>
<section class="mb-8">
  <h2 class="text-xl font-semibold mb-2">Other</h2>
  <div class="grid md:grid-cols-2 gap-4">
    <a href="../security/" class="bg-purple-500 text-white p-3 rounded-lg text-center hover:bg-purple-600">Security dashboard</a>
  </div>
</section>

<div id="guestAddOverlay" class="overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white p-6 rounded-lg max-w-md w-full max-h-[90vh] overflow-auto shadow-lg relative">
    <button type="button" data-close class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl font-bold" aria-label="Close">&times;</button>
    <h2 class="text-xl font-semibold mb-4">Add guest</h2>

    <form id="guestAddForm" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block mb-1 font-medium" for="first_name">First name</label>
        <input type="text" id="first_name" name="first_name" required maxlength="100" class="w-full px-3 py-2 border rounded">
      </div>
      <div>
        <label class="block mb-1 font-medium" for="last_name">Last name</label>
        <input type="text" id="last_name" name="last_name" required maxlength="100" class="w-full px-3 py-2 border rounded">
      </div>
      <div>
        <label class="block mb-1 font-medium" for="age_group">Age</label>
        <select id="age_group" name="age_group" required class="w-full px-3 py-2 border rounded">
          <option value="">-- choose --</option>
          <?php foreach (AGE_GROUPS as $value => $label): ?>
          <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="text-right">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add</button>
      </div>
    </form>

    <div id="guestAddResult" class="mt-4 text-center hidden"></div>

    <div id="ticketPreview" class="mt-6 text-center hidden">
      <p class="font-semibold mb-2">Ticket for the guest:</p>
      <img id="ticketImage" alt="Guest ticket" class="max-w-full object-contain border shadow-md mb-4">
      <p>Fallback code: <span id="fallbackCode" class="font-mono text-lg"></span></p>
      <a id="ticketDownload" href="#" class="inline-block mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Download ticket</a>
    </div>

    <hr class="my-6">

    <h3 class="text-lg font-semibold mb-2">Import guests from JSON</h3>
    <form method="POST" action="add_guest.php" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <input type="file" name="guest_json" accept=".json,application/json" required class="w-full border rounded px-3 py-2">
      <p class="text-xs text-gray-500">
        Format: <code>[{"first_name": "…", "last_name": "…", "age": "minor|adult", "guardian_name": "…"}]</code>
      </p>
      <div class="text-right">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Upload &amp; generate tickets</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php foreach (['admin' => 'Add admin', 'security' => 'Add security staff'] as $role => $title): ?>
<?php if ($role === 'admin' && !$fullAdmin) continue; ?>
<div id="<?= $role ?>AddOverlay" class="overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white p-6 rounded-lg max-w-md w-full shadow-lg relative">
    <button type="button" data-close class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl font-bold" aria-label="Close">&times;</button>
    <h2 class="text-xl font-semibold mb-4"><?= e($title) ?></h2>
    <form method="POST" action="create_account.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="role" value="<?= $role ?>">
      <div>
        <label class="block mb-1 font-medium">Username</label>
        <input type="text" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" class="w-full px-3 py-2 border rounded">
      </div>
      <div>
        <label class="block mb-1 font-medium">Password</label>
        <input type="password" name="password" required minlength="10" autocomplete="new-password" class="w-full px-3 py-2 border rounded">
      </div>
      <?php if ($role === 'admin'): ?>
      <label class="flex items-center space-x-2">
        <input type="checkbox" name="guest_list_only" value="1" class="w-4 h-4">
        <span class="font-medium">Restricted: may only manage the guest list and add security staff</span>
      </label>
      <?php endif; ?>
      <div class="text-right">
        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Create</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<?php page_footer(['admin/js/dashboard.js']);
