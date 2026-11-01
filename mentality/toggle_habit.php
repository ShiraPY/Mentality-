<?php
// toggle_habit.php - receives a tick/untick from the dashboard (JavaScript) and calls the stored procedure.
require_once __DIR__ . '/includes/auth.php';
header('Content-Type: application/json');

function fail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')                      { fail(405, 'POST only.'); }
if (!is_logged_in() || !account_is_active($pdo)) { fail(403, 'Not allowed. Please log in again.'); }
if (!csrf_check())                                              { fail(400, 'Session expired. Please refresh the page.'); }

$habitId   = (int)($_POST['habit_id'] ?? 0);
$date      = $_POST['date'] ?? '';
$completed = (($_POST['completed'] ?? '0') === '1') ? 1 : 0;

$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date || $habitId <= 0)      { fail(400, 'Invalid request.'); }

try {
    $stmt = $pdo->prepare('CALL sp_set_habit_log(?, ?, ?, ?)');
    $stmt->execute([(int)$_SESSION['user_id'], $habitId, $date, $completed]);
    echo json_encode(['ok' => true]);
} catch (PDOException $ex) {
    $state = $ex->errorInfo[0] ?? '';
    if ($state === '45000') { fail(422, $ex->errorInfo[2] ?? 'Not allowed.'); }   // our own rule messages
    fail(500, APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Could not save. Please try again.');
} catch (Throwable $ex) {
    fail(500, APP_DEBUG ? 'Server error: ' . $ex->getMessage() : 'Could not save. Please try again.');
}
