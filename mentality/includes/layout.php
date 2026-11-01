<?php
// includes/layout.php - the frame shared by every logged-in page:
//   top bar (notification bell + profile menu), left column (daily quote + live date/time),
//   the Dashboard tabs, and the logout confirmation pop-up.
// Usage in a page:  render_header('Title', 'habits');  ...page content...  render_footer();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/quotes.php';
require_once __DIR__ . '/theme.php';

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect_to(string $file): void {
    header('Location: ' . BASE_URL . '/' . $file);
    exit;
}

// Only allow "go back" addresses that stay inside this site.
function clean_back_url(string $url): string {
    if (preg_match('#^' . preg_quote(BASE_URL, '#') . '/[A-Za-z0-9_./?=&%-]*$#', $url)) { return $url; }
    return BASE_URL . '/dashboard.php';
}

// URL of a file in /assets with a version number, so the browser always loads the newest copy after you edit it.
function asset_url(string $file): string {
    return BASE_URL . '/assets/' . $file . '?v=' . (int)@filemtime(__DIR__ . '/../assets/' . $file);
}

// ---- Reminders for the bell: things the user has not done yet today
function get_reminders(PDO $pdo, int $uid): array {
    $today = date('Y-m-d');
    $out = [];

    // Habits not completed today
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM habits h
         WHERE h.user_id = ? AND h.is_active = 1 AND DATE(h.created_at) <= ?
           AND NOT EXISTS (SELECT 1 FROM habit_logs l WHERE l.habit_id = h.habit_id AND l.log_date = ? AND l.completed = 1)'
    );
    $stmt->execute([$uid, $today, $today]);
    $left = (int)$stmt->fetchColumn();
    if ($left > 0) {
        $out[] = ['icon' => '✅', 'url' => 'dashboard.php',
                  'text' => 'You still have ' . $left . ' habit' . ($left === 1 ? '' : 's') . ' to complete today.'];
    }

    // Mood not logged today
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mood_logs WHERE user_id = ? AND log_date = ?');
    $stmt->execute([$uid, $today]);
    if ((int)$stmt->fetchColumn() === 0) {
        $out[] = ['icon' => '🙂', 'url' => 'mood.php', 'text' => 'You haven\'t logged your mood today.'];
    }

    // Goals that are overdue or due within 3 days
    $stmt = $pdo->prepare(
        "SELECT title, DATEDIFF(target_date, ?) AS d FROM goals
         WHERE user_id = ? AND status <> 'completed' AND target_date <= DATE_ADD(?, INTERVAL 3 DAY)
         ORDER BY target_date LIMIT 5"
    );
    $stmt->execute([$today, $uid, $today]);
    foreach ($stmt->fetchAll() as $g) {
        $d = (int)$g['d'];
        if ($d < 0)       { $when = abs($d) . ' day' . (abs($d) === 1 ? '' : 's') . ' overdue'; }
        elseif ($d === 0) { $when = 'due today'; }
        else              { $when = 'due in ' . $d . ' day' . ($d === 1 ? '' : 's'); }
        $out[] = ['icon' => '🎯', 'url' => 'goals.php', 'text' => 'Goal "' . $g['title'] . '" is ' . $when . '.'];
    }
    return $out;
}

