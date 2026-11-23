<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

page_header('Digital ticket', 'bg-gray-50 min-h-screen flex flex-col', ['assets/css/landing.css']);
?>
  <header class="flex justify-between items-center px-6 py-4 bg-white shadow-sm sticky top-0 z-10 fade-slide-up delay-100">
    <h1 class="text-2xl font-bold text-slate-700"><?= e(config('app.name')) ?></h1>
    <a href="<?= e(url('login/')) ?>" class="text-slate-600 hover:text-slate-900 font-semibold">Log in</a>
  </header>

  <main class="flex-grow max-w-4xl mx-auto px-6 sm:px-12 lg:px-24 py-10 space-y-20">
    <section class="text-center fade-slide-up delay-300">
      <h2 class="text-4xl sm:text-5xl font-extrabold text-slate-900 mb-6 leading-tight">Get your ticket in digital form</h2>
      <p class="text-slate-600 max-w-xl mx-auto mb-10 leading-relaxed">
        Claim your secure digital ticket for the <?= e(config('event.title')) ?> &ndash; quick, simple and straight to your phone.
      </p>
      <a href="<?= e(url('ticket/')) ?>"
         class="inline-flex items-center justify-center gap-3 bg-slate-700 text-white px-8 py-3 rounded-md font-semibold shadow-lg hover:bg-slate-900">
        Claim my ticket
      </a>
    </section>

    <section class="bg-white rounded-lg shadow p-8 text-slate-800 fade-slide-up delay-500">
      <h3 class="text-2xl font-bold mb-4">About FinalEntry</h3>
      <p>
        FinalEntry was built by Tobias Herrmann as a voluntary computer science side project in 10th grade.
        The goal was to make entry to the 10th-grade graduation party fast, safe and fully digital.
      </p>
      <p class="mt-6">
        More projects on <a href="https://github.com/encounterbe" target="_blank" rel="noopener noreferrer" class="font-semibold hover:underline">GitHub</a>.
      </p>
    </section>
  </main>
<?php page_footer();
