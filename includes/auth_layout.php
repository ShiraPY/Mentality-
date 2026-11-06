<?php
// includes/auth_layout.php - the frame shared by the sign-in pages (login, register, forgot password, PIN, new password):
//   a coloured night-sky background, a card with the form on the left and a daily quote on the right.
//
// Usage in a page:
//   auth_open('Page title', 'Heading', 'Small text under the heading');
//   ... the <form> ...
//   auth_close();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/quotes.php';

// Puts the last two words of a quote in the accent colour.
function auth_quote_html(string $q): string {
    $w = explode(' ', $q);
    if (count($w) < 4) { return e($q); }
    return e(implode(' ', array_slice($w, 0, -2))) . ' <span class="accent">' . e(implode(' ', array_slice($w, -2))) . '</span>';
}

// $topHtml is trusted HTML written by the page itself (for example the 3-step bar of the reset-password pages).
function auth_open(string $title, string $heading, string $subtitle = '', string $topHtml = ''): void {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Mentality</title>
    <link rel="stylesheet" href="<?= e(BASE_URL . '/assets/style.css') ?>">
</head>
<body class="au-page">
<div class="au-bg" aria-hidden="true"><i class="au-orb o1"></i><i class="au-orb o2"></i><i class="au-orb o3"></i></div>
<a class="au-wordmark" href="<?= e(BASE_URL) ?>/login.php">mentality</a>

<main class="au-card">
  <section class="au-form-col">
    <?= $topHtml ?>
    <h1 class="au-title"><?= e($heading) ?></h1>
    <?php if ($subtitle !== ''): ?><p class="au-sub"><?= e($subtitle) ?></p><?php endif; ?>
<?php
}

function auth_close(): void {
    $quotes = all_quotes();
    $first  = $quotes[array_rand($quotes)];
    ?>
  </section>

  <aside class="au-quote-col">
    <div class="au-quote-mark"><?= icon('quote', 40) ?></div>
    <p class="au-kicker">A thought for today</p>
    <blockquote class="au-quote" id="login-quote" data-quotes="<?= e(json_encode($quotes, JSON_UNESCAPED_UNICODE)) ?>"><?= auth_quote_html($first) ?></blockquote>
  </aside>
</main>

<script src="<?= e(BASE_URL . '/assets/auth.js') ?>"></script>
</body>
</html>
<?php
}
