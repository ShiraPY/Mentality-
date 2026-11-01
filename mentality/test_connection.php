<?php
// test_connection.php - open http://localhost/mentality/test_connection.php
require_once __DIR__ . '/includes/db.php';

// A prepared statement: the ? is filled in separately from the SQL text.
$stmt = $pdo->prepare('SELECT role_id, role_name FROM roles WHERE role_id >= ?');
$stmt->execute([1]);
$roles = $stmt->fetchAll();

echo '<h2>Connected to mentality_db</h2><ul>';
foreach ($roles as $r) {
    echo '<li>' . (int)$r['role_id'] . ' - ' . htmlspecialchars($r['role_name']) . '</li>';
}
echo '</ul>';
