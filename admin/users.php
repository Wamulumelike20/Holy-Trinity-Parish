<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = intval($_POST['user_id'] ?? 0);
        $action = sanitize($_POST['action']);

        if ($action === 'update_role' && $userId) {
            $newRole = sanitize($_POST['new_role'] ?? '');
            if (in_array($newRole, ['parishioner','priest','department_head','admin'])) {
                $db->update('users', ['role' => $newRole], 'id = ?', [$userId]);
                logAudit('user_role_updated', 'user', $userId, null, json_encode(['role' => $newRole]));
                setFlash('success', 'User role updated successfully.');
            }
        } elseif ($action === 'toggle_status' && $userId) {
            $user = $db->fetch("SELECT is_active FROM users WHERE id = ?", [$userId]);
            $newStatus = $user['is_active'] ? 0 : 1;
            $db->update('users', ['is_active' => $newStatus], 'id = ?', [$userId]);
            logAudit('user_status_toggled', 'user', $userId);
            setFlash('success', 'User status updated.');
        }
        redirect('/holy-trinity/admin/users.php');
    }
}

// Filters
$role = sanitize($_GET['role'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($role) { $where .= " AND role = ?"; $params[] = $role; }
if ($status !== '') { $where .= " AND is_active = ?"; $params[] = intval($status); }
if ($search) {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$users = $db->fetchAll("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Admin</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/users.php" class="active"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-users"></i> Users</h1>
                    <p class="text-muted">Manage parishioner accounts and roles</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label style="font-size:0.8rem;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, email, phone..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Role</label>
                            <select name="role" class="form-control">
                                <option value="">All Roles</option>
                                <option value="parishioner" <?= $role === 'parishioner' ? 'selected' : '' ?>>Parishioner</option>
                                <option value="priest" <?= $role === 'priest' ? 'selected' : '' ?>>Priest</option>
                                <option value="department_head" <?= $role === 'department_head' ? 'selected' : '' ?>>Department Head</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="/holy-trinity/admin/users.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Users (<?= count($users) ?>)</h3>
                    <button onclick="printContent('usersTableContent')" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="table-responsive" id="usersTableContent">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="7" class="text-center p-3">No users found</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.8rem;">
                                                <?= strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)) ?>
                                            </div>
                                            <strong><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= sanitize($u['email']) ?></td>
                                    <td><?= sanitize($u['phone'] ?? '-') ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="update_role">
                                            <select name="new_role" class="form-control" style="padding:0.3rem 1.5rem 0.3rem 0.5rem; font-size:0.8rem; width:auto;" onchange="this.form.submit()">
                                                <option value="parishioner" <?= $u['role'] === 'parishioner' ? 'selected' : '' ?>>Parishioner</option>
                                                <option value="priest" <?= $u['role'] === 'priest' ? 'selected' : '' ?>>Priest</option>
                                                <option value="department_head" <?= $u['role'] === 'department_head' ? 'selected' : '' ?>>Dept Head</option>
                                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $u['is_active'] ? 'success' : 'error' ?>">
                                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($u['created_at'], 'M d, Y') ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline' : 'btn-primary' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                    </td>
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
    <script>
        if (window.innerWidth <= 1024) document.getElementById('sidebarToggle').style.display = 'inline-flex';
        window.addEventListener('resize', function() {
            const btn = document.getElementById('sidebarToggle');
            if (btn) btn.style.display = window.innerWidth <= 1024 ? 'inline-flex' : 'none';
        });
    </script>
</body>
</html>
