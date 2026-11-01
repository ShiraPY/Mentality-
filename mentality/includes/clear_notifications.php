<?php
require_once __DIR__ . '/includes/layout.php';
require_role(['user', 'moderator', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $pdo->prepare('UPDATE notifications SET is_cleared = 1 WHERE user_id = ? AND is_cleared = 0')->execute([(int)$_SESSION['user_id']]);
    log_audit($pdo, (int)$_SESSION['user_id'], $_SESSION['username'], 'CLEAR_NOTIFICATIONS', 'notifications');
}
header('Location: ' . clean_back_url($_POST['back'] ?? ''));
exit;
