<?php
require_once __DIR__ . '/includes/layout.php';
require_login();


$uid    = (int)$_SESSION['user_id'];
$today  = new DateTimeImmutable('today');
$monday = new DateTimeImmutable('monday this week');
$days   = [];
for ($i = 0; $i < 7; $i++) { $days[] = $monday->modify("+$i day"); }
$daysElapsed = (int)$monday->diff($today)->days + 1;      // Monday..today, counting today

// Active habits of this user
$stmt = $pdo->prepare(
    'SELECT h.habit_id, h.name, c.name AS category_name
     FROM habits h JOIN habit_categories c ON c.category_id = h.category_id
     WHERE h.user_id = ? AND h.is_active = 1
     ORDER BY h.name'
);
$stmt->execute([$uid]);
$habits = $stmt->fetchAll();

// Completed ticks for this week
$stmt = $pdo->prepare(
    'SELECT l.habit_id, l.log_date
     FROM habit_logs l JOIN habits h ON h.habit_id = l.habit_id
     WHERE h.user_id = ? AND h.is_active = 1 AND l.completed = 1 AND l.log_date BETWEEN ? AND ?'
);
$stmt->execute([$uid, $monday->format('Y-m-d'), $days[6]->format('Y-m-d')]);
$done = [];
foreach ($stmt->fetchAll() as $row) { $done[$row['habit_id'] . '|' . $row['log_date']] = true; }

$total     = count($habits);
$todayStr  = $today->format('Y-m-d');
$doneToday = 0;
foreach ($habits as $h) { if (isset($done[$h['habit_id'] . '|' . $todayStr])) { $doneToday++; } }
$doneWeek  = count($done);
$weekPct   = ($total > 0) ? (int)round($doneWeek / ($total * $daysElapsed) * 100) : 0;

// Active (not completed) goals for the shortcut panel. Uses the same view as the Goals page.
$stmt = $pdo->prepare("SELECT goal_id, title, progress, days_left FROM v_goal_overview
                       WHERE user_id = ? AND status <> 'completed' ORDER BY target_date");
$stmt->execute([$uid]);
$allActiveGoals = $stmt->fetchAll();
$activeGoals    = array_slice($allActiveGoals, 0, 5);      // the panel shows the 5 closest to their target date

$hour     = (int)date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
$firstName = explode(' ', trim($_SESSION['full_name'] ?? $_SESSION['username']))[0];

render_header('Dashboard', 'dashboard');
?>
<h1><?= e($greeting) ?>, <?= e($firstName) ?>!</h1>
<p class="muted"><?= e($today->format('l, F j, Y')) ?> &middot; Small steps every day lead to big results.</p>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-label">Total Habits</div><div class="stat-value"><?= $total ?></div></div>
    <div class="stat-card"><div class="stat-label">Completion This Week</div><div class="stat-value" id="stat-week"><?= $weekPct ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Today's Progress</div><div class="stat-value" id="stat-today"><?= $doneToday ?> / <?= $total ?></div></div>
    <div class="stat-card"><div class="stat-label">Active Goals</div><div class="stat-value"><?= count($allActiveGoals) ?></div></div>
</div>

<div class="dash-grid">
<div class="panel">
    <div class="panel-head">
        <h2>My Habits</h2>
        <a class="btn btn-sm" href="habits.php">+ Add / Manage Habits</a>
    </div>

    <?php if ($total === 0): ?>
        <p class="muted">You have no active habits yet. <a href="habits.php">Add your first habit</a> to start tracking.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="grid" id="habit-grid"
           data-url="<?= BASE_URL ?>/toggle_habit.php" data-today="<?= e($todayStr) ?>"
           data-days="<?= $daysElapsed ?>" data-total="<?= $total ?>">
        <thead>
            <tr>
                <th class="left">Habit</th>
                <?php foreach ($days as $d): ?>
                    <th class="<?= $d->format('Y-m-d') === $todayStr ? 'today' : '' ?>"><?= e($d->format('D')) ?><br><small><?= e($d->format('j')) ?></small></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($habits as $h): ?>
            <tr>
                <td class="left"><?= e($h['name']) ?><br><small class="muted"><?= e($h['category_name']) ?></small></td>
                <?php foreach ($days as $d):
                    $ds     = $d->format('Y-m-d');
                    $future = ($ds > $todayStr); ?>
                    <td class="<?= $ds === $todayStr ? 'today' : '' ?>">
                        <input type="checkbox" class="habit-check"
                               data-habit="<?= (int)$h['habit_id'] ?>" data-date="<?= e($ds) ?>"
                               aria-label="<?= e($h['name'] . ' on ' . $ds) ?>"
                               <?= isset($done[$h['habit_id'] . '|' . $ds]) ? 'checked' : '' ?>
                               <?= $future ? 'disabled' : '' ?>>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Active Goals</h2>
        <a class="btn-ghost" href="goals.php">View all</a>
    </div>
    <?php if (!$activeGoals): ?>
        <p class="muted">No active goals yet. <a href="goals.php">Set a goal</a> and link your habits to it.</p>
    <?php else: foreach ($activeGoals as $g):
        $dl = (int)$g['days_left'];
        if ($dl < 0)      { $when = abs($dl) . ' day(s) overdue'; $cls = 'overdue'; $label = 'Overdue'; }
        elseif ($dl === 0){ $when = 'Due today';                  $cls = 'progress'; $label = 'In Progress'; }
        else              { $when = $dl . ' day(s) left';         $cls = 'progress'; $label = 'In Progress'; }
    ?>
        <a class="goal-mini" href="goals.php">
            <div class="goal-mini-top"><strong><?= e($g['title']) ?></strong><span class="status <?= $cls ?>"><?= $label ?></span></div>
            <div class="progress-row"><div class="progress"><div class="bar" style="width: <?= (int)$g['progress'] ?>%"></div></div><span><?= (int)$g['progress'] ?>%</span></div>
            <div class="muted small"><?= e($when) ?></div>
        </a>
    <?php endforeach; ?>
        <?php if (count($allActiveGoals) > count($activeGoals)): ?>
            <p class="muted small" style="margin:10px 0 0">+ <?= count($allActiveGoals) - count($activeGoals) ?> more on the <a href="goals.php">Goals tab</a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>
<?php render_footer(); ?>
