<?php
require_once __DIR__ . '/includes/layout.php';
require_role(['user', 'moderator', 'admin']);
$uid = (int)$_SESSION['user_id'];

// ---- Which tab? (Weekly / Monthly / Yearly)
$period = $_GET['period'] ?? 'week';
if (!in_array($period, ['week', 'month', 'year'], true)) { $period = 'week'; }

$today    = new DateTimeImmutable('today');
$todayStr = $today->format('Y-m-d');
$yesterdayStr = $today->modify('-1 day')->format('Y-m-d');

if ($period === 'month')      { $start = $today->modify('first day of this month'); $periodLabel = 'this month'; }
elseif ($period === 'year')   { $start = new DateTimeImmutable($today->format('Y') . '-01-01'); $periodLabel = 'this year'; }
else                          { $start = new DateTimeImmutable('monday this week'); $periodLabel = 'this week'; }
$startStr = $start->format('Y-m-d');

// ---- Habit performance for the period (uses a CTE: WITH ... AS)
// Each habit is only counted from the day it was created, so new habits are not punished for earlier days.
$stmt = $pdo->prepare(
    'WITH period AS (
         SELECT CAST(? AS DATE) AS p_start, CAST(? AS DATE) AS p_end
     )
     SELECT h.habit_id, h.name, c.name AS category_name,
            DATEDIFF(p.p_end, GREATEST(p.p_start, DATE(h.created_at))) + 1 AS days_tracked,
            COUNT(CASE WHEN l.completed = 1 THEN 1 END) AS done
     FROM period p
     JOIN habits h ON h.user_id = ? AND h.is_active = 1 AND DATE(h.created_at) <= p.p_end
     JOIN habit_categories c ON c.category_id = h.category_id
     LEFT JOIN habit_logs l ON l.habit_id = h.habit_id
          AND l.log_date BETWEEN GREATEST(p.p_start, DATE(h.created_at)) AND p.p_end
     GROUP BY h.habit_id, h.name, c.name, h.created_at, p.p_start, p.p_end
     ORDER BY h.name'
);
$stmt->execute([$startStr, $todayStr, $uid]);
$habits = $stmt->fetchAll();

$sumDone = 0; $sumDays = 0;
foreach ($habits as &$h) {
    $h['pct'] = $h['days_tracked'] > 0 ? min(100, (int)round($h['done'] / $h['days_tracked'] * 100)) : 0;
    $sumDone += (int)$h['done'];
    $sumDays += (int)$h['days_tracked'];
}
unset($h);
$overall = $sumDays > 0 ? min(100, (int)round($sumDone / $sumDays * 100)) : 0;

// ---- Missed days: days in the period (before today) with no completed habit at all
$stmt = $pdo->prepare('SELECT MIN(DATE(created_at)) FROM habits WHERE user_id = ?');
$stmt->execute([$uid]);
$firstHabitDate = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT DISTINCT l.log_date
     FROM habit_logs l JOIN habits h ON h.habit_id = l.habit_id
     WHERE h.user_id = ? AND l.completed = 1 AND l.log_date BETWEEN ? AND ?'
);
$stmt->execute([$uid, $startStr, $todayStr]);
$tickDays = [];
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $d) { $tickDays[$d] = true; }

$missed = 0;
if ($firstHabitDate) {
    $from = max($startStr, $firstHabitDate);
    for ($d = new DateTimeImmutable($from); $d->format('Y-m-d') <= $yesterdayStr; $d = $d->modify('+1 day')) {
        if (empty($tickDays[$d->format('Y-m-d')])) { $missed++; }
    }
}

// ---- Streaks (from the view that uses a window function)
$stmt = $pdo->prepare('SELECT start_date, end_date, streak_len FROM v_user_streaks WHERE user_id = ? ORDER BY end_date DESC');
$stmt->execute([$uid]);
$streaks = $stmt->fetchAll();
$bestStreak = 0; $currentStreak = 0;
foreach ($streaks as $s) { $bestStreak = max($bestStreak, (int)$s['streak_len']); }
if ($streaks && $streaks[0]['end_date'] >= $yesterdayStr) { $currentStreak = (int)$streaks[0]['streak_len']; }   // still alive if you ticked today or yesterday

