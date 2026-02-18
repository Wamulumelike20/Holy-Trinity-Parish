<?php
$pageTitle = 'Department Members';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$userRole = $_SESSION['user_role'] ?? '';
$deptId = intval($_GET['dept'] ?? 0);

if (!$deptId) {
    setFlash('error', 'Department not specified.');
    redirect('/holy-trinity/portal/dashboard.php');
}

$dept = $db->fetch("SELECT * FROM departments WHERE id = ? AND is_active = 1", [$deptId]);
if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('/holy-trinity/portal/dashboard.php');
}

$isHead = ($dept['head_user_id'] == $_SESSION['user_id']);
$canManage = $isHead || in_array($userRole, ['priest', 'super_admin', 'admin']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '') && $canManage) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'add_member') {
        $userId = intval($_POST['user_id'] ?? 0);
        $role = sanitize($_POST['member_role'] ?? 'member');
        if ($userId) {
            $existing = $db->fetch("SELECT id FROM department_members WHERE department_id = ? AND user_id = ?", [$deptId, $userId]);
            if (!$existing) {
                $db->insert('department_members', ['department_id' => $deptId, 'user_id' => $userId, 'role' => $role]);
                $user = $db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
                sendNotification(
                    'Added to Department',
                    "You have been added to {$dept['name']} as {$role}.",
                    'success',
                    '/holy-trinity/department/dashboard.php?dept=' . $deptId,
                    $userId
                );
                logAudit('department_member_added', 'department', $deptId);
                setFlash('success', 'Member added successfully.');
            } else {
                setFlash('info', 'User is already a member of this department.');
            }
        }
        redirect('/holy-trinity/department/members.php?dept=' . $deptId);
    } elseif ($action === 'remove_member') {
        $memberId = intval($_POST['member_id'] ?? 0);
        if ($memberId) {
            $member = $db->fetch("SELECT user_id FROM department_members WHERE id = ? AND department_id = ?", [$memberId, $deptId]);
            if ($member) {
                $db->delete('department_members', 'id = ?', [$memberId]);
                sendNotification(
                    'Removed from Department',
                    "You have been removed from {$dept['name']}.",
                    'warning',
                    null,
                    $member['user_id']
                );
                logAudit('department_member_removed', 'department', $deptId);
                setFlash('success', 'Member removed.');
            }
            redirect('/holy-trinity/department/members.php?dept=' . $deptId);
        }
    } elseif ($action === 'update_role') {
        $memberId = intval($_POST['member_id'] ?? 0);
        $newRole = sanitize($_POST['new_role'] ?? 'member');
        if ($memberId) {
            $db->update('department_members', ['role' => $newRole], 'id = ? AND department_id = ?', [$memberId, $deptId]);
            setFlash('success', 'Member role updated.');
            redirect('/holy-trinity/department/members.php?dept=' . $deptId);
        }
    }
}

$members = $db->fetchAll(
    "SELECT dm.*, u.first_name, u.last_name, u.email, u.phone, u.role as user_role
     FROM department_members dm INNER JOIN users u ON dm.user_id = u.id
     WHERE dm.department_id = ? ORDER BY dm.role DESC, u.first_name",
    [$deptId]
);

$availableUsers = $db->fetchAll(
    "SELECT u.id, u.first_name, u.last_name, u.email FROM users u
     WHERE u.is_active = 1 AND u.id NOT IN (SELECT user_id FROM department_members WHERE department_id = ?)
     ORDER BY u.first_name",
    [$deptId]
);

if (in_array($userRole, ['priest', 'super_admin', 'admin'])) {
    $allDepts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
} else {
    $allDepts = getUserDepartments($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title>Members - <?= sanitize($dept['name']) ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Dept</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Departments</div>
                <?php foreach ($allDepts as $d): ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>"><i class="fas fa-building"></i> <?= sanitize($d['name']) ?></a>
                <?php endforeach; ?>
                <div class="sidebar-section">Actions</div>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>" class="active"><i class="fas fa-users"></i> Members</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-user"></i> My Portal</a>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="font-family:var(--font-heading); color:var(--primary); margin-bottom:0.25rem;">
                        <i class="fas fa-users" style="color:var(--gold);"></i> <?= sanitize($dept['name']) ?> Members
                    </h1>
                    <p class="text-muted"><?= count($members) ?> members</p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $deptId ?>" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </div>

            <?php if ($canManage): ?>
            <!-- Add Member -->
            <div class="card mb-3">
                <div class="card-header"><h3><i class="fas fa-user-plus"></i> Add Member</h3></div>
                <div class="card-body">
                    <form method="POST" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="add_member">
                        <div class="form-group" style="margin-bottom:0; flex:2; min-width:200px;">
                            <label style="font-size:0.8rem;">Select User</label>
                            <select name="user_id" class="form-control" required>
                                <option value="">Choose a user...</option>
                                <?php foreach ($availableUsers as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?> (<?= sanitize($u['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:150px;">
                            <label style="font-size:0.8rem;">Role</label>
                            <select name="member_role" class="form-control">
                                <option value="member">Member</option>
                                <option value="secretary">Secretary</option>
                                <option value="treasurer">Treasurer</option>
                                <option value="coordinator">Coordinator</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Members List -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Dept Role</th>
                                <th>Joined</th>
                                <?php if ($canManage): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center p-3">No members</td></tr>
                            <?php else: ?>
                                <?php foreach ($members as $m): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($m['first_name'] . ' ' . $m['last_name']) ?></strong>
                                        <br><small class="badge badge-primary"><?= ucfirst(str_replace('_', ' ', $m['user_role'])) ?></small>
                                    </td>
                                    <td><?= sanitize($m['email']) ?></td>
                                    <td><?= sanitize($m['phone'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($canManage): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="update_role">
                                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                            <select name="new_role" class="form-control" style="width:auto; display:inline; font-size:0.85rem; padding:0.2rem 0.5rem;" onchange="this.form.submit()">
                                                <?php foreach (['member','secretary','treasurer','coordinator'] as $r): ?>
                                                    <option value="<?= $r ?>" <?= $m['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <?php else: ?>
                                            <span class="badge badge-gold"><?= ucfirst($m['role']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($m['joined_at']) ?></td>
                                    <?php if ($canManage): ?>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this member?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="remove_member">
                                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                            <button class="btn btn-sm btn-accent"><i class="fas fa-user-minus"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="/holy-trinity/assets/js/main.js"></script>

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
