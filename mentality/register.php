<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/quotes.php';

if (is_logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$errors  = [];
$success = false;
$old     = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username']  = trim($_POST['username'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm          = $_POST['confirm_password'] ?? '';

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    // ---- Input validation with specific messages
    if ($old['full_name'] === '' || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Full name is required (max 100 characters).';
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $old['username'])) {
        $errors[] = 'Username must be 3-50 characters: letters, numbers and underscore only.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address (it must contain '@' and a domain).";
    }
    $errors = array_merge($errors, validate_password($password, $confirm));

    // ---- "Username already taken?" check from the flowchart
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$old['username'], $old['email']]);
        foreach ($stmt->fetchAll() as $row) {
            if (strcasecmp($row['username'], $old['username']) === 0) { $errors[] = 'That username is already taken.'; }
            if (strcasecmp($row['email'], $old['email']) === 0)       { $errors[] = 'That email is already registered.'; }
        }
        $errors = array_unique($errors);
    }

    // ---- Save account: TRANSACTION (all steps succeed, or none are saved)
    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, username, email, password_hash, role_id) VALUES (?, ?, ?, ?, 3)'
            );
            $stmt->execute([
                $old['full_name'], $old['username'], $old['email'],
                password_hash($password, PASSWORD_BCRYPT),      // hashed + salted automatically
            ]);
            $newId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO user_settings (user_id) VALUES (?)')->execute([$newId]);
            log_audit($pdo, $newId, $old['username'], 'REGISTER', 'users', $newId, 'New account created');

            $pdo->commit();
            $success = true;
            $old = ['full_name' => '', 'username' => '', 'email' => ''];
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = ($ex->getCode() === '23000')
                ? 'That username or email was just taken. Please choose another.'
                : 'Something went wrong while saving your account. Please try again.';
        }
    }
}
// Puts the last two words of a quote in the accent colour (same as the login page).
function quote_html(string $q): string {
    $w = explode(' ', $q);
    if (count($w) < 4) { return e($q); }
    return e(implode(' ', array_slice($w, 0, -2))) . ' <span class="accent">' . e(implode(' ', array_slice($w, -2))) . '</span>';
}
$quotes = all_quotes();
$first  = $quotes[array_rand($quotes)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Mentality</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="login-page">
<main class="lp">

    <!-- LEFT: quotes, same as the login page -->
    <section class="lp-left">
        <div class="lp-wordmark">mentality</div>
        <div class="lp-quote-wrap">
            <p class="lp-label">A thought for today</p>
            <h1 class="lp-quote" id="login-quote" data-quotes="<?= e(json_encode($quotes, JSON_UNESCAPED_UNICODE)) ?>"><?= quote_html($first) ?></h1>
        </div>
    </section>

    <!-- RIGHT: the registration form -->
    <section class="lp-right">
        <form class="lp-form lp-form-register" method="post" novalidate>
            <h2>Create your Mentality account</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">Registration successful! <a href="login.php">Log in now</a>.</div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?= csrf_field() ?>
            <label class="sr-only" for="full_name">Full name</label>
            <input class="lp-input" type="text" id="full_name" name="full_name" placeholder="Full name" value="<?= e($old['full_name']) ?>" autocomplete="name" autofocus>

            <label class="sr-only" for="username">Username</label>
            <input class="lp-input" type="text" id="username" name="username" placeholder="Username" value="<?= e($old['username']) ?>" autocomplete="username">

            <label class="sr-only" for="email">Email</label>
            <input class="lp-input" type="email" id="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" autocomplete="email">

            <label class="sr-only" for="password">Password</label>
            <div class="lp-pw">
                <input class="lp-input" type="password" id="password" name="password" placeholder="Password" autocomplete="new-password">
                <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
            </div>

            <label class="sr-only" for="confirm_password">Confirm password</label>
            <div class="lp-pw">
                <input class="lp-input" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password">
                <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
            </div>

            <button type="submit" class="lp-btn">Register</button>
            <a class="lp-create" href="login.php">Already have an account? Log in</a>
            <div class="lp-foot">mentality</div>
        </form>
    </section>

</main>
<script src="<?= BASE_URL ?>/assets/auth.js"></script>
</body>
</html> 