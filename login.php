<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (is_logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Please enter both your username/email and your password.';
    } else {
        // Prepared statement: user input is NEVER placed inside the SQL text.
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.username, u.full_name, u.password_hash, u.status, r.role_name
             FROM users u JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = ? OR u.email = ? LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {     // "Credentials valid?"
            if ($user['status'] === 'suspended') {                              // "Account suspended?"
                $error = 'This account has been suspended. Please contact an administrator.';
                log_audit($pdo, (int)$user['user_id'], $user['username'], 'LOGIN_BLOCKED', 'users', (int)$user['user_id'], 'Suspended account tried to log in');
            } else {
                session_regenerate_id(true);                                    // stops session fixation
                $_SESSION['user_id']  = (int)$user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']     = $user['role_name'];
                log_audit($pdo, (int)$user['user_id'], $user['username'], 'LOGIN', 'users', (int)$user['user_id']);
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password.';                           // same message for both cases
            log_audit($pdo, null, $login, 'LOGIN_FAILED', 'users', null, 'Wrong credentials');
        }
    }
}

?>

<?php
// ---- A different look every time the page loads: random colour palette, random background scene,
//      random blob positions, random quote, random sub-line.
$palettes = [
    ['Aurora', '#34e5c3', '#6d7bff', '#c455ff'],
    ['Sunset', '#ff9a62', '#ff4f8b', '#ffd166'],
    ['Ocean',  '#3cc4ff', '#3d6dff', '#58f0d2'],
    ['Forest', '#74e37a', '#1fbf9d', '#c8f261'],
    ['Orchid', '#c984ff', '#ff6fd8', '#6f8dff'],
    ['Ember',  '#ff7152', '#ffb23a', '#ff3f76'],
];
[$palName, $a1, $a2, $a3] = $palettes[array_rand($palettes)];
$scenes = ['aurora', 'stars', 'grid', 'rings'];
$scene  = $scenes[array_rand($scenes)];

$vars = "--a1:$a1;--a2:$a2;--a3:$a3;";
for ($i = 1; $i <= 3; $i++) {
    $vars .= "--b{$i}x:" . mt_rand(0, 100) . "%;--b{$i}y:" . mt_rand(0, 100) . "%;--b{$i}s:" . mt_rand(44, 68) . "vmax;";
}
$ringX = mt_rand(10, 90); $ringY = mt_rand(10, 90);

$hour     = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$subs = ['Pick up right where you left off.', 'Your habits are waiting for you.', "Let's make today count.",
         'Small steps, big changes.', 'Log in to keep your streak alive.', 'Ready when you are.'];
$sub = $subs[array_rand($subs)];

$quotes = all_quotes();
$first  = $quotes[array_rand($quotes)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - Mentality</title>
    <link rel="stylesheet" href="<?= e(BASE_URL . '/assets/style.css') ?>">
</head>
<body class="lg-page lg-scene-<?= e($scene) ?>" style="<?= e($vars) ?>">

<!-- ===== background: blobs + one random scene ===== -->
<div class="lg-stage" aria-hidden="true">
    <i class="lg-blob b1"></i><i class="lg-blob b2"></i><i class="lg-blob b3"></i>
    <?php if ($scene === 'stars'): ?>
        <svg class="lg-svg" width="100%" height="100%">
            <?php for ($i = 0; $i < 80; $i++): ?>
                <circle cx="<?= mt_rand(0, 100) ?>%" cy="<?= mt_rand(0, 100) ?>%" r="<?= mt_rand(5, 20) / 10 ?>" fill="#fff" opacity="<?= mt_rand(15, 70) / 100 ?>"/>
            <?php endfor; ?>
        </svg>
    <?php elseif ($scene === 'rings'): ?>
        <svg class="lg-svg" width="100%" height="100%">
            <?php for ($r = 70; $r <= 1400; $r += 90): ?>
                <circle cx="<?= $ringX ?>%" cy="<?= $ringY ?>%" r="<?= $r ?>" fill="none" stroke="#fff" stroke-opacity="<?= max(.03, .12 - $r / 14000) ?>"/>
            <?php endfor; ?>
        </svg>
    <?php elseif ($scene === 'grid'): ?>
        <div class="lg-grid"></div>
    <?php endif; ?>
    <div class="lg-vignette"></div>
</div>

<div class="lg-main">

    <!-- ===== LEFT: quotes ===== -->
    <section class="lg-left">
        <header class="lg-top"><span class="lg-wordmark">mentality</span></header>

        <div class="lg-hero">
            <p class="lg-kicker">A thought for today</p>
            <h1 class="lg-quote" id="login-quote" data-quotes="<?= e(json_encode($quotes, JSON_UNESCAPED_UNICODE)) ?>"><?= auth_quote_html($first) ?></h1>
            <div class="lg-chips">
                <span class="lg-chip"><?= icon('habits', 18) ?> Daily habits</span>
                <span class="lg-chip"><?= icon('goals', 18) ?> Goals</span>
                <span class="lg-chip"><?= icon('mood', 18) ?> Mood journal</span>
                <span class="lg-chip"><?= icon('stats', 18) ?> Stats</span>
            </div>
        </div>

        <footer class="lg-foot-left">
            <span class="lg-badge"><?= icon('database', 16) ?> IM2 &middot; Final Project</span>
            <span class="lg-built">Habit &amp; mood tracker &middot; PHP + MySQL</span>
        </footer>
    </section>

    <!-- ===== RIGHT: the login card ===== -->
    <section class="lg-right">
        <div class="lg-clock" id="lg-clock"><?= e(date('D, M j')) ?> &middot; <?= e(date('g:i A')) ?></div>

        <div class="lg-card">
            <span class="lg-hello"><?= e($greeting) ?></span>
            <h2 class="lg-title">Welcome back<span class="dot">.</span></h2>
            <p class="lg-sub"><?= e($sub) ?></p>

            <form method="post" novalidate>
                <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
                <?= csrf_field() ?>

                <div class="lg-field">
                    <input type="text" id="login" name="login" placeholder=" " value="<?= e($login) ?>" autocomplete="username" autofocus>
                    <label for="login">Email or username</label>
                    <?= icon('user', 18, 'lg-ico') ?>
                </div>

                <div class="lg-field">
                    <input type="password" id="password" name="password" placeholder=" " autocomplete="current-password">
                    <label for="password">Password</label>
                    <?= icon('lock', 18, 'lg-ico') ?>
                    <button type="button" class="toggle-pw" aria-label="Show password"><?= icon('eye', 20, 'i-on') ?><?= icon('eye-off', 20, 'i-off') ?></button>
                </div>

                <div class="lg-row">
                    <span class="lg-caps" id="lg-caps" hidden><?= icon('shield', 14) ?> Caps Lock is on</span>
                    <a class="lg-link" href="forgot_password.php">Forgot password?</a>
                </div>

                <button type="submit" class="lg-btn"><span>Log in</span><?= icon('arrow-right', 20) ?></button>
            </form>

            <div class="lg-or"><span>New here?</span></div>
            <a class="lg-create" href="register.php">Create an account</a>

            <p class="lg-secure"><?= icon('shield', 15) ?> Passwords are hashed &middot; Sessions are protected</p>
        </div>

        <div class="lg-copy">&copy; <?= e(date('Y')) ?> Mentality</div>
    </section>
</div>

<script src="<?= e(BASE_URL . '/assets/auth.js') ?>"></script>
</body>
</html>
