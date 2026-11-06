<?php
// includes/auth.php - sessions, CSRF protection, role checks, audit log helper.
// Every page that needs login does:  require_once __DIR__ . '/includes/auth.php';

define('BASE_URL', '/mentality');   // folder name inside htdocs
date_default_timezone_set('Asia/Manila');
define('APP_DEBUG', false);   // true = show technical error details while developing. Set to false before your defense.

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

// Escape text before printing it in HTML (stops XSS).
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ---- CSRF: a secret token inside every form so other sites can't submit it for you
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// ---- Login / role checks (enforced on the server, not just hidden menus)
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
// True if the logged-in account still exists and has not been suspended.
// It also refreshes the role from the database, so when an admin promotes or demotes someone the change
// applies on their very next click (they do not have to log out and in again).
function account_is_active(PDO $pdo): bool {
    if (!is_logged_in()) { return false; }
    $stmt = $pdo->prepare('SELECT u.status, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') { return false; }
    $_SESSION['role'] = $row['role_name'];
    return true;
}

function require_login(): void {
    global $pdo;
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (!account_is_active($pdo)) {          // suspended or deleted while logged in: sign out now
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('403 - You do not have permission to view this page.');
    }
}

// ---- Audit log: who did what, and when
function log_audit(PDO $pdo, ?int $userId, ?string $username, string $action,
                   string $table, ?int $recordId = null, ?string $details = null): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, username, action, table_name, record_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $username !== null ? substr($username, 0, 50) : null,
        $action, $table, $recordId, $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// ---- ONE password rule used everywhere (register, reset password, change password)
// Rule: at least 8 characters, with at least one letter and one number.
function validate_password(string $password, string $confirm): array {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password and Confirm Password do not match.';
    }
    return $errors;
}
