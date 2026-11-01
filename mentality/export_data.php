<?php
// export_data.php - lets a user download their own data as a CSV file (opens in Excel).
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/crypto.php';
require_role(['user', 'moderator', 'admin']);
$uid  = (int)$_SESSION['user_id'];
$type = $_GET['type'] ?? 'habits';

// Stops "CSV injection": a cell that starts with = + - @ could run as a formula in Excel.
function csv_safe($v): string {
    $v = (string)$v;
    return ($v !== '' && strpos('=+-@', $v[0]) !== false) ? "'" . $v : $v;
}

if ($type === 'habits') {
    $header = ['Habit', 'Category', 'Date', 'Completed'];
    $stmt = $pdo->prepare('SELECT h.name, c.name AS category, l.log_date, l.completed
                           FROM habit_logs l JOIN habits h ON h.habit_id = l.habit_id
                           JOIN habit_categories c ON c.category_id = h.category_id
                           WHERE h.user_id = ? ORDER BY l.log_date DESC, h.name');
    $stmt->execute([$uid]);
    $rows = array_map(fn($r) => [$r['name'], $r['category'], $r['log_date'], $r['completed'] ? 'Yes' : 'No'], $stmt->fetchAll());
} elseif ($type === 'moods') {
    $header = ['Date', 'Mood', 'Note'];
    $stmt = $pdo->prepare('SELECT log_date, mood, mood_note FROM mood_logs WHERE user_id = ? ORDER BY log_date DESC');
    $stmt->execute([$uid]);
    $rows = array_map(fn($r) => [$r['log_date'], $r['mood'], (string)decrypt_text($r['mood_note'])], $stmt->fetchAll());
} elseif ($type === 'goals') {
    $header = ['Goal', 'Category', 'Target date', 'Progress %', 'Status'];
    $stmt = $pdo->prepare('SELECT title, category_name, target_date, progress, status FROM v_goal_overview WHERE user_id = ? ORDER BY target_date');
    $stmt->execute([$uid]);
    $rows = array_map(fn($r) => [$r['title'], (string)$r['category_name'], $r['target_date'], $r['progress'], $r['status']], $stmt->fetchAll());
} else {
    http_response_code(400);
    die('Unknown export type.');
}

log_audit($pdo, $uid, $_SESSION['username'], 'EXPORT', 'users', $uid, 'Exported ' . $type);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mentality_' . $type . '_' . date('Ymd') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");                         // so Excel reads accents/emoji correctly
fputcsv($out, $header);
foreach ($rows as $r) { fputcsv($out, array_map('csv_safe', $r)); }
fclose($out);
