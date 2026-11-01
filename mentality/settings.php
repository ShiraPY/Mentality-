<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/backup.php';
require_role(['user', 'moderator', 'admin']);
$uid = (int)$_SESSION['user_id'];

define('AVATAR_DIR', __DIR__ . '/uploads/avatars');

// Creates uploads/avatars and a .htaccess that stops any PHP file in it from running.
function ensure_upload_dir(): void {
    if (!is_dir(AVATAR_DIR)) { mkdir(AVATAR_DIR, 0755, true); }
    $ht = AVATAR_DIR . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|php[0-9])$\">\n    Require all denied\n</FilesMatch>\n");
    }
}

$pdo->prepare('INSERT IGNORE INTO user_settings (user_id) VALUES (?)')->execute([$uid]);

$tabs = ['profile' => 'Profile', 'security' => 'Security', 'appearance' => 'Appearance', 'notifications' => 'Notifications'];
if (can_backup()) { $tabs['backup'] = 'Backup'; }          // only admins and moderators see this tab
$tab  = $_GET['tab'] ?? 'profile';
if (!isset($tabs[$tab])) { $tab = 'profile'; }

try {
    $stmt = $pdo->prepare(
        'SELECT u.full_name, u.username, u.email, u.password_hash, u.profile_picture,
                s.appearance, s.notifications_on, s.bg_color, s.card_color, s.text_color, s.accent_color, s.font_choice
         FROM users u JOIN user_settings s ON s.user_id = u.user_id WHERE u.user_id = ?'
    );
    $stmt->execute([$uid]);
} catch (PDOException $ex) {
    die('Settings need the newest database changes. Import sql/08_features.sql in phpMyAdmin first.');
}
$user = $stmt->fetch();

$errors     = [];
$nameValue  = $user['full_name'];
$emailValue = $user['email'];

