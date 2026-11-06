<?php
// tests/injection_demo.php - BEFORE / AFTER SQL-injection demonstration (rubric IV-D).
// Open: http://localhost/mentality/tests/injection_demo.php
// SAFE TO RUN: it only reads the users table, shows usernames (never password hashes), and refuses
// to run unless the request comes from this computer. DELETE THIS FOLDER after your defense.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); die('Local use only.'); }
require_once __DIR__ . '/../includes/db.php';

// What an attacker would type into the "username" box of a login form:
$payloads = [
    "nobody' OR '1'='1",        // always-true condition  -> returns EVERY account
    "admin' -- ",               // comment trick          -> ignores the rest of the query
];

// BEFORE: the insecure way (user text pasted into the SQL string)
function vulnerable_lookup(PDO $pdo, string $login): array {
    $sql = "SELECT user_id, username FROM users WHERE username = '$login' AND status = 'active'";
    try { return ['sql' => $sql, 'rows' => $pdo->query($sql)->fetchAll()]; }
    catch (PDOException $e) { return ['sql' => $sql, 'rows' => [], 'error' => 'SQL error (query was broken by the input)']; }
}
// AFTER: the way Mentality really does it (login.php) - prepared statement, input is only ever DATA
function safe_lookup(PDO $pdo, string $login): array {
    $sql = 'SELECT user_id, username FROM users WHERE username = ? AND status = ?';
    $st = $pdo->prepare($sql);
    $st->execute([$login, 'active']);
    return ['sql' => $sql . "   -- ? = '" . $login . "'", 'rows' => $st->fetchAll()];
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function show(string $title, array $r, bool $bad): void {
    $n = count($r['rows']);
    echo '<div style="border:1px solid #ccd0d5;border-radius:10px;padding:14px;margin:10px 0;background:' . ($bad ? '#fdecea' : '#e8f7ee') . '">';
    echo '<strong>' . h($title) . '</strong><br><code>' . h($r['sql']) . '</code><br>';
    echo isset($r['error']) ? h($r['error']) : ('Rows returned: <strong>' . $n . '</strong>' . ($n ? ' &rarr; ' . h(implode(', ', array_column($r['rows'], 'username'))) : ''));
    echo '</div>';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>SQL injection demo - Mentality</title></head>
<body style="font-family:Segoe UI,Arial,sans-serif;max-width:900px;margin:30px auto;padding:0 16px">
<h1>SQL injection: before vs after</h1>
<p>Same malicious input, two ways of building the query. A secure login must return <strong>0 rows</strong> for these inputs.</p>
<?php foreach ($payloads as $p): ?>
    <h2>Input typed by attacker: <code><?= h($p) ?></code></h2>
    <?php show('BEFORE - string-concatenated query (VULNERABLE)', vulnerable_lookup($pdo, $p), true); ?>
    <?php show('AFTER - prepared statement (what Mentality uses)', safe_lookup($pdo, $p), false); ?>
<?php endforeach; ?>
<p><em>Take a screenshot of this page for your Security documentation (rubric IV-D).</em></p>
</body></html>
