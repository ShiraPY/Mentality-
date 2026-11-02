<?php
require_once __DIR__ . '/includes/layout.php';
require_role(['user', 'moderator', 'admin']);
$uid = (int)$_SESSION['user_id'];

[$parents, $children, $selectable] = get_category_tree($pdo);

$errors    = [];
$form      = ['name' => '', 'details' => '', 'category_id' => 0];
$editId    = 0;
$openModal = false;

// ---- Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $habitId = (int)($_POST['habit_id'] ?? 0);

    if (!csrf_check()) {
        flash('error', 'Your session expired. Please try again.');
        redirect_to('habits.php');
    }

    if ($action === 'add' || $action === 'update') {
        $form = [
            'name'        => trim($_POST['name'] ?? ''),
            'details'     => trim($_POST['details'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
        ];
        if ($form['name'] === '' || mb_strlen($form['name']) > 100) { $errors[] = 'Habit name is required (max 100 characters).'; }
        if (mb_strlen($form['details']) > 500)                      { $errors[] = 'Details must be 500 characters or less.'; }
        if (empty($selectable[$form['category_id']]))               { $errors[] = 'Please choose a category.'; }

        if (!$errors && $action === 'add') {
            $pdo->prepare('INSERT INTO habits (user_id, category_id, name, details) VALUES (?, ?, ?, ?)')
                ->execute([$uid, $form['category_id'], $form['name'], $form['details'] !== '' ? $form['details'] : null]);
            // (the trigger trg_habit_added also creates a notification automatically)
            log_audit($pdo, $uid, $_SESSION['username'], 'CREATE', 'habits', (int)$pdo->lastInsertId(), 'Habit added: ' . $form['name']);
            flash('success', 'Habit added!');
            redirect_to('habits.php');
        }

        if (!$errors && $action === 'update') {
            $own = $pdo->prepare('SELECT habit_id FROM habits WHERE habit_id = ? AND user_id = ?');
            $own->execute([$habitId, $uid]);
            if (!$own->fetch()) {
                flash('error', 'Habit not found.');
                redirect_to('habits.php');
            }
            $pdo->prepare('UPDATE habits SET name = ?, details = ?, category_id = ? WHERE habit_id = ? AND user_id = ?')
                ->execute([$form['name'], $form['details'] !== '' ? $form['details'] : null, $form['category_id'], $habitId, $uid]);
            log_audit($pdo, $uid, $_SESSION['username'], 'UPDATE', 'habits', $habitId, 'Habit edited: ' . $form['name']);
            flash('success', 'Habit updated.');
            redirect_to('habits.php');
        }

        if ($errors) {                       // show the pop-up again with the errors
            $openModal = true;
            $editId    = ($action === 'update') ? $habitId : 0;
        }
    }

    if ($action === 'deactivate' || $action === 'activate') {
        $own = $pdo->prepare('SELECT name FROM habits WHERE habit_id = ? AND user_id = ?');
        $own->execute([$habitId, $uid]);
        $row = $own->fetch();
        if (!$row) {
            flash('error', 'Habit not found.');
        } else {
            $active = ($action === 'activate') ? 1 : 0;
            $pdo->prepare('UPDATE habits SET is_active = ? WHERE habit_id = ? AND user_id = ?')->execute([$active, $habitId, $uid]);
            log_audit($pdo, $uid, $_SESSION['username'], strtoupper($action), 'habits', $habitId, ucfirst($action) . 'd habit: ' . $row['name']);
            flash('success', $active ? 'Habit reactivated.' : 'Habit deactivated. Its history is kept.');
        }
        redirect_to('habits.php');
    }
}

// ---- The list (uses the VIEW created in 02_habits.sql)
$stmt = $pdo->prepare('SELECT * FROM v_habit_overview WHERE user_id = ? ORDER BY is_active DESC, created_at DESC');
$stmt->execute([$uid]);
$habits = $stmt->fetchAll();

$reopenJson = $openModal
    ? json_encode(['id' => $editId, 'name' => $form['name'], 'details' => $form['details'], 'category' => $form['category_id']], JSON_UNESCAPED_UNICODE)
    : '';

render_header('My Habits', 'habits');
?>
<div class="page-head">
    <div>
        <h1>My Habits</h1>
        <p class="muted">Everything you track every day, in one place.</p>
    </div>
    <button type="button" class="btn-sm" data-habit-new>+ Add Habit</button>
</div>

<div class="panel">
    <h2>All Habits</h2>
    <?php if (!$habits): ?>
        <p class="muted">No habits yet. Click <strong>+ Add Habit</strong> to create your first one.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="list">
        <thead><tr><th>Habit</th><th>Category</th><th>Times done</th><th>Last done</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($habits as $h):
            $json = json_encode(['id' => (int)$h['habit_id'], 'name' => $h['name'], 'details' => (string)$h['details'], 'category' => (int)$h['category_id']], JSON_UNESCAPED_UNICODE);
        ?>
            <tr class="<?= $h['is_active'] ? '' : 'inactive' ?>">
                <td><?= e($h['name']) ?><?php if ($h['details']): ?><br><small class="muted"><?= e($h['details']) ?></small><?php endif; ?></td>
                <td><?= e($h['parent_category'] ? $h['parent_category'] . ' › ' . $h['category_name'] : $h['category_name']) ?></td>
                <td><?= (int)$h['total_completed'] ?></td>
                <td><?= $h['last_completed'] ? e(date('M j, Y', strtotime($h['last_completed']))) : '—' ?></td>
                <td><?= $h['is_active'] ? 'Active' : 'Inactive' ?></td>
                <td class="actions">
                    <button type="button" class="btn-ghost btn-edit" data-habit="<?= e($json) ?>"><?= icon('edit', 14) ?> Edit</button>
                    <form method="post" onsubmit="return confirm('<?= $h['is_active'] ? 'Deactivate this habit? Its history is kept.' : 'Reactivate this habit?' ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="habit_id" value="<?= (int)$h['habit_id'] ?>">
                        <input type="hidden" name="action" value="<?= $h['is_active'] ? 'deactivate' : 'activate' ?>">
                        <button type="submit" class="btn-sm <?= $h['is_active'] ? 'btn-danger' : '' ?>"><?= $h['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ============ POP-UP: ADD / EDIT HABIT ============ -->
<dialog id="habit-modal" class="modal" data-static <?= $openModal ? 'data-reopen="' . e($reopenJson) . '"' : '' ?>>
  <div class="modal-body">
    <div class="modal-head">
        <h2 id="habit-modal-title">Add a New Habit</h2>
        <button type="button" class="modal-x" data-close-modal aria-label="Close">&times;</button>
    </div>
    <div id="habit-errors">
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    </div>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="habit-action" value="add">
        <input type="hidden" name="habit_id" id="habit-id" value="0">

        <label for="habit-name">Habit name</label>
        <input type="text" id="habit-name" name="name" maxlength="100">

        <label for="habit-category">Category</label>
        <select id="habit-category" name="category_id"><?= category_options($parents, $children, 0) ?></select>

        <label for="habit-details">Details (optional)</label>
        <input type="text" id="habit-details" name="details" maxlength="500">

        <div class="form-actions">
            <button type="submit" class="btn-sm" id="habit-submit">Add Habit</button>
            <button type="button" class="btn-ghost" data-close-modal>Cancel</button>
        </div>
    </form>
  </div>
</dialog>
<?php render_footer(['habits.js']); ?>