// Values shown in the Appearance form (saved values, or what the user just posted if there was an error)
$ap = [
    'theme'  => $user['appearance'],
    'accent' => (valid_hex($user['accent_color']) && isset(THEME_ACCENTS[strtolower($user['accent_color'])])) ? strtolower($user['accent_color']) : '',
    'custom' => valid_hex($user['bg_color']) && valid_hex($user['card_color']) && valid_hex($user['text_color']),
    'bg'     => valid_hex($user['bg_color'])   ? $user['bg_color']   : null,
    'card'   => valid_hex($user['card_color']) ? $user['card_color'] : null,
    'text'   => valid_hex($user['text_color']) ? $user['text_color'] : null,
    'font'   => isset(THEME_FONTS[$user['font_choice']]) ? $user['font_choice'] : 'modern',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!csrf_check()) {
        flash('error', 'Your session expired. Please try again.');
        redirect_to('settings.php?tab=' . $tab);
    }

    // =============== PROFILE: full name + picture ===============
    if ($action === 'profile') {
        $tab = 'profile';
        $nameValue = trim($_POST['full_name'] ?? '');
        $removePic = !empty($_POST['remove_picture']);
        $file = null; $ext = null;

        if ($nameValue === '' || mb_strlen($nameValue) > 100) { $errors[] = 'Full name is required (max 100 characters).'; }

        if (isset($_FILES['picture']) && $_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['picture'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'The picture could not be uploaded. Please try again.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'The picture must be 2 MB or smaller.';
            } else {
                $info    = @getimagesize($file['tmp_name']);              // checks the real file contents, not just the name
                $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
                if (!$info || !isset($allowed[$info[2]])) { $errors[] = 'The picture must be a JPG, PNG or WEBP image.'; }
                else { $ext = $allowed[$info[2]]; }
            }
        }

        if (!$errors) {
            $newName = null;
            try {
                $stored = $user['profile_picture'];
                if ($ext !== null) {
                    ensure_upload_dir();
                    $newName = 'u' . $uid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;     // random name chosen by us
                    if (!move_uploaded_file($file['tmp_name'], AVATAR_DIR . '/' . $newName)) { throw new RuntimeException('Could not save the picture.'); }
                    $stored = $newName;
                } elseif ($removePic) {
                    $stored = null;
                }

                $pdo->beginTransaction();
                $pdo->prepare('UPDATE users SET full_name = ?, profile_picture = ? WHERE user_id = ?')->execute([$nameValue, $stored, $uid]);
                log_audit($pdo, $uid, $_SESSION['username'], 'PROFILE_UPDATE', 'users', $uid, 'Profile updated');
                $pdo->commit();

                if ($user['profile_picture'] && $user['profile_picture'] !== $stored) { @unlink(AVATAR_DIR . '/' . basename($user['profile_picture'])); }
                $_SESSION['full_name'] = $nameValue;
                $_SESSION['avatar']    = $stored;
                flash('success', 'Profile updated.');
                redirect_to('settings.php?tab=profile');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if ($newName) { @unlink(AVATAR_DIR . '/' . $newName); }
                $errors[] = APP_DEBUG ? 'Error: ' . $ex->getMessage() : 'Could not update your profile. Please try again.';
            }
        }
    }

    // =============== SECURITY: email + password ===============
    if ($action === 'security') {
        $tab = 'security';
        $emailValue = trim($_POST['email'] ?? '');
        $current    = $_POST['current_password'] ?? '';
        $new        = $_POST['new_password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        if (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) { $errors[] = "Please enter a valid email address (it must contain '@' and a domain)."; }
        if ($current === '') { $errors[] = 'Enter your current password to save changes.'; }
        elseif (!password_verify($current, $user['password_hash'])) { $errors[] = 'Your current password is incorrect.'; }
        if ($new !== '' || $confirm !== '') { $errors = array_merge($errors, validate_password($new, $confirm)); }

        $changedEmail = strcasecmp($emailValue, $user['email']) !== 0;
        $changedPw    = $new !== '';

        if (!$errors && $changedEmail) {
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ?');
            $stmt->execute([$emailValue, $uid]);
            if ($stmt->fetch()) { $errors[] = 'That email is already used by another account.'; }
        }
        if (!$errors && !$changedEmail && !$changedPw) { $errors[] = 'Nothing to change. Enter a new email or a new password.'; }

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $hash = $changedPw ? password_hash($new, PASSWORD_BCRYPT) : $user['password_hash'];
                $pdo->prepare('UPDATE users SET email = ?, password_hash = ? WHERE user_id = ?')->execute([$emailValue, $hash, $uid]);
                if ($changedEmail) { log_audit($pdo, $uid, $_SESSION['username'], 'EMAIL_CHANGE', 'users', $uid, 'Email changed'); }
                if ($changedPw)    { log_audit($pdo, $uid, $_SESSION['username'], 'PASSWORD_CHANGE', 'users', $uid, 'Password changed in settings'); }
                $pdo->commit();

                if ($changedPw) { session_regenerate_id(true); }
                flash('success', 'Security settings saved.');
                redirect_to('settings.php?tab=security');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errors[] = ($ex->getCode() === '23000') ? 'That email is already used by another account.'
                          : (APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Could not save. Please try again.');
            }
        }
    }

    // =============== DELETE MY ACCOUNT ===============
    if ($action === 'delete_account') {
        $tab = 'security';
        if (empty($_POST['delete_confirm']))                                         { $errors[] = 'Tick the box to confirm that you want to delete your account.'; }
        if (!password_verify($_POST['delete_password'] ?? '', $user['password_hash'])) { $errors[] = 'Your password is incorrect.'; }

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                log_audit($pdo, $uid, $_SESSION['username'], 'ACCOUNT_DELETE', 'users', $uid, 'User deleted their own account');
                $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$uid]);    // habits, goals, moods... are removed by ON DELETE CASCADE
                $pdo->commit();

                if ($user['profile_picture']) { @unlink(AVATAR_DIR . '/' . basename($user['profile_picture'])); }
                $_SESSION = [];
                session_destroy();
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errors[] = APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Could not delete the account. Please try again.';
            }
        }
    }

    // =============== APPEARANCE ===============
    if ($action === 'appearance') {
        $tab = 'appearance';
        $ap['theme']  = $_POST['appearance'] ?? '';
        $ap['accent'] = strtolower(trim($_POST['accent_color'] ?? ''));
        $ap['font']   = $_POST['font_choice'] ?? 'modern';
        $ap['custom'] = !empty($_POST['custom_on']);
        $ap['bg'] = strtolower(trim($_POST['bg_color'] ?? ''));
        $ap['card'] = strtolower(trim($_POST['card_color'] ?? ''));
        $ap['text'] = strtolower(trim($_POST['text_color'] ?? ''));

        if (!in_array($ap['theme'], ['light', 'dark'], true))                        { $errors[] = 'Please choose Light or Dark.'; }
        if ($ap['accent'] !== '' && !isset(THEME_ACCENTS[$ap['accent']]))            { $errors[] = 'Please choose one of the accent colours.'; }
        if (!isset(THEME_FONTS[$ap['font']]))                                        { $errors[] = 'Please choose a font from the list.'; }
        if ($ap['custom']) {
            if (!valid_hex($ap['bg']) || !valid_hex($ap['card']) || !valid_hex($ap['text'])) {
                $errors[] = 'Please choose valid colours.';
            } else {
                if (contrast_ratio($ap['text'], $ap['bg']) < 4.5 || contrast_ratio($ap['text'], $ap['card']) < 4.5) {
                    $errors[] = 'The text colour is hard to read on those backgrounds. Choose colours that contrast more (for example light text on a dark background).';
                }
            }
        }

        if (!$errors) {
            $bg = $card = $text = null;
            if ($ap['custom']) { $bg = $ap['bg']; $card = $ap['card']; $text = $ap['text']; }
            $accentSave = ($ap['accent'] === '' || $ap['accent'] === DEFAULT_ACCENT) ? null : $ap['accent'];

            $pdo->prepare('UPDATE user_settings SET appearance = ?, bg_color = ?, card_color = ?, text_color = ?, accent_color = ?, font_choice = ? WHERE user_id = ?')
                ->execute([$ap['theme'], $bg, $card, $text, $accentSave, $ap['font'], $uid]);
            log_audit($pdo, $uid, $_SESSION['username'], 'SETTINGS_UPDATE', 'user_settings', $uid, 'Appearance changed');

            $_SESSION['theme'] = $ap['theme'];
            $_SESSION['ui'] = ['bg' => $bg, 'card' => $card, 'text' => $text, 'accent' => $accentSave, 'font' => $ap['font']];
            flash('success', 'Appearance saved.');
            redirect_to('settings.php?tab=appearance');
        }
    }

    if ($action === 'appearance_reset') {
        $pdo->prepare("UPDATE user_settings SET bg_color = NULL, card_color = NULL, text_color = NULL, accent_color = NULL, font_choice = 'modern' WHERE user_id = ?")->execute([$uid]);
        log_audit($pdo, $uid, $_SESSION['username'], 'SETTINGS_UPDATE', 'user_settings', $uid, 'Appearance reset to default');
        $_SESSION['ui'] = default_ui();
        flash('success', 'Colours and font were reset to the default.');
        redirect_to('settings.php?tab=appearance');
    }

    // =============== BACKUP (admins and moderators only) ===============
    if ($action === 'create_backup') {
        if (!can_backup()) {
            flash('error', 'You do not have permission to create backups.');
            redirect_to('settings.php?tab=profile');
        }
        try {
            $b = create_backup($pdo);
            log_audit($pdo, $uid, $_SESSION['username'], 'BACKUP_CREATE', 'database', null, 'Created ' . $b['name'] . ' (' . format_bytes($b['size']) . ')');
            flash('success', 'Backup created: ' . $b['name'] . ' (' . format_bytes($b['size']) . ')');
        } catch (Throwable $ex) {
            flash('error', APP_DEBUG ? 'Backup failed: ' . $ex->getMessage() : 'The backup could not be created.');
        }
        redirect_to('settings.php?tab=backup');
    }

    // =============== NOTIFICATIONS ===============
    if ($action === 'notifications') {
        $on = !empty($_POST['notifications_on']) ? 1 : 0;
        $pdo->prepare('UPDATE user_settings SET notifications_on = ? WHERE user_id = ?')->execute([$on, $uid]);
        log_audit($pdo, $uid, $_SESSION['username'], 'SETTINGS_UPDATE', 'user_settings', $uid, 'Notifications ' . ($on ? 'on' : 'off'));
        $_SESSION['notify'] = $on;
        flash('success', 'Notification settings saved.');
        redirect_to('settings.php?tab=notifications');
    }
}

