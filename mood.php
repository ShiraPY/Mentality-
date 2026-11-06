<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/crypto.php';
require_role(['user', 'moderator', 'admin']);
$uid = (int)$_SESSION['user_id'];

// mood name => [face, css class]
$moods = [
    'Great'      => ['', 'great'],
    'Good'       => ['', 'good'],
    'Okay'       => ['', 'okay'],
    'Low'        => ['', 'low'],
    'Frustrated' => ['', 'frustrated'],
    'Tired'      => ['', 'tired'],
];
$todayStr   = date('Y-m-d');
$errors     = [];     // today's form
$editErrors = [];     // Edit pop-up
$editForm   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if (!csrf_check()) {
        flash('error', 'Your session expired. Please try again.');
        redirect_to('mood.php');
    }

    // ---------- Save today's mood ----------
    if ($action === 'save') {
        $mood = $_POST['mood'] ?? '';
        $note = trim($_POST['note'] ?? '');
        if (!isset($moods[$mood]))   { $errors[] = 'Please select how you are feeling today.'; }
        if (mb_strlen($note) > 300)  { $errors[] = 'Your note must be 300 characters or less.'; }

        if (!$errors) {
            try {
                $encrypted = ($note !== '') ? encrypt_text($note) : null;            // encrypted BEFORE it reaches the database
                $pdo->prepare('CALL sp_save_mood(?, ?, ?, ?)')->execute([$uid, $mood, $encrypted, $todayStr]);
                log_audit($pdo, $uid, $_SESSION['username'], 'SAVE_MOOD', 'mood_logs', null, 'Mood saved: ' . $mood);   // the note is never written to the log
                flash('success', 'Mood saved!');
                redirect_to('mood.php');
            } catch (PDOException $ex) {
                $state = $ex->errorInfo[0] ?? '';
                $errors[] = ($state === '45000') ? ($ex->errorInfo[2] ?? 'Not allowed.')
                          : (APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Could not save your mood. Please try again.');
            }
        }
    }

    // ---------- Edit an earlier entry ----------
    if ($action === 'edit_mood') {
        $date = trim($_POST['date'] ?? '');
        $mood = $_POST['mood'] ?? '';
        $note = trim($_POST['note'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $editForm = ['date' => $date, 'label' => ($d ? $d->format('l, M j, Y') : $date), 'mood' => $mood, 'note' => $note];

        if (!$d || $d->format('Y-m-d') !== $date || $date > $todayStr) { $editErrors[] = 'Invalid date.'; }
        if (!isset($moods[$mood]))   { $editErrors[] = 'Please select a mood.'; }
        if (mb_strlen($note) > 300)  { $editErrors[] = 'Your note must be 300 characters or less.'; }

        if (!$editErrors) {                                   // the entry must already exist and belong to this user
            $stmt = $pdo->prepare('SELECT mood_id FROM mood_logs WHERE user_id = ? AND log_date = ?');
            $stmt->execute([$uid, $date]);
            if (!$stmt->fetch()) { $editErrors[] = 'That mood entry was not found.'; }
        }

        if (!$editErrors) {
            try {
                $encrypted = ($note !== '') ? encrypt_text($note) : null;
                $pdo->prepare('CALL sp_save_mood(?, ?, ?, ?)')->execute([$uid, $mood, $encrypted, $date]);
                log_audit($pdo, $uid, $_SESSION['username'], 'EDIT_MOOD', 'mood_logs', null, 'Mood edited for ' . $date . ': ' . $mood);
                flash('success', 'Mood entry updated.');
                redirect_to('mood.php');
            } catch (PDOException $ex) {
                $state = $ex->errorInfo[0] ?? '';
                $editErrors[] = ($state === '45000') ? ($ex->errorInfo[2] ?? 'Not allowed.')
                              : (APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Could not update the entry. Please try again.');
            }
        }
    }
}

// Today's entry (to pre-fill the form)
$stmt = $pdo->prepare('SELECT mood, mood_note FROM mood_logs WHERE user_id = ? AND log_date = ?');
$stmt->execute([$uid, $todayStr]);
$todayRow  = $stmt->fetch();
$posted    = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'save');
$selected  = $posted ? ($_POST['mood'] ?? '') : ($todayRow['mood'] ?? '');
$noteValue = $posted ? trim($_POST['note'] ?? '') : (string)decrypt_text($todayRow['mood_note'] ?? null);

// Recent mood log
$stmt = $pdo->prepare('SELECT log_date, mood, mood_note FROM mood_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 10');
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

render_header('Mood', 'mood');
?>
<div class="mood-page">
<h1>Mood Tracking</h1>

<div class="panel">
    <h2>How are you feeling today?</h2>
    <p class="muted small"><?= e(date('l, F j, Y')) ?><?= $todayRow ? ' &middot; You already logged today. Saving again will update it.' : '' ?></p>

    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="mood-options">
            <?php foreach ($moods as $name => [$face, $cls]): ?>
                <label class="mood-option">
                    <input type="radio" name="mood" value="<?= e($name) ?>" <?= $selected === $name ? 'checked' : '' ?>>
                    <span class="face"><?= mood_face($name, 46) ?></span>
                    <span class="mood-name"><?= e($name) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="inline-form">
            <label for="note">Private note (optional, stored encrypted)</label>
            <textarea id="note" name="note" rows="3" maxlength="300"><?= e($noteValue) ?></textarea>
            <div class="form-actions"><button type="submit" class="btn-sm">Save Mood</button></div>
        </div>
    </form>
</div>

<div class="panel">
    <h2>Recent Mood Log</h2>
    <?php if (!$recent): ?>
        <p class="muted">No moods logged yet. Pick how you feel above and save it.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="list">
        <thead><tr><th>Date</th><th>Mood</th><th>Note</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r):
            $note = decrypt_text($r['mood_note']);
            [$face, $cls] = $moods[$r['mood']] ?? ['', ''];
            $json = json_encode([
                'date'  => $r['log_date'],
                'label' => date('l, M j, Y', strtotime($r['log_date'])),
                'mood'  => $r['mood'],
                'note'  => (string)$note,
            ], JSON_UNESCAPED_UNICODE);
        ?>
            <tr>
                <td><?= e(date('M j, Y', strtotime($r['log_date']))) ?></td>
                <td><span class="mood-tag <?= e($cls) ?>"><?= mood_face($r['mood'], 18) ?> <?= e($r['mood']) ?></span></td>
                <td><?= $note !== null ? '<span class="diary-note">' . e($note) . '</span>' : '<span class="muted">—</span>' ?></td>
                <td class="row-actions"><button type="button" class="btn-ghost btn-edit row-edit" data-mood="<?= e($json) ?>"><?= icon('edit', 14) ?> Edit</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- ============ POP-UP: EDIT A MOOD ENTRY ============ -->
<dialog id="mood-edit-modal" class="modal mood-page" data-static
        <?= ($editErrors && $editForm) ? 'data-reopen="' . e(json_encode($editForm, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>>
  <div class="modal-body">
    <div class="modal-head">
        <h2>Edit Mood</h2>
        <button type="button" class="modal-x" data-close-modal aria-label="Close">&times;</button>
    </div>
    <p class="muted small" id="mood-edit-label"></p>
    <div id="mood-edit-errors">
    <?php if ($editErrors): ?>
        <div class="alert alert-error"><ul><?php foreach ($editErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    </div>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="edit_mood">
        <input type="hidden" name="date" id="mood-edit-date" value="">
        <div class="mood-options">
            <?php foreach ($moods as $name => [$face, $cls]): ?>
                <label class="mood-option">
                    <input type="radio" name="mood" value="<?= e($name) ?>">
                    <span class="face"><?= mood_face($name, 46) ?></span>
                    <span class="mood-name"><?= e($name) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <label for="mood-edit-note">Private note (optional, stored encrypted)</label>
        <textarea id="mood-edit-note" name="note" rows="3" maxlength="300"></textarea>
        <div class="form-actions">
            <button type="submit" class="btn-sm">Save Changes</button>
            <button type="button" class="btn-ghost" data-close-modal>Cancel</button>
        </div>
    </form>
  </div>
</dialog>
<?php render_footer(['mood.js']); ?>
