<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    if (is_logged_in()) {
        log_audit($pdo, (int)$_SESSION['user_id'], $_SESSION['username'], 'LOGOUT', 'users', (int)$_SESSION['user_id']);
    }
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
// Opening logout.php directly (GET) does nothing: logging out needs the confirmation pop-up.
header('Location: ' . BASE_URL . '/dashboard.php');
exit;
