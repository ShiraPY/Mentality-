<?php
require_once __DIR__ . '/includes/layout.php';
require_role(['user', 'moderator', 'admin']);
$uid = (int)$_SESSION['user_id'];

[$parents, $children, $selectable] = get_category_tree($pdo);

// The user's active habits (for the "supporting habits" checkboxes)
$stmt = $pdo->prepare('SELECT habit_id, name FROM habits WHERE user_id = ? AND is_active = 1 ORDER BY name');
$stmt->execute([$uid]);
$myHabits = $stmt->fetchAll();
$validHabit = [];
foreach ($myHabits as $h) { $validHabit[(int)$h['habit_id']] = true; }

$errors     = [];          // errors of the Add pop-up
$form       = ['title' => '', 'description' => '', 'category_id' => 0, 'target_date' => '', 'habit_ids' => []];
$editErrors = [];          // errors of the Edit pop-up
$editForm   = null;
$openModal  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'Your session expired. Please try again.');
        redirect_to('goals.php');
    }
    $action = $_POST['action'] ?? '';

    // ---------- Add a new goal ----------
    if ($action === 'add_goal') {
        $form['title']       = trim($_POST['title'] ?? '');
        $form['description'] = trim($_POST['description'] ?? '');
        $form['category_id'] = (int)($_POST['category_id'] ?? 0);
        $form['target_date'] = trim($_POST['target_date'] ?? '');
        $form['habit_ids']   = array_values(array_unique(array_map('intval', (array)($_POST['habit_ids'] ?? []))));

        if ($form['title'] === '' || mb_strlen($form['title']) > 100) { $errors[] = 'Goal title is required (max 100 characters).'; }
        if (mb_strlen($form['description']) > 500)                    { $errors[] = 'Description must be 500 characters or less.'; }
        if (empty($selectable[$form['category_id']]))                 { $errors[] = 'Please choose a category.'; }

        $d = DateTime::createFromFormat('Y-m-d', $form['target_date']);
        if (!$d || $d->format('Y-m-d') !== $form['target_date']) {
            $errors[] = 'Please choose a valid target date.';
        } elseif ($form['target_date'] < date('Y-m-d')) {
            $errors[] = 'Target date cannot be in the past.';
        }
        foreach ($form['habit_ids'] as $hid) {
            if (empty($validHabit[$hid])) { $errors[] = 'One of the selected habits is not valid.'; break; }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();            // goal + its habit links are saved together, or not at all
                $pdo->prepare('INSERT INTO goals (user_id, category_id, title, description, target_date) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$uid, $form['category_id'], $form['title'],
                               $form['description'] !== '' ? $form['description'] : null, $form['target_date']]);
                $goalId = (int)$pdo->lastInsertId();

                $link = $pdo->prepare('INSERT INTO goal_habits (goal_id, habit_id) VALUES (?, ?)');   // junction table
                foreach ($form['habit_ids'] as $hid) { $link->execute([$goalId, $hid]); }
                if ($form['habit_ids']) {            // progress comes from the linked habits
                    $pdo->prepare('UPDATE goals SET progress = fn_goal_auto_progress(goal_id, DATE(created_at), target_date) WHERE goal_id = ?')
                        ->execute([$goalId]);
                }

                log_audit($pdo, $uid, $_SESSION['username'], 'CREATE', 'goals', $goalId, 'Goal added: ' . $form['title']);
                $pdo->commit();

                flash('success', 'Goal added!');
                redirect_to('goals.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $state = $ex->errorInfo[0] ?? '';
                $errors[] = ($state === '45000')
                    ? ($ex->errorInfo[2] ?? 'Not allowed.')               // message written by our trigger
                    : 'Something went wrong while saving the goal. Please try again.';
            }
        }
    }

    // ---------- Edit a goal ----------
    if ($action === 'edit_goal') {
        $editId = (int)($_POST['goal_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT status, target_date FROM goals WHERE goal_id = ? AND user_id = ?');
        $stmt->execute([$editId, $uid]);
        $cur = $stmt->fetch();
        if (!$cur) {
            flash('error', 'Goal not found.');
            redirect_to('goals.php');
        }
        $isDone = ($cur['status'] === 'completed');

        $editForm = [
            'id'          => $editId,
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category'    => (int)($_POST['category_id'] ?? 0),
            'date'        => $isDone ? $cur['target_date'] : trim($_POST['target_date'] ?? ''),
            'habits'      => $isDone ? [] : array_values(array_unique(array_map('intval', (array)($_POST['habit_ids'] ?? [])))),
            'completed'   => $isDone,
        ];

        if ($editForm['title'] === '' || mb_strlen($editForm['title']) > 100) { $editErrors[] = 'Goal title is required (max 100 characters).'; }
        if (mb_strlen($editForm['description']) > 500)                        { $editErrors[] = 'Description must be 500 characters or less.'; }
        if (empty($selectable[$editForm['category']]))                        { $editErrors[] = 'Please choose a category.'; }

        if (!$isDone) {
            $d = DateTime::createFromFormat('Y-m-d', $editForm['date']);
            if (!$d || $d->format('Y-m-d') !== $editForm['date']) {
                $editErrors[] = 'Please choose a valid target date.';
            } elseif ($editForm['date'] !== $cur['target_date'] && $editForm['date'] < date('Y-m-d')) {
                $editErrors[] = 'Target date cannot be in the past.';       // keeping an old date unchanged is allowed
            }
            foreach ($editForm['habits'] as $hid) {
                if (empty($validHabit[$hid])) { $editErrors[] = 'One of the selected habits is not valid.'; break; }
            }
        }

        if (!$editErrors) {
            try {
                $pdo->beginTransaction();
                $desc = $editForm['description'] !== '' ? $editForm['description'] : null;

                if ($isDone) {
                    $pdo->prepare('UPDATE goals SET title = ?, description = ?, category_id = ? WHERE goal_id = ? AND user_id = ?')
                        ->execute([$editForm['title'], $desc, $editForm['category'], $editId, $uid]);
                } else {
                    $pdo->prepare('UPDATE goals SET title = ?, description = ?, category_id = ?, target_date = ? WHERE goal_id = ? AND user_id = ?')
                        ->execute([$editForm['title'], $desc, $editForm['category'], $editForm['date'], $editId, $uid]);

                    // Re-link the active habits (links to deactivated habits are left alone)
                    $pdo->prepare('DELETE gh FROM goal_habits gh JOIN habits h ON h.habit_id = gh.habit_id
                                   WHERE gh.goal_id = ? AND h.is_active = 1')->execute([$editId]);
                    $link = $pdo->prepare('INSERT IGNORE INTO goal_habits (goal_id, habit_id) VALUES (?, ?)');
                    foreach ($editForm['habits'] as $hid) { $link->execute([$editId, $hid]); }

                    // The date or the habits may have changed, so recalculate the progress
                    $pdo->prepare('UPDATE goals SET progress = fn_goal_auto_progress(goal_id, DATE(created_at), target_date)
                                   WHERE goal_id = ? AND status <> \'completed\'
                                     AND goal_id IN (SELECT goal_id FROM goal_habits WHERE goal_id = ?)')
                        ->execute([$editId, $editId]);
                }

                log_audit($pdo, $uid, $_SESSION['username'], 'UPDATE', 'goals', $editId, 'Goal edited: ' . $editForm['title']);
                $pdo->commit();

                flash('success', 'Goal updated.');
                redirect_to('goals.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $state = $ex->errorInfo[0] ?? '';
                $editErrors[] = ($state === '45000')
                    ? ($ex->errorInfo[2] ?? 'Not allowed.')
                    : (APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Something went wrong while saving the goal. Please try again.');
            }
        }
    }

    // ---------- Delete a goal ----------
    if ($action === 'delete_goal') {
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT title FROM goals WHERE goal_id = ? AND user_id = ?');
        $stmt->execute([$goalId, $uid]);
        $row = $stmt->fetch();
        if (!$row) {
            flash('error', 'Goal not found.');
        } else {
            try {
                $pdo->beginTransaction();
                // goal_habits rows are removed automatically (ON DELETE CASCADE); the habits themselves are NOT touched
                $pdo->prepare('DELETE FROM goals WHERE goal_id = ? AND user_id = ?')->execute([$goalId, $uid]);
                log_audit($pdo, $uid, $_SESSION['username'], 'DELETE', 'goals', $goalId, 'Goal deleted: ' . $row['title']);
                $pdo->commit();
                flash('success', 'Goal deleted. Your habits and their history were not affected.');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                flash('error', 'Could not delete the goal. Please try again.');
            }
        }
        redirect_to('goals.php');
    }

    // ---------- Mark a goal as completed ----------
    if ($action === 'complete') {
        $goalId = (int)($_POST['goal_id'] ?? 0);
        try {
            $pdo->prepare('CALL sp_update_goal_progress(?, ?, ?)')->execute([$uid, $goalId, 100]);
            log_audit($pdo, $uid, $_SESSION['username'], 'COMPLETE', 'goals', $goalId, 'Goal marked as completed');
            flash('success', 'Goal completed. Congratulations! Your linked habits stay in My Habits.');
        } catch (PDOException $ex) {
            $state = $ex->errorInfo[0] ?? '';
            flash('error', $state === '45000' ? ($ex->errorInfo[2] ?? 'Not allowed.') : 'Could not complete the goal.');
        }
        redirect_to('goals.php');
    }

    // ---------- Update progress (goals without linked habits) ----------
    if ($action === 'progress') {
        $goalId   = (int)($_POST['goal_id'] ?? 0);
        $progress = filter_var($_POST['progress'] ?? '', FILTER_VALIDATE_INT);

        if ($progress === false || $progress < 0 || $progress > 100) {
            flash('error', 'Progress must be a whole number from 0 to 100.');
        } else {
            try {
                $pdo->prepare('CALL sp_update_goal_progress(?, ?, ?)')->execute([$uid, $goalId, $progress]);
                log_audit($pdo, $uid, $_SESSION['username'], 'UPDATE', 'goals', $goalId, 'Progress set to ' . $progress . '%');
                flash('success', $progress === 100 ? 'Goal completed. Congratulations!' : 'Progress updated.');
            } catch (PDOException $ex) {
                $state = $ex->errorInfo[0] ?? '';
                flash('error', $state === '45000' ? ($ex->errorInfo[2] ?? 'Not allowed.') : 'Could not update progress.');
            }
        }
        redirect_to('goals.php');
    }
}

if ($errors)     { $openModal = 'add'; }
if ($editErrors) { $openModal = 'edit'; }

// ---- Load goals (uses VIEW #2, which uses FUNCTION #1)
$stmt = $pdo->prepare("SELECT * FROM v_goal_overview WHERE user_id = ? ORDER BY (status = 'completed'), target_date");
$stmt->execute([$uid]);
$goals = $stmt->fetchAll();

// Category id of each goal (the Edit pop-up needs it)
$stmt = $pdo->prepare('SELECT goal_id, category_id FROM goals WHERE user_id = ?');
$stmt->execute([$uid]);
$catByGoal = [];
foreach ($stmt->fetchAll() as $row) { $catByGoal[(int)$row['goal_id']] = (int)$row['category_id']; }

// Supporting habits per goal (through the junction table)
$stmt = $pdo->prepare(
    'SELECT gh.goal_id, gh.habit_id, h.name
     FROM goal_habits gh
     JOIN goals g  ON g.goal_id  = gh.goal_id
     JOIN habits h ON h.habit_id = gh.habit_id
     WHERE g.user_id = ? ORDER BY h.name'
);
$stmt->execute([$uid]);
$goalHabits = []; $goalHabitIds = [];
foreach ($stmt->fetchAll() as $row) {
    $goalHabits[(int)$row['goal_id']][]   = $row['name'];
    $goalHabitIds[(int)$row['goal_id']][] = (int)$row['habit_id'];
}

$countDone = 0;
foreach ($goals as $g) { if ($g['status'] === 'completed') { $countDone++; } }

render_header('Goals', 'goals');
?>
<div class="page-head">
    <div>
        <h1>My Goals</h1>
        <p class="muted">Set a target, track your progress, and link the habits that help you get there.</p>
    </div>
    <button type="button" class="btn-sm" data-open-modal="goal-add-modal">+ Add Goal</button>
</div>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-label">Total Goals</div><div class="stat-value"><?= count($goals) ?></div></div>
    <div class="stat-card"><div class="stat-label">In Progress</div><div class="stat-value"><?= count($goals) - $countDone ?></div></div>
    <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value"><?= $countDone ?></div></div>
</div>

<?php if (!$goals): ?>
    <div class="panel"><p class="muted">You have no goals yet. Click <strong>+ Add Goal</strong> to create your first one.</p></div>
<?php else: ?>
<div class="goal-grid">
    <?php foreach ($goals as $g):
        $gid      = (int)$g['goal_id'];
        $done     = $g['status'] === 'completed';
        $daysLeft = (int)$g['days_left'];
        $overdue  = !$done && $daysLeft < 0;
        if ($done)            { $statusLabel = 'Completed';   $statusClass = 'done'; }
        elseif ($overdue)     { $statusLabel = 'Overdue';     $statusClass = 'overdue'; }
        else                  { $statusLabel = 'In Progress'; $statusClass = 'progress'; }

        if ($done)                { $timeText = 'Goal reached'; }
        elseif ($daysLeft < 0)    { $timeText = abs($daysLeft) . ' day(s) overdue'; }
        elseif ($daysLeft === 0)  { $timeText = 'Due today'; }
        else                      { $timeText = $daysLeft . ' day(s) left'; }
        $category = $g['parent_category'] ? $g['parent_category'] . ' › ' . $g['category_name'] : (string)$g['category_name'];

        $goalJson = json_encode([
            'id'          => $gid,
            'title'       => $g['title'],
            'description' => (string)$g['description'],
            'category'    => $catByGoal[$gid] ?? 0,
            'date'        => $g['target_date'],
            'habits'      => $goalHabitIds[$gid] ?? [],
            'completed'   => $done,
        ], JSON_UNESCAPED_UNICODE);
    ?>
    <div class="goal-card">
        <div class="goal-top">
            <h3><?= e($g['title']) ?></h3>
            <div class="goal-actions">
                <span class="status <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                <button type="button" class="btn-ghost btn-edit" data-goal="<?= e($goalJson) ?>" title="Edit this goal">&#9998; Edit</button>
            </div>
        </div>
        <?php if ($category !== ''): ?><div class="muted small"><?= e($category) ?></div><?php endif; ?>
        <?php if ($g['description']): ?><p class="goal-desc"><?= e($g['description']) ?></p><?php endif; ?>

        <div class="progress-row">
            <div class="progress"><div class="bar" style="width: <?= (int)$g['progress'] ?>%"></div></div>
            <span><?= (int)$g['progress'] ?>%</span>
        </div>
        <div class="muted small">Target: <?= e(date('M j, Y', strtotime($g['target_date']))) ?> &middot; <?= e($timeText) ?></div>

        <?php if (!empty($goalHabits[$gid])): ?>
            <div class="chips">
                <?php foreach ($goalHabits[$gid] as $hn): ?><span class="chip"><?= e($hn) ?></span><?php endforeach; ?>
            </div>
            <?php if ($g['habit_rate_7d'] !== null): ?>
                <div class="muted small">Linked habits, last 7 days: <strong><?= e($g['habit_rate_7d']) ?>%</strong> done</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($done): ?>
            <?php if ((int)$g['habit_count'] > 0): ?>
                <div class="auto-note">Goal reached. Your linked habits are still in <a href="habits.php">My Habits</a>, with all your history kept.</div>
            <?php endif; ?>
        <?php elseif ((int)$g['habit_count'] > 0): ?>
            <div class="auto-note">Progress updates automatically each time you tick a linked habit on your Dashboard, up to the target date.</div>
            <form method="post" class="progress-form" onsubmit="return confirm('Mark this goal as completed now?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="goal_id" value="<?= $gid ?>">
                <button type="submit" class="btn-sm">Mark as completed</button>
            </form>
        <?php else: ?>
            <form method="post" class="progress-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="progress">
                <input type="hidden" name="goal_id" value="<?= $gid ?>">
                <label class="small muted" for="p<?= $gid ?>">Progress %</label>
                <input type="number" id="p<?= $gid ?>" name="progress" min="0" max="100" value="<?= (int)$g['progress'] ?>">
                <button type="submit" class="btn-sm">Update</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============ POP-UP: ADD GOAL ============ -->
<dialog id="goal-add-modal" class="modal" data-static data-reopen="<?= $openModal === 'add' ? '1' : '0' ?>">
  <div class="modal-body">
    <div class="modal-head">
        <h2>Add a New Goal</h2>
        <button type="button" class="modal-x" data-close-modal aria-label="Close">&times;</button>
    </div>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" novalidate onsubmit="return confirm('Add this goal?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_goal">

        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="100" value="<?= e($form['title']) ?>">

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description" rows="3" maxlength="500"><?= e($form['description']) ?></textarea>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id"><?= category_options($parents, $children, $form['category_id']) ?></select>

        <label for="target_date">Target date</label>
        <input type="date" id="target_date" name="target_date" min="<?= e(date('Y-m-d')) ?>" value="<?= e($form['target_date']) ?>">

        <label>Supporting habits (optional)</label>
        <p class="muted small" style="margin:0 0 6px">The linked habits drive this goal's progress: it fills up as you tick them each day until the target date.</p>
        <?php if (!$myHabits): ?>
            <p class="muted small">You have no active habits yet. <a href="habits.php">Add some</a> to link them to goals.</p>
        <?php else: ?>
            <div class="check-list">
            <?php foreach ($myHabits as $h): ?>
                <label class="check-item">
                    <input type="checkbox" name="habit_ids[]" value="<?= (int)$h['habit_id'] ?>"
                        <?= in_array((int)$h['habit_id'], $form['habit_ids'], true) ? 'checked' : '' ?>>
                    <?= e($h['name']) ?>
                </label>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn-sm">Add Goal</button>
            <button type="button" class="btn-ghost" data-close-modal>Cancel</button>
        </div>
    </form>
  </div>
</dialog>

<!-- ============ POP-UP: EDIT GOAL ============ -->
<dialog id="goal-edit-modal" class="modal" data-static
        <?= ($openModal === 'edit' && $editForm) ? 'data-reopen="' . e(json_encode($editForm, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>>
  <div class="modal-body">
    <div class="modal-head">
        <h2>Edit Goal</h2>
        <button type="button" class="modal-x" data-close-modal aria-label="Close">&times;</button>
    </div>
    <div id="edit-errors">
    <?php if ($editErrors): ?>
        <div class="alert alert-error"><ul><?php foreach ($editErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    </div>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="edit_goal">
        <input type="hidden" name="goal_id" id="edit-goal-id" value="">

        <label for="edit-title">Title</label>
        <input type="text" id="edit-title" name="title" maxlength="100">

        <label for="edit-description">Description</label>
        <textarea id="edit-description" name="description" rows="3" maxlength="500"></textarea>

        <label for="edit-category">Category</label>
        <select id="edit-category" name="category_id"><?= category_options($parents, $children, 0) ?></select>

        <div id="edit-extra">
            <label for="edit-date">Target date</label>
            <input type="date" id="edit-date" name="target_date">

            <label>Supporting habits</label>
            <?php if (!$myHabits): ?>
                <p class="muted small">You have no active habits.</p>
            <?php else: ?>
                <div class="check-list">
                <?php foreach ($myHabits as $h): ?>
                    <label class="check-item"><input type="checkbox" class="edit-habit" name="habit_ids[]" value="<?= (int)$h['habit_id'] ?>"> <?= e($h['name']) ?></label>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-sm">Save Changes</button>
            <button type="button" class="btn-ghost" data-close-modal>Cancel</button>
        </div>
    </form>

    <form method="post" class="danger-zone" onsubmit="return confirm('Delete this goal? This cannot be undone.\n\nYour linked habits and their history are NOT deleted.');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_goal">
        <input type="hidden" name="goal_id" id="delete-goal-id" value="">
        <span class="muted small">Don't need this goal anymore?</span>
        <button type="submit" class="btn-sm btn-danger">Delete Goal</button>
    </form>
  </div>
</dialog>
<?php render_footer(['goals.js']); ?>
