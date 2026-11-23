<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/guests.php';
require_once __DIR__ . '/../../includes/layout.php';

require_role(['admin']);

$search = trim((string) ($_GET['search'] ?? ''));
if ($search === '') {
    $guests = db()->query('SELECT * FROM guests ORDER BY last_name, first_name')->fetchAll();
} else {
    $stmt = db()->prepare(
        "SELECT * FROM guests
         WHERE CONCAT(first_name, ' ', last_name) LIKE ?
         ORDER BY last_name, first_name"
    );
    $stmt->execute(['%' . addcslashes($search, '%_\\') . '%']);
    $guests = $stmt->fetchAll();
}

page_header('Guest list', 'p-6 bg-white font-sans text-gray-800');
?>
<a href="../" class="text-blue-600 underline mb-4 inline-block">&larr; Back</a>

<h1 class="text-2xl font-bold mb-4">Manage guest list</h1>

<?php render_flashes(); ?>

<form method="GET" class="mb-4 flex gap-2 items-center">
  <input type="text" name="search" placeholder="Search by name" value="<?= e($search) ?>" class="border px-3 py-2 rounded">
  <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Search</button>
</form>

<div class="overflow-x-auto">
<table class="min-w-full table-auto border-collapse border border-gray-300">
  <thead class="bg-gray-100">
    <tr>
      <th class="border px-4 py-2 text-left">First name</th>
      <th class="border px-4 py-2 text-left">Last name</th>
      <th class="border px-4 py-2 text-left">Age</th>
      <th class="border px-4 py-2 text-center">Parental consent</th>
      <th class="border px-4 py-2 text-center">Alcohol allowed</th>
      <th class="border px-4 py-2 text-center">Status</th>
      <th class="border px-4 py-2 text-left">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($guests as $guest): $formId = 'guest-' . (int) $guest['id']; ?>
    <tr class="hover:bg-gray-50">
      <td class="border px-4 py-2">
        <input form="<?= $formId ?>" type="text" name="first_name" value="<?= e($guest['first_name']) ?>" required maxlength="100" class="border px-2 py-1 rounded w-full">
      </td>
      <td class="border px-4 py-2">
        <input form="<?= $formId ?>" type="text" name="last_name" value="<?= e($guest['last_name']) ?>" required maxlength="100" class="border px-2 py-1 rounded w-full">
      </td>
      <td class="border px-4 py-2">
        <select form="<?= $formId ?>" name="age_group" class="border px-2 py-1 rounded w-full">
          <?php foreach (AGE_GROUPS as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $guest['age_group'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td class="border px-4 py-2 text-center">
        <input form="<?= $formId ?>" type="checkbox" name="has_parental_consent" value="1" <?= $guest['has_parental_consent'] ? 'checked' : '' ?>>
      </td>
      <td class="border px-4 py-2 text-center">
        <input form="<?= $formId ?>" type="checkbox" name="alcohol_allowed" value="1" <?= $guest['alcohol_allowed'] ? 'checked' : '' ?>>
      </td>
      <td class="border px-4 py-2 text-center text-sm">
        <?php if ($guest['blocked']): ?>
          <span class="text-red-600 font-semibold">Blocked</span>
        <?php elseif ($guest['checked_in']): ?>
          <span class="text-green-600 font-semibold">Inside</span>
        <?php else: ?>
          <span class="text-gray-500">Expected</span>
        <?php endif; ?>
      </td>
      <td class="border px-4 py-2">
        <div class="flex gap-2">
          <form id="<?= $formId ?>" method="POST" action="update.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $guest['id'] ?>">
            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Save</button>
          </form>
          <form method="POST" action="delete.php" data-confirm="Really delete this guest?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $guest['id'] ?>">
            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($guests === []): ?>
    <tr><td colspan="7" class="text-center py-4 text-gray-500">No guests found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

<?php page_footer(['assets/js/confirm.js']);