// ---- Monthly consistency for the current year (12 bars)
$year = (int)$today->format('Y');
$stmt = $pdo->prepare(
    'SELECT MONTH(l.log_date) AS m, COUNT(*) AS ticks
     FROM habit_logs l JOIN habits h ON h.habit_id = l.habit_id
     WHERE h.user_id = ? AND h.is_active = 1 AND l.completed = 1 AND YEAR(l.log_date) = ?
     GROUP BY MONTH(l.log_date)'
);
$stmt->execute([$uid, $year]);
$ticksByMonth = [];
foreach ($stmt->fetchAll() as $r) { $ticksByMonth[(int)$r['m']] = (int)$r['ticks']; }

$stmt = $pdo->prepare('SELECT DATE(created_at) FROM habits WHERE user_id = ? AND is_active = 1');
$stmt->execute([$uid]);
$habitDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

$months = [];
for ($m = 1; $m <= 12; $m++) {
    $mStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $m));
    $mStartStr = $mStart->format('Y-m-d');
    $mEndStr = min($mStart->modify('last day of this month')->format('Y-m-d'), $todayStr);
    $days = 0;
    if ($mStartStr <= $todayStr) {
        foreach ($habitDates as $created) {
            $from = max($mStartStr, $created);
            if ($from <= $mEndStr) { $days += (new DateTimeImmutable($from))->diff(new DateTimeImmutable($mEndStr))->days + 1; }
        }
    }
    $pct = $days > 0 ? min(100, (int)round(($ticksByMonth[$m] ?? 0) / $days * 100)) : 0;
    $months[$m] = ['label' => $mStart->format('M'), 'pct' => $pct, 'future' => $mStartStr > $todayStr];
}
$thisMonthPct = $months[(int)$today->format('n')]['pct'];

render_header('Stats', 'stats');
?>
<h1>Statistics</h1>

<div class="tabs">
    <a class="tab<?= $period === 'week'  ? ' active' : '' ?>" href="stats.php?period=week">Weekly</a>
    <a class="tab<?= $period === 'month' ? ' active' : '' ?>" href="stats.php?period=month">Monthly</a>
    <a class="tab<?= $period === 'year'  ? ' active' : '' ?>" href="stats.php?period=year">Yearly</a>
</div>

<?php if (!$habits && !$firstHabitDate): ?>
    <div class="panel"><p class="muted">No data yet. <a href="habits.php">Add a habit</a> and start ticking it on your Dashboard to see your stats.</p></div>
<?php else: ?>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-label">Overall Completion (<?= e($periodLabel) ?>)</div><div class="stat-value"><?= $overall ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Current Streak</div><div class="stat-value"><?= $currentStreak ?> <small><?= $currentStreak === 1 ? 'day' : 'days' ?></small></div></div>
    <div class="stat-card"><div class="stat-label">Best Streak</div><div class="stat-value"><?= $bestStreak ?> <small><?= $bestStreak === 1 ? 'day' : 'days' ?></small></div></div>
    <div class="stat-card"><div class="stat-label">Missed Days (<?= e($periodLabel) ?>)</div><div class="stat-value"><?= $missed ?> <small><?= $missed === 1 ? 'day' : 'days' ?></small></div></div>
</div>

<div class="stats-layout">
    <div class="panel">
        <h2>Habit Performance</h2>
        <?php if (!$habits): ?>
            <p class="muted">No active habits in this period.</p>
        <?php else: foreach ($habits as $h): ?>
            <div class="perf-row">
                <div class="perf-name"><?= e($h['name']) ?><br><small class="muted"><?= e($h['category_name']) ?> &middot; <?= (int)$h['done'] ?> of <?= (int)$h['days_tracked'] ?> days</small></div>
                <div class="progress"><div class="bar" style="width: <?= (int)$h['pct'] ?>%"></div></div>
                <div class="perf-pct"><?= (int)$h['pct'] ?>%</div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Monthly Consistency (<?= $year ?>)</h2><span class="big-pct"><?= $thisMonthPct ?>%</span></div>
        <div class="bar-chart">
            <?php foreach ($months as $mo): ?>
                <div class="col<?= $mo['future'] ? ' future' : '' ?>" title="<?= e($mo['label']) ?>: <?= (int)$mo['pct'] ?>%">
                    <div class="col-track"><div class="col-fill" style="height: <?= (int)$mo['pct'] ?>%"></div></div>
                    <div class="col-label"><?= e($mo['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php render_footer(); ?>
