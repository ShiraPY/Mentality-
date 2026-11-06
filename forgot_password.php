<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_layout.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address (it must contain '@' and a domain).";
    } else {
        // "Email connected to account?" (flowchart)
        $stmt = $pdo->prepare('SELECT user_id, username, full_name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'No account is connected to that email.';
        } else {
            $pin = (string)random_int(100000, 999999);          // 6-digit code
            try {
                $pdo->beginTransaction();
                // Cancel any older unused codes, then save the new one (hashed, valid for 10 minutes)
                $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE user_id = ? AND is_used = 0')
                    ->execute([$user['user_id']]);
                $pdo->prepare('INSERT INTO password_resets (user_id, pin_hash, expires_at)
                               VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))')
                    ->execute([$user['user_id'], password_hash($pin, PASSWORD_BCRYPT)]);
                $resetId = (int)$pdo->lastInsertId();
                log_audit($pdo, (int)$user['user_id'], $user['username'], 'RESET_REQUEST', 'password_resets', $resetId, 'Password reset code requested');
                $pdo->commit();

                send_reset_email($user['email'], $user['full_name'], $pin);
                $_SESSION['reset_user_id'] = (int)$user['user_id'];
                unset($_SESSION['reset_verified']);
                header('Location: ' . BASE_URL . '/verify_pin.php');
                exit;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<?php auth_open('Forgot Password', 'Reset your password', 'Enter your registered email address.', '<div class="steps"><span class="step active">1 Email</span><span class="step ">2 Verify PIN</span><span class="step ">3 New Password</span></div>'); ?>
<form class="au-form" method="post" novalidate>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label class="au-label" for="email">Email address <span class="req">*</span></label>
    <input class="au-input" type="email" id="email" name="email" value="<?= e($email) ?>" autofocus>
    <button type="submit" class="au-btn">Send PIN</button>
    <p class="au-foot"><a class="au-link" href="login.php">&larr; Back to log in</a></p>
</form>
<?php auth_close(); ?>
