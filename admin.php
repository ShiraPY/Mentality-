<?php
require_once __DIR__ . '/includes/layout.php';
require_role(['admin', 'moderator']);

$actorId = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

// ---------- Actions: suspend / reactivate / delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'Your session expired. Please try again.');
        redirect_to('admin.php');
    }
    $action   = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT u.username, u.profile_picture, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ?');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('error', 'User not found.');
    } elseif ($action === 'suspend' || $action === 'reactivate') {
        try {
            // The permission rules are enforced INSIDE the database procedure
            $pdo->prepare('CALL sp_set_user_status(?, ?, ?)')->execute([$actorId, $targetId, $action === 'suspend' ? 'suspended' : 'active']);
            log_audit($pdo, $actorId, $_SESSION['username'], strtoupper($action), 'users', $targetId,
                      ($action === 'suspend' ? 'Suspended' : 'Reactivated') . ' account: ' . $target['username']);
            flash('success', 'Account ' . ($action === 'suspend' ? 'suspended' : 'reactivated') . ': ' . $target['username']);
        } catch (PDOException $ex) {
            $state = $ex->errorInfo[0] ?? '';
            flash('error', $state === '45000' ? ($ex->errorInfo[2] ?? 'Not allowed.') : 'Could not change the account status.');
        }
    } elseif ($action === 'set_role') {
        $newRole = $_POST['role'] ?? '';
        try {
            // The role rules are enforced INSIDE the database procedure
            $pdo->prepare('CALL sp_set_user_role(?, ?, ?)')->execute([$actorId, $targetId, $newRole]);
            log_audit($pdo, $actorId, $_SESSION['username'], 'ROLE_CHANGE', 'users', $targetId,
                      'Role of ' . $target['username'] . ': ' . $target['role_name'] . ' -> ' . $newRole);
            flash('success', '@' . $target['username'] . ' is now a ' . $newRole . '.');
        } catch (PDOException $ex) {
            $state = $ex->errorInfo[0] ?? '';
            flash('error', $state === '45000' ? ($ex->errorInfo[2] ?? 'Not allowed.') : 'Could not change the role.');
        }
    } elseif ($action === 'delete') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('CALL sp_delete_user(?, ?)')->execute([$actorId, $targetId]);
            log_audit($pdo, $actorId, $_SESSION['username'], 'DELETE_USER', 'users', $targetId, 'Deleted account: ' . $target['username']);
            $pdo->commit();
            if ($target['profile_picture']) { @unlink(__DIR__ . '/uploads/avatars/' . basename($target['profile_picture'])); }
            flash('success', 'Account deleted: ' . $target['username']);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $state = $ex->errorInfo[0] ?? '';
            flash('error', $state === '45000' ? ($ex->errorInfo[2] ?? 'Not allowed.') : 'Could not delete the account.');
        }
    }
    redirect_to('admin.php');
}

// ---------- Search / filter ----------
$q      = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';
if (!in_array($filter, ['active', 'suspended'], true)) { $filter = ''; }

$sql = 'SELECT u.user_id, u.full_name, u.username, u.email, u.status, u.created_at, u.profile_picture, r.role_name
        FROM users u JOIN roles r ON r.role_id = u.role_id WHERE 1 = 1';
