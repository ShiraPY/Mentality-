<?php
// backup_download.php - sends one backup file to the browser. Backups are never served directly by Apache.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/backup.php';
require_role(BACKUP_ROLES);

$file = $_GET['file'] ?? '';
if (!preg_match('/^mentality_[0-9]{8}_[0-9]{6}\.sql$/', $file)) { http_response_code(400); die('Invalid file name.'); }
$path = BACKUP_DIR . '/' . $file;
if (!is_file($path)) { http_response_code(404); die('Backup not found.'); }

log_audit($pdo, (int)$_SESSION['user_id'], $_SESSION['username'], 'BACKUP_DOWNLOAD', 'database', null, 'Downloaded ' . $file);

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