function render_header(string $title, string $active = ''): void {
    global $pdo;
    load_user_prefs();

    $role   = $_SESSION['role'] ?? 'user';
    $isUser = true;                                          // every role (user, moderator, admin) uses the normal website
    $canManage = in_array($role, ['admin', 'moderator'], true);   // admins and moderators also get Manage Users
    $theme  = (($_SESSION['theme'] ?? 'dark') === 'light') ? 'light' : 'dark';
    $ui     = $_SESSION['ui'] ?? default_ui();
    $name   = $_SESSION['full_name'] ?? $_SESSION['username'];
    $tabs   = $isUser ? [
        'dashboard' => ['Dashboard', 'dashboard.php'],
        'habits'    => ['My Habits', 'habits.php'],
        'goals'     => ['Goals',     'goals.php'],
        'mood'      => ['Mood',      'mood.php'],
        'stats'     => ['Stats',     'stats.php'],
    ] : [];

    $notifyOn = ((int)($_SESSION['notify'] ?? 1) === 1);
    $reminders = []; $stored = [];
    if ($isUser && $notifyOn) {
        $reminders = get_reminders($pdo, (int)$_SESSION['user_id']);
        $stmt = $pdo->prepare('SELECT message, created_at FROM notifications
                               WHERE user_id = ? AND is_cleared = 0 ORDER BY created_at DESC, notification_id DESC LIMIT 10');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $stored = $stmt->fetchAll();
    }
    $bellCount = count($reminders) + count($stored);
    $backUrl   = clean_back_url($_SERVER['REQUEST_URI'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> - Mentality</title>
    <link rel="stylesheet" href="<?= e(asset_url('style.css')) ?>">
    <style><?= custom_theme_css($ui) ?></style>
</head>
<body>
<header class="appbar">
    <div class="brand">MENTALITY</div>
    <div class="appbar-right">

        <?php if ($isUser): ?>
        <div class="dropdown" data-dropdown>
            <button type="button" class="bell-btn" data-dropdown-toggle aria-label="Notifications" aria-haspopup="true">
                &#128276;<?php if ($bellCount > 0): ?><span class="bell-badge"><?= $bellCount > 9 ? '9+' : $bellCount ?></span><?php endif; ?>
            </button>
            <div class="dropdown-menu wide">
                <div class="dd-head">Notifications</div>
                <?php if (!$notifyOn): ?>
                    <div class="dd-empty">Notifications are turned off. You can turn them on in Settings.</div>
                <?php elseif (!$reminders && !$stored): ?>
                    <div class="dd-empty">You're all caught up!</div>
                <?php else: ?>
                    <?php if ($reminders): ?>
                        <div class="dd-section">Reminders</div>
                        <?php foreach ($reminders as $r): ?>
                            <a class="dd-note" href="<?= BASE_URL . '/' . e($r['url']) ?>"><span><?= $r['icon'] ?></span><span><?= e($r['text']) ?></span></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($stored): ?>
                        <div class="dd-section">Recent</div>
                        <?php foreach ($stored as $n): ?>
                            <div class="dd-note"><span>&#128172;</span><span><?= e($n['message']) ?><br><small class="muted"><?= e(date('M j, g:i A', strtotime($n['created_at']))) ?></small></span></div>
                        <?php endforeach; ?>
                        <div class="dd-foot">
                            <form method="post" action="<?= BASE_URL ?>/clear_notifications.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                                <button type="submit" class="dd-item">Clear notifications</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="dropdown" data-dropdown>
            <button type="button" class="profile-btn" data-dropdown-toggle aria-haspopup="true">
                <?= avatar_html($_SESSION['avatar'] ?? null, $name) ?>
                <span class="profile-name"><?= e($name) ?></span><span class="caret">&#9662;</span>
            </button>
            <div class="dropdown-menu">
                <div class="dd-head"><?= e($name) ?><br><span class="badge"><?= e($role) ?></span></div>
                <?php if ($canManage): ?>
                    <a class="dd-item" href="<?= BASE_URL ?>/admin.php">&#128101; Manage Users</a>
                <?php endif; ?>
                <a class="dd-item" href="<?= BASE_URL ?>/settings.php">&#9881; Settings</a>
                <button type="button" class="dd-item" data-open-modal="logout-modal">&#10140; Log out</button>
            </div>
        </div>
    </div>
</header>

<div class="shell">
    <aside class="leftcol">
        <div class="quote-card">
            <div class="card-label">Daily Quote</div>
            <blockquote><?= e(daily_quote()) ?></blockquote>
        </div>
        <div class="today-card">
            <div class="card-label">Today</div>
            <div id="live-date" class="live-date"><?= e(date('l, F j, Y')) ?></div>
            <div id="live-time" class="live-time"><?= e(date('g:i:s A')) ?></div>
        </div>
    </aside>

    <main class="main">
    <?php if ($tabs): ?>
        <nav class="tabs dash-tabs">
            <?php foreach ($tabs as $key => [$label, $file]): ?>
                <a class="tab<?= $key === $active ? ' active' : '' ?>" href="<?= BASE_URL . '/' . $file ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <?php
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . ($f['type'] === 'success' ? 'success' : 'error') . '">' . e($f['message']) . '</div>';
    }
}

function render_footer(array $scripts = []): void {
    ?>
    </main>
</div>

<dialog id="logout-modal" class="modal modal-sm">
  <div class="modal-body center-text">
    <div class="logout-icon">&#128274;</div>
    <h2>Logout</h2>
    <p class="muted">Are you sure you want to logout?</p>
    <form method="post" action="<?= BASE_URL ?>/logout.php">
        <?= csrf_field() ?>
        <div class="form-actions center">
            <button type="button" class="btn-ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn-sm">Logout</button>
        </div>
    </form>
  </div>
</dialog>

<script src="<?= e(asset_url('app.js')) ?>"></script>
<?php foreach ($scripts as $sc): ?>
<script src="<?= e(asset_url($sc)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

// ---- Category helpers (used by pages that show the category dropdown)
// Returns [parents, children, selectable]
function get_category_tree(PDO $pdo): array {
    $cats = $pdo->query('SELECT category_id, name, parent_id FROM habit_categories ORDER BY name')->fetchAll();
    $parents = []; $children = []; $selectable = [];
    foreach ($cats as $c) {
        if ($c['parent_id'] === null) { $parents[(int)$c['category_id']] = $c['name']; }
        else { $children[(int)$c['parent_id']][] = $c; $selectable[(int)$c['category_id']] = true; }
    }
    foreach ($parents as $pid => $pname) { if (empty($children[$pid])) { $selectable[$pid] = true; } }
    asort($parents);
    return [$parents, $children, $selectable];
}

function category_options(array $parents, array $children, int $selected): string {
    $html = '<option value="0">Choose a category...</option>';
    foreach ($parents as $pid => $pname) {
        if (!empty($children[$pid])) {
            $html .= '<optgroup label="' . e($pname) . '">';
            foreach ($children[$pid] as $c) {
                $id = (int)$c['category_id'];
                $html .= '<option value="' . $id . '"' . ($selected === $id ? ' selected' : '') . '>' . e($c['name']) . '</option>';
            }
            $html .= '</optgroup>';
        } else {
            $html .= '<option value="' . $pid . '"' . ($selected === $pid ? ' selected' : '') . '>' . e($pname) . '</option>';
        }
    }
    return $html;
}

// ---- Per-user preferences (theme, colours, font, profile picture), loaded once per login session
function load_user_prefs(): void {
    global $pdo;
    if (isset($_SESSION['prefs_loaded']) || empty($_SESSION['user_id'])) { return; }
    try {
        $stmt = $pdo->prepare('SELECT u.profile_picture, s.appearance, s.notifications_on,
                                      s.bg_color, s.card_color, s.text_color, s.accent_color, s.font_choice
                               FROM users u LEFT JOIN user_settings s ON s.user_id = u.user_id WHERE u.user_id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
    } catch (PDOException $e) {            // the colour columns do not exist yet (08_features.sql not imported)
        $stmt = $pdo->prepare('SELECT u.profile_picture, s.appearance, s.notifications_on
                               FROM users u LEFT JOIN user_settings s ON s.user_id = u.user_id WHERE u.user_id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
    }
    $row = $stmt->fetch() ?: [];

    $_SESSION['theme']  = (($row['appearance'] ?? 'dark') === 'light') ? 'light' : 'dark';
    $_SESSION['avatar'] = $row['profile_picture'] ?? null;
    $_SESSION['notify'] = isset($row['notifications_on']) ? (int)$row['notifications_on'] : 1;

    $pick = function ($v) { return valid_hex($v) ? strtolower($v) : null; };
    $accent = $pick($row['accent_color'] ?? null);
    $_SESSION['ui'] = [
        'bg'     => $pick($row['bg_color'] ?? null),
        'card'   => $pick($row['card_color'] ?? null),
        'text'   => $pick($row['text_color'] ?? null),
        'accent' => ($accent && isset(THEME_ACCENTS[$accent])) ? $accent : null,
        'font'   => isset(THEME_FONTS[$row['font_choice'] ?? '']) ? $row['font_choice'] : 'modern',
    ];
    $_SESSION['prefs_loaded'] = true;
}

// A round profile picture, or a circle with the first letter of the name when there is no picture.
function avatar_html(?string $file, string $name, string $size = 'sm'): string {
    if ($file) {
        return '<img class="avatar ' . e($size) . '" src="' . BASE_URL . '/uploads/avatars/' . rawurlencode($file) . '" alt="Profile picture">';
    }
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
    return '<span class="avatar ' . e($size) . '">' . e($initial !== '' ? $initial : '?') . '</span>';
}
