<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_layout.php';

// Only reachable after the PIN was verified
if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_verified'])) {
    header('Location: ' . BASE_URL . '/forgot_password.php');
    exit;
}
$uid     = (int)$_SESSION['reset_user_id'];
$resetId = (int)$_SESSION['reset_verified'];
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!csrf_check()) { $errors[] = 'Your session expired. Please try again.'; }
    $errors = array_merge($errors, validate_password($password, $confirm));

    if (!$errors) {
        // Re-check that the verified code is still valid (not used, not expired)
        $stmt = $pdo->prepare('SELECT r.reset_id, u.username FROM password_resets r
                               JOIN users u ON u.user_id = r.user_id
                               WHERE r.reset_id = ? AND r.user_id = ? AND r.is_used = 0 AND r.expires_at > NOW()');
        $stmt->execute([$resetId, $uid]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = 'Your reset session expired. Please start again.';
        } else {
            try {
                $pdo->beginTransaction();                                  // all-or-nothing
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                    ->execute([password_hash($password, PASSWORD_BCRYPT), $uid]);
                $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE reset_id = ?')->execute([$resetId]);
                log_audit($pdo, $uid, $row['username'], 'PASSWORD_RESET', 'users', $uid, 'Password changed via reset code');
                $pdo->commit();

                unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['demo_inbox']);
                $success = true;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errors[] = 'Something went wrong while saving your new password. Please try again.';
            }
        }
    }
}
?>
<?php auth_open('New Password', 'Create new password', 'Enter your new password below.', '<div class="steps"><span class="step done">1 Email</span><span class="step done">2 Verify PIN</span><span class="step active">3 New Password</span></div>'); ?>
<form class="au-form" method="post" novalidate>
    <?php if ($success): ?>
        <div class="alert alert-success">Your password was changed. <a class="au-link" href="login.php">Back to log in</a></div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?= csrf_field() ?>
    <label class="au-label" for="password">New password <span class="req">*</span></label>
    <div class="au-pw">
        <input class="au-input" type="password" id="password" name="password" autocomplete="new-password" autofocus>
        <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
    </div>
    <label class="au-label" for="confirm_password">Confirm password <span class="req">*</span></label>
    <div class="au-pw">
        <input class="au-input" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
        <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
    </div>
        <button type="submit" class="au-btn">Reset Password</button>
    <?php endif; ?>
</form>
<?php auth_close(); ?>