// Colour inputs always need a value: use the theme's own colours when no custom colour is set
$themeForInputs = in_array($ap['theme'], ['light', 'dark'], true) ? $ap['theme'] : 'dark';
$inputDefaults  = theme_defaults($themeForInputs);
$inBg   = valid_hex($ap['bg'])   ? $ap['bg']   : $inputDefaults['bg'];
$inCard = valid_hex($ap['card']) ? $ap['card'] : $inputDefaults['card'];
$inText = valid_hex($ap['text']) ? $ap['text'] : $inputDefaults['text'];
$fontStacks = [];
foreach (THEME_FONTS as $k => [$label, $stack]) { $fontStacks[$k] = $stack; }

render_header('Settings', 'settings');
?>
<h1>Settings</h1>

<div class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab<?= $key === $tab ? ' active' : '' ?>" href="settings.php?tab=<?= e($key) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="panel" style="margin-top:16px">
<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($tab === 'profile'): ?>
    <h2>Profile Information</h2>
    <form method="post" enctype="multipart/form-data" class="inline-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">

        <div class="profile-head">
            <?= avatar_html($user['profile_picture'], $user['full_name'], 'lg') ?>
            <div><strong><?= e($user['full_name']) ?></strong><br><span class="muted small">@<?= e($user['username']) ?></span></div>
        </div>

        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($nameValue) ?>">

        <label for="picture">Profile picture (JPG, PNG or WEBP, max 2 MB)</label>
        <input type="file" id="picture" name="picture" accept="image/png,image/jpeg,image/webp">
        <?php if ($user['profile_picture']): ?>
            <label class="check-item" style="margin-top:8px"><input type="checkbox" name="remove_picture" value="1"> Remove my current picture</label>
        <?php endif; ?>

        <div class="form-actions"><button type="submit" class="btn-sm">Save Changes</button></div>
    </form>

    <div class="settings-section">
        <h2>Your data</h2>
        <p class="muted small">Download a copy of your information as a spreadsheet (CSV) file.</p>
        <div class="form-actions">
            <a class="btn-ghost" href="export_data.php?type=habits">Habit history</a>
            <a class="btn-ghost" href="export_data.php?type=moods">Moods</a>
            <a class="btn-ghost" href="export_data.php?type=goals">Goals</a>
        </div>
    </div>

