<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/quotes.php';

if (is_logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Please enter both your username/email and your password.';
    } else {
        // Prepared statement: user input is NEVER placed inside the SQL text.
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.username, u.full_name, u.password_hash, u.status, r.role_name
             FROM users u JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = ? OR u.email = ? LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {     // "Credentials valid?"
            if ($user['status'] === 'suspended') {                              // "Account suspended?"
                $error = 'This account has been suspended. Please contact an administrator.';
                log_audit($pdo, (int)$user['user_id'], $user['username'], 'LOGIN_BLOCKED', 'users', (int)$user['user_id'], 'Suspended account tried to log in');
            } else {
                session_regenerate_id(true);                                    // stops session fixation
                $_SESSION['user_id']  = (int)$user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']     = $user['role_name'];
                log_audit($pdo, (int)$user['user_id'], $user['username'], 'LOGIN', 'users', (int)$user['user_id']);
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password.';                           // same message for both cases
            log_audit($pdo, null, $login, 'LOGIN_FAILED', 'users', null, 'Wrong credentials');
        }
    }
}

// Puts the last two words of a quote in the accent colour (the same idea as "you love." on Facebook's page).
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
    <title>Log in - Mentality</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="login-page">
<main class="lp">

    <!-- LEFT: quotes instead of a picture -->
    <section class="lp-left">
        <div class="lp-wordmark">mentality</div>
        <div class="lp-quote-wrap">
            <p class="lp-label">A thought for today</p>
            <h1 class="lp-quote" id="login-quote" data-quotes="<?= e(json_encode($quotes, JSON_UNESCAPED_UNICODE)) ?>"><?= quote_html($first) ?></h1>
        </div>
    </section>

    <!-- RIGHT: the login form -->
    <section class="lp-right">
        <form class="lp-form" method="post" novalidate>
            <h2>Log in to Mentality</h2>

            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <?= csrf_field() ?>
            <label class="sr-only" for="login">Email or username</label>
            <input class="lp-input" type="text" id="login" name="login" placeholder="Email or username" value="<?= e($login) ?>" autocomplete="username" autofocus>

            <label class="sr-only" for="password">Password</label>
            <div class="lp-pw">
                <input class="lp-input" type="password" id="password" name="password" placeholder="Password" autocomplete="current-password">
                <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
            </div>

            <button type="submit" class="lp-btn">Log in</button>
            <a class="lp-forgot" href="forgot_password.php">Forgot password?</a>
            <a class="lp-create" href="register.php">Create new account</a>
            <div class="lp-foot">mentality</div>
        </form>
    </section>

</main>
<script src="<?= BASE_URL ?>/assets/auth.js"></script>
</body>
</html>