$params = [];
if ($q !== '') {
    if ($isAdmin) { $sql .= ' AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)'; $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%']; }
    else          { $sql .= ' AND (u.username LIKE ? OR u.full_name LIKE ?)';                    $params = ['%' . $q . '%', '%' . $q . '%']; }   // moderators cannot search by email
}
if ($filter !== '') { $sql .= ' AND u.status = ?'; $params[] = $filter; }
$sql .= ' ORDER BY r.role_id, u.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// ---------- Numbers for the cards ----------
$counts = ['total' => 0, 'active' => 0, 'suspended' => 0];
$stmt = $pdo->query("SELECT status, COUNT(*) c FROM users WHERE role_id = 3 GROUP BY status");
foreach ($stmt->fetchAll() as $r) { $counts[$r['status']] = (int)$r['c']; $counts['total'] += (int)$r['c']; }

// ---------- Activity log (administrators only) ----------
$logs = [];
if ($isAdmin) {
    $logs = $pdo->query('SELECT created_at, username, action, table_name, details, ip_address FROM audit_logs ORDER BY audit_id DESC LIMIT 25')->fetchAll();
}

render_header('Manage Users', 'admin');
?>
<p class="small" style="margin:0 0 6px"><a href="dashboard.php">&larr; Back to Dashboard</a></p>
<h1>Manage Users</h1>
<p class="muted">
    You are logged in as <strong><?= e($_SESSION['role']) ?></strong>.
    <?= $isAdmin
        ? 'You can suspend, reactivate and delete accounts, and change anyone\'s role between user and moderator.'
        : 'You can suspend and reactivate regular users and promote a user to moderator. You cannot change another moderator\'s role, and only administrators can delete accounts.' ?>
</p>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-label">Users</div><div class="stat-value"><?= $counts['total'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Active</div><div class="stat-value"><?= $counts['active'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Suspended</div><div class="stat-value"><?= $counts['suspended'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Moderators</div><div class="stat-value"><?= (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role_id = 2')->fetchColumn() ?></div></div>
</div>

<div class="panel">
    <h2>Accounts</h2>
    <form method="get" class="search-row">
        <input type="text" name="q" placeholder="<?= $isAdmin ? 'Search name, username or email' : 'Search name or username' ?>" value="<?= e($q) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
        <button type="submit" class="btn-sm">Search</button>
    </form>

    <?php if (!$users): ?>
        <p class="muted">No accounts match your search.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="list">
        <thead><tr><th>Account</th><?php if ($isAdmin): ?><th>Email</th><?php endif; ?><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $isSelf    = ((int)$u['user_id'] === $actorId);
            $canManage = !$isSelf && ($u['role_name'] === 'user' || ($isAdmin && $u['role_name'] === 'moderator'));
            $canPromote = !$isSelf && $u['role_name'] === 'user';                    // admins and moderators can promote a user to moderator
            $canDemote  = !$isSelf && $isAdmin && $u['role_name'] === 'moderator';  // only admins can turn a moderator back into a user
            $toRole     = $canPromote ? 'moderator' : ($canDemote ? 'user' : '');
        ?>
            <tr class="<?= $u['status'] === 'suspended' ? 'inactive' : '' ?>">
                <td><div class="user-cell"><?= avatar_html($u['profile_picture'], $u['full_name']) ?>
                    <div><?= e($u['full_name']) ?><br><small class="muted">@<?= e($u['username']) ?><?= $isSelf ? ' (you)' : '' ?></small></div></div></td>
                <?php if ($isAdmin): ?><td><?= e($u['email']) ?></td><?php endif; ?>
                <td>
                    <?= e($u['role_name']) ?>
                    <?php if ($toRole !== ''): ?>
                    <form method="post" class="role-form" onsubmit="return confirm('Change @<?= e($u['username']) ?> from <?= e($u['role_name']) ?> to <?= e($toRole) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <input type="hidden" name="action" value="set_role">
                        <input type="hidden" name="role" value="<?= e($toRole) ?>">
                        <button type="submit" class="btn-ghost btn-xs">Make <?= e($toRole) ?></button>
                    </form>
                    <?php endif; ?>
                </td>
                <td><span class="pill <?= e($u['status']) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                <td class="actions">
                <?php if (!$canManage): ?>
                    <span class="muted small">—</span>
                <?php else: ?>
                    <form method="post" onsubmit="return confirm('<?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?> the account @<?= e($u['username']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <input type="hidden" name="action" value="<?= $u['status'] === 'active' ? 'suspend' : 'reactivate' ?>">
                        <button type="submit" class="btn-sm <?= $u['status'] === 'active' ? '' : 'btn-ghost' ?>"><?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
                    </form>
                    <?php if ($isAdmin): ?>
                    <form method="post" onsubmit="return confirm('DELETE the account @<?= e($u['username']) ?> and ALL of its data? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn-sm btn-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class="panel">
    <h2>Recent Activity</h2>
    <p class="muted small">The last 25 entries of the audit log: who did what, and when.</p>
    <div class="table-wrap">
    <table class="list log-table">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Table</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?= e(date('M j, g:i:s A', strtotime($l['created_at']))) ?></td>
                <td><?= e($l['username'] ?? '—') ?></td>
                <td><?= e($l['action']) ?></td>
                <td><?= e($l['table_name']) ?></td>
                <td><?= e($l['details'] ?? '') ?></td>
                <td><?= e($l['ip_address'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
<?php render_footer(); ?>
