<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = current_user();
if ($user !== null) {
    redirect(dashboard_for($user['role']));
}

page_header('Log in', 'min-h-screen flex items-center justify-center relative bg-slate-900', ['assets/css/login.css']);
?>
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

  <div class="relative z-10 bg-white/90 rounded-2xl shadow-xl p-6 w-[90%] max-w-md animate-fade-in">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Log in</h1>

    <?php render_flashes(); ?>

    <form action="<?= e(url('auth/login.php')) ?>" method="POST" class="space-y-4">
      <?= csrf_field() ?>
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" id="username" required maxlength="50" autocomplete="username"
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" name="password" id="password" required autocomplete="current-password"
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
      </div>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg w-full font-semibold">
        Log in
      </button>
    </form>

    <div class="mt-6 text-center">
      <a href="<?= e(url('')) ?>" class="text-gray-700 hover:text-blue-600 font-medium underline">&larr; Back to homepage</a>
    </div>
  </div>
</body>
</html>