<?php elseif ($tab === 'security'): ?>
    <h2>Security</h2>
    <p class="muted small">Change your email, your password, or both. You must enter your current password to save.</p>
    <form method="post" class="inline-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="security">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($emailValue) ?>">

        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password">

        <label for="new_password">New password (leave empty to keep the current one)</label>
        <input type="password" id="new_password" name="new_password" autocomplete="new-password">

        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">

        <div class="form-actions"><button type="submit" class="btn-sm">Save Changes</button></div>
    </form>

    <div class="settings-section">
        <h2>Delete my account</h2>
        <p class="muted small">This permanently deletes your account and everything in it: habits, goals, moods and notifications. It cannot be undone.</p>
        <form method="post" class="inline-form" novalidate onsubmit="return confirm('Delete your account permanently? This cannot be undone.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_account">
            <label for="delete_password">Your password</label>
            <input type="password" id="delete_password" name="delete_password" autocomplete="current-password">
            <label class="check-item" style="margin-top:10px"><input type="checkbox" name="delete_confirm" value="1"> I understand that this cannot be undone</label>
            <div class="form-actions"><button type="submit" class="btn-sm btn-danger">Delete My Account</button></div>
        </form>
    </div>

<?php elseif ($tab === 'appearance'): ?>
    <h2>Appearance</h2>
    <p class="muted small">The page changes instantly while you choose. Click <strong>Save Appearance</strong> to keep your choices.</p>

    <form method="post" id="appearance-form" novalidate
          data-fonts="<?= e(json_encode($fontStacks)) ?>"
          data-defaults="<?= e(json_encode(['dark' => theme_defaults('dark'), 'light' => theme_defaults('light')])) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="appearance">

        <div class="settings-section">
            <h3>Theme</h3>
            <div class="mood-options" style="max-width:360px">
                <label class="mood-option"><input type="radio" name="appearance" value="dark" <?= $ap['theme'] === 'dark' ? 'checked' : '' ?>>
                    <span class="face">🌙</span><span class="mood-name">Dark</span></label>
                <label class="mood-option"><input type="radio" name="appearance" value="light" <?= $ap['theme'] === 'light' ? 'checked' : '' ?>>
                    <span class="face">☀️</span><span class="mood-name">Light</span></label>
            </div>
        </div>

        <div class="settings-section">
            <h3>Accent colour</h3>
            <p class="muted small" style="margin:0">Used for buttons, tabs and highlights.</p>
            <div class="swatches">
                <?php foreach (THEME_ACCENTS as $hex => $label):
                    $value = ($hex === DEFAULT_ACCENT) ? '' : $hex; ?>
                    <label class="swatch" title="<?= e($label) ?>">
                        <input type="radio" name="accent_color" value="<?= e($value) ?>" <?= $ap['accent'] === $value ? 'checked' : '' ?>>
                        <span style="background: <?= e($hex) ?>"></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="settings-section">
            <h3>Your own colours</h3>
            <label class="check-item" style="font-size:1rem"><input type="checkbox" name="custom_on" value="1" <?= $ap['custom'] ? 'checked' : '' ?>> Use my own background, panel and text colours</label>
            <div class="color-row">
                <label>Background <input type="color" name="bg_color" value="<?= e($inBg) ?>"></label>
                <label>Panels and cards <input type="color" name="card_color" value="<?= e($inCard) ?>"></label>
                <label>Letters (text) <input type="color" name="text_color" value="<?= e($inText) ?>"></label>
            </div>
            <p class="muted small">If the letters are too hard to read on your background, the page will ask you to choose colours with more contrast.</p>
        </div>

        <div class="settings-section">
            <h3>Font</h3>
            <select name="font_choice" style="max-width:260px">
                <?php foreach (THEME_FONTS as $key => [$label, $stack]): ?>
                    <option value="<?= e($key) ?>" <?= $ap['font'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions"><button type="submit" class="btn-sm">Save Appearance</button></div>
    </form>

    <form method="post" class="settings-section" onsubmit="return confirm('Reset your colours and font to the default?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="appearance_reset">
        <button type="submit" class="btn-ghost">Reset colours and font to default</button>
    </form>

<?php elseif ($tab === 'backup'): $backups = backup_list(); ?>
    <h2>Database Backup</h2>
    <p class="muted small">A backup is a full copy of the database (every table with its data, plus the functions, procedures, views and triggers) saved as one <strong>.sql</strong> file. If data is lost or damaged, the file can be restored.</p>

    <div class="auto-note">
        <strong>Keep backups private.</strong> They contain every account's email and password hash.
        Mood notes are stored encrypted, but the key that unlocks them (<code>includes/mood.key</code>) is <strong>not</strong> in the backup file.
        Copy that key file somewhere safe too, or restored notes can no longer be read.
    </div>

    <form method="post" style="margin-top:14px" onsubmit="return confirm('Create a full backup of the database now?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_backup">
        <button type="submit" class="btn-sm">Create Backup Now</button>
    </form>

    <div class="settings-section">
        <h3>Saved backups</h3>
        <?php if (!$backups): ?>
            <p class="muted small">No backups yet. Click <strong>Create Backup Now</strong>.</p>
        <?php else: ?>
        <div class="table-wrap">
        <table class="list">
            <thead><tr><th>File</th><th>Created</th><th>Size</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($backups as $b): ?>
                <tr>
                    <td><?= e($b['name']) ?></td>
                    <td><?= e(date('M j, Y g:i A', $b['time'])) ?></td>
                    <td><?= e(format_bytes($b['size'])) ?></td>
                    <td class="actions"><a class="btn-ghost" href="backup_download.php?file=<?= urlencode($b['name']) ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="settings-section">
        <h3>How to restore a backup</h3>
        <p class="muted small" style="margin:0">Restoring replaces the current data, so it is done outside the website on purpose:</p>
        <ol class="muted small">
            <li>Double-click <code>tools\restore.bat</code>, type the backup file name, and confirm. <strong>Or</strong></li>
            <li>Open phpMyAdmin, click <code>mentality_db</code>, open the <strong>Import</strong> tab, choose the .sql file, and click Import.</li>
        </ol>
        <p class="muted small">Backups are saved in the <code>backups</code> folder of the project, next to the ones made by <code>tools\backup.bat</code>.</p>
    </div>

<?php else: ?>
    <h2>Notifications</h2>
    <p class="muted small">The bell at the top shows reminders when you haven't completed your habits, logged your mood, or when a goal is almost due.</p>
    <form method="post" class="inline-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="notifications">
        <label class="check-item" style="font-size:1rem">
            <input type="checkbox" name="notifications_on" value="1" <?= $user['notifications_on'] ? 'checked' : '' ?>>
            Turn on notifications and reminders
        </label>
        <div class="form-actions"><button type="submit" class="btn-sm">Save Notification Settings</button></div>
    </form>
<?php endif; ?>
</div>
<?php render_footer(['settings.js']); ?>