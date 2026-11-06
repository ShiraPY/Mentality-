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

// ---- Weekly consistency: the last 8 weeks (Monday to Sunday)
$weeksN     = 8;
$thisMonday = new DateTimeImmutable('monday this week');
$rangeStart = $thisMonday->modify('-' . ($weeksN - 1) . ' weeks');
$stmt = $pdo->prepare(
    'SELECT l.log_date, COUNT(*) AS c
     FROM habit_logs l JOIN habits h ON h.habit_id = l.habit_id
     WHERE h.user_id = ? AND h.is_active = 1 AND l.completed = 1 AND l.log_date BETWEEN ? AND ?
     GROUP BY l.log_date'
);
$stmt->execute([$uid, $rangeStart->format('Y-m-d'), $todayStr]);
$ticksByDay = [];
foreach ($stmt->fetchAll() as $r) { $ticksByDay[$r['log_date']] = (int)$r['c']; }

$weekly = [];
for ($i = 0; $i < $weeksN; $i++) {
    $ws    = $rangeStart->modify("+$i weeks");
    $wsStr = $ws->format('Y-m-d');
    $weEnd = min($ws->modify('+6 days')->format('Y-m-d'), $todayStr);
    $ticks = 0; $days = 0;
    for ($d = $ws; $d->format('Y-m-d') <= $weEnd; $d = $d->modify('+1 day')) { $ticks += $ticksByDay[$d->format('Y-m-d')] ?? 0; }
    foreach ($habitDates as $created) {                       // a habit only counts from the day it was created
        $from = max($wsStr, $created);
        if ($from <= $weEnd) { $days += (new DateTimeImmutable($from))->diff(new DateTimeImmutable($weEnd))->days + 1; }
    }
    $weekly[] = ['label' => $ws->format('M j'), 'has' => $days > 0, 'current' => ($i === $weeksN - 1),
                 'pct'   => $days > 0 ? min(100, (int)round($ticks / $days * 100)) : 0];
}
$weekNow  = $weekly[$weeksN - 1]['pct'];
$weekDiff = $weekNow - $weekly[$weeksN - 2]['pct'];
$withData = array_filter($weekly, fn($w) => $w['has']);
$weekAvg  = $withData ? (int)round(array_sum(array_column($withData, 'pct')) / count($withData)) : 0;

// geometry of the line chart (an inline SVG, so it needs no library and works offline)
$cw = 560; $ch = 230; $pl = 46; $pr = 34; $pt = 18; $pb = 44;
$plotW = $cw - $pl - $pr; $plotH = $ch - $pt - $pb; $baseY = $pt + $plotH;
$pts = [];
foreach ($weekly as $i => $w) { $pts[] = [round($pl + $i * $plotW / ($weeksN - 1), 1), round($pt + (100 - $w['pct']) / 100 * $plotH, 1)]; }
$linePath = '';
foreach ($pts as $i => [$x, $y]) { $linePath .= ($i ? ' L' : 'M') . $x . ' ' . $y; }
$areaPath = $linePath . ' L' . $pts[$weeksN - 1][0] . ' ' . $baseY . ' L' . $pts[0][0] . ' ' . $baseY . ' Z';

// ---- Focus area this week: which category needs the most attention? (GROUP BY ... HAVING)
$wStart = $thisMonday->format('Y-m-d');
$stmt = $pdo->prepare(
    'SELECT t.category_id, c.name AS category_name, p.name AS parent_name,
            COUNT(*) AS habits, SUM(t.days_tracked) AS days, SUM(t.done) AS done,
            ROUND(SUM(t.done) / SUM(t.days_tracked) * 100) AS pct
     FROM (
         SELECT h.habit_id, h.category_id,
                DATEDIFF(CAST(? AS DATE), GREATEST(CAST(? AS DATE), DATE(h.created_at))) + 1 AS days_tracked,
                (SELECT COUNT(*) FROM habit_logs l
                 WHERE l.habit_id = h.habit_id AND l.completed = 1
                   AND l.log_date BETWEEN GREATEST(CAST(? AS DATE), DATE(h.created_at)) AND CAST(? AS DATE)) AS done
         FROM habits h
         WHERE h.user_id = ? AND h.is_active = 1 AND DATE(h.created_at) <= CAST(? AS DATE)
     ) t
     JOIN habit_categories c ON c.category_id = t.category_id
     LEFT JOIN habit_categories p ON p.category_id = c.parent_id
     GROUP BY t.category_id, c.name, p.name
     HAVING SUM(t.days_tracked) > 0
     ORDER BY pct ASC, days DESC'
);
$stmt->execute([$todayStr, $wStart, $wStart, $todayStr, $uid, $todayStr]);
$areas = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT h.habit_id, h.name, h.category_id,
            DATEDIFF(CAST(? AS DATE), GREATEST(CAST(? AS DATE), DATE(h.created_at))) + 1 AS days_tracked,
            (SELECT COUNT(*) FROM habit_logs l
             WHERE l.habit_id = h.habit_id AND l.completed = 1
               AND l.log_date BETWEEN GREATEST(CAST(? AS DATE), DATE(h.created_at)) AND CAST(? AS DATE)) AS done,
            (SELECT COUNT(*) FROM habit_logs l2
             WHERE l2.habit_id = h.habit_id AND l2.completed = 1 AND l2.log_date = CAST(? AS DATE)) AS done_today
     FROM habits h
     WHERE h.user_id = ? AND h.is_active = 1 AND DATE(h.created_at) <= CAST(? AS DATE)'
);
$stmt->execute([$todayStr, $wStart, $wStart, $todayStr, $todayStr, $uid, $todayStr]);
$weekHabits = $stmt->fetchAll();

$areaLabel = fn($a) => $a['parent_name'] ? $a['parent_name'] . ' › ' . $a['category_name'] : $a['category_name'];
$focus = null; $strongest = null; $catchUp = [];
if ($areas && (int)$areas[0]['pct'] < 100) {
    $focus = $areas[0];
    $last  = $areas[count($areas) - 1];
    if (count($areas) > 1 && (int)$last['pct'] > (int)$focus['pct']) { $strongest = $last; }
    foreach ($weekHabits as $wh) {
        if ((int)$wh['category_id'] === (int)$focus['category_id'] && (int)$wh['done'] < (int)$wh['days_tracked']) {
            $wh['pct'] = (int)$wh['days_tracked'] > 0 ? (int)round($wh['done'] / $wh['days_tracked'] * 100) : 0;
            $catchUp[] = $wh;
        }
    }
    usort($catchUp, fn($a, $b) => ($a['pct'] <=> $b['pct']) ?: strcmp($a['name'], $b['name']));
    $catchUp = array_slice($catchUp, 0, 3);
}

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
    <div class="stack">
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
            <h2>Focus Area This Week</h2>
            <?php if (!$areas): ?>
                <p class="muted">No data for this week yet. Tick a few habits on your Dashboard first.</p>
            <?php elseif (!$focus): ?>
                <p class="muted"><?= icon('check', 18) ?> Perfect week so far: every area is at 100%. Keep it going!</p>
            <?php else: ?>
                <div class="focus-head">
                    <div class="focus-icon"><?= icon('goals', 24) ?></div>
                    <div><div class="muted small">Needs the most attention</div><div class="focus-name"><?= e($areaLabel($focus)) ?></div></div>
                    <div class="focus-pct"><?= (int)$focus['pct'] ?>%</div>
                </div>
                <div class="progress"><div class="bar" style="width: <?= (int)$focus['pct'] ?>%"></div></div>
                <p class="muted small" style="margin:8px 0 14px"><?= (int)$focus['done'] ?> of <?= (int)$focus['days'] ?> check-ins done since Monday, across <?= (int)$focus['habits'] ?> habit<?= (int)$focus['habits'] === 1 ? '' : 's' ?>.</p>

                <?php if ($catchUp): ?>
                    <div class="card-label">Habits to catch up on</div>
                    <?php foreach ($catchUp as $c): ?>
                        <a class="catch-row" href="dashboard.php">
                            <span><?= e($c['name']) ?></span>
                            <span class="muted small"><?= (int)$c['done'] ?>/<?= (int)$c['days_tracked'] ?> days<?= (int)$c['done_today'] ? ' &middot; done today' : ' &middot; not done today' ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($strongest): ?>
                    <p class="muted small" style="margin:14px 0 0"><?= icon('check', 14) ?> Going well: <strong><?= e($areaLabel($strongest)) ?></strong> (<?= (int)$strongest['pct'] ?>%)</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="stack">
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

        <div class="panel">
            <div class="panel-head"><h2>Weekly Consistency</h2><span class="big-pct"><?= $weekNow ?>%</span></div>
            <p class="muted small" style="margin:-6px 0 8px">
                <?= $weekDiff > 0 ? '+' . $weekDiff . '% vs last week' : ($weekDiff < 0 ? $weekDiff . '% vs last week' : 'Same as last week') ?>
                &middot; 8-week average: <?= $weekAvg ?>%
            </p>
            <svg class="wk-chart" viewBox="0 0 <?= $cw ?> <?= $ch ?>" role="img" aria-label="Weekly consistency over the last 8 weeks">
                <defs><linearGradient id="wkGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" style="stop-color:var(--primary);stop-opacity:.38"/><stop offset="1" style="stop-color:var(--primary);stop-opacity:0"/>
                </linearGradient></defs>
                <?php foreach ([0, 25, 50, 75, 100] as $g): $gy = $pt + (100 - $g) / 100 * $plotH; ?>
                    <line class="wk-grid" x1="<?= $pl ?>" x2="<?= $cw - $pr ?>" y1="<?= $gy ?>" y2="<?= $gy ?>"/>
                    <text class="wk-axis" x="<?= $pl - 8 ?>" y="<?= $gy + 4 ?>" text-anchor="end"><?= $g ?>%</text>
                <?php endforeach; ?>
                <path class="wk-area" d="<?= $areaPath ?>"/>
                <path class="wk-line" d="<?= $linePath ?>"/>
                <?php foreach ($weekly as $i => $w): [$x, $y] = $pts[$i]; ?>
                    <circle class="wk-dot<?= $w['current'] ? ' now' : '' ?>" cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $w['current'] ? 6 : 4 ?>"><title>Week of <?= e($w['label']) ?>: <?= (int)$w['pct'] ?>%</title></circle>
                    <text class="wk-axis<?= $w['current'] ? ' now' : '' ?>" x="<?= $x ?>" y="<?= $ch - 16 ?>" text-anchor="middle"><?= $w['current'] ? 'This week' : e($w['label']) ?></text>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>
</div>
<?php endif; ?>
<?php render_footer(); ?>
