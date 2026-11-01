<?php
require_once __DIR__ . '/includes/auth.php';

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Password - Mentality</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="center">
  <form class="card" method="post" novalidate>
    <div class="steps"><span class="step done">1 Email</span><span class="step done">2 Verify PIN</span><span class="step active">3 New Password</span></div>
    <h1>Create New Password</h1>
    <p class="sub">Enter your new password below.</p>

    <?php if ($success): ?>
        <div class="alert alert-success">Your password was changed. <a href="login.php">Back to Login</a></div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?= csrf_field() ?>
        <label for="password">New Password</label>
        <input type="password" id="password" name="password" autofocus>
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password">
        <button type="submit">Reset Password</button>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
