<?php
require_once __DIR__ . '/includes/auth.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Mentality</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="center">
  <form class="card" method="post" novalidate>
    <h1>Welcome Back!</h1>
    <p class="sub">Log in to continue your journey.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?= csrf_field() ?>
    <label for="login">Email or Username</label>
    <input type="text" id="login" name="login" value="<?= e($login) ?>" autofocus>
    <label for="password">Password</label>
    <input type="password" id="password" name="password">

    <button type="submit">Login</button>
    <div class="link-row"><a href="forgot_password.php">Forgot Password?</a></div>
    <div class="link-row">Don't have an account? <a href="register.php">Register</a></div>
  </form>
</div>
</body>
</html>
