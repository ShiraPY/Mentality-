<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_layout.php';
require_once __DIR__ . '/includes/mailer.php';

if (empty($_SESSION['reset_user_id'])) { header('Location: ' . BASE_URL . '/forgot_password.php'); exit; }
$uid   = (int)$_SESSION['reset_user_id'];
$error = '';
$maxAttempts = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!preg_match('/^[0-9]{6}$/', $pin)) {
        $error = 'Please enter the 6-digit code (numbers only).';
    } else {
        $stmt = $pdo->prepare('SELECT reset_id, pin_hash, attempts FROM password_resets
                               WHERE user_id = ? AND is_used = 0 AND expires_at > NOW()
                               ORDER BY reset_id DESC LIMIT 1');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'This code has expired or was already used. Please request a new one.';
        } elseif ((int)$row['attempts'] >= $maxAttempts) {
            $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE reset_id = ?')->execute([$row['reset_id']]);
            $error = 'Too many wrong attempts. Please request a new code.';
        } elseif (password_verify($pin, $row['pin_hash'])) {            // "6 pin correct?" (flowchart)
            session_regenerate_id(true);
            $_SESSION['reset_verified'] = (int)$row['reset_id'];
            log_audit($pdo, $uid, null, 'RESET_PIN_OK', 'password_resets', (int)$row['reset_id'], 'Reset code verified');
            header('Location: ' . BASE_URL . '/new_password.php');
            exit;
        } else {
            $pdo->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE reset_id = ?')->execute([$row['reset_id']]);
            log_audit($pdo, $uid, null, 'RESET_PIN_FAILED', 'password_resets', (int)$row['reset_id'], 'Wrong reset code');
            $left = $maxAttempts - ((int)$row['attempts'] + 1);
            $error = 'Invalid code! You have ' . max($left, 0) . ' attempt(s) left.';
        }
    }
}
?>
<?php auth_open('Verify PIN', 'Enter verification code', 'We sent a 6-digit code to your email.', '<div class="steps"><span class="step done">1 Email</span><span class="step active">2 Verify PIN</span><span class="step ">3 New Password</span></div>'); ?>
<form class="au-form" method="post" novalidate>
    <?php if (MAIL_MODE === 'demo' && !empty($_SESSION['demo_inbox'])): ?>
        <div class="alert alert-success">Demo mode: <a class="au-link" href="demo_inbox.php" target="_blank">open the simulated inbox</a> to see your code.</div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label class="au-label" for="pin">6-digit code <span class="req">*</span></label>
    <input class="au-input pin" type="text" id="pin" name="pin" maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus>
    <button type="submit" class="au-btn">Verify Code</button>
    <p class="au-foot">Didn't get it? <a class="au-link" href="forgot_password.php">Send a new code</a></p>
</form>
<?php auth_close(); ?>
