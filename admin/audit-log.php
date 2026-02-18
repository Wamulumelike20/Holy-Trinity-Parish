<?php
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

$search = sanitize($_GET['search'] ?? '');
$action = sanitize($_GET['action'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR al.action LIKE ? OR al.entity_type LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
if ($action) { $where .= " AND al.action = ?"; $params[] = $action; }
if ($dateFrom) { $where .= " AND DATE(al.created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo) { $where .= " AND DATE(al.created_at) <= ?"; $params[] = $dateTo; }

$logs = $db->fetchAll(
    "SELECT al.*, u.first_name, u.last_name, u.email
     FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id
     WHERE {$where} ORDER BY al.created_at DESC LIMIT 200",
    $params
);

$actions = $db->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
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
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">System</div>
                <a href="/holy-trinity/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="/holy-trinity/admin/audit-log.php" class="active"><i class="fas fa-history"></i> Audit Log</a>
                <a href="/holy-trinity/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-history"></i> Audit Log</h1>
                    <p class="text-muted">Track all system activities and changes</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:180px;">
                            <label style="font-size:0.8rem;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="User, action..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Action</label>
                            <select name="action" class="form-control">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $a): ?>
                                    <option value="<?= sanitize($a['action']) ?>" <?= $action === $a['action'] ? 'selected' : '' ?>><?= sanitize(ucfirst(str_replace('_', ' ', $a['action']))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="/holy-trinity/admin/audit-log.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Log Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Activity Log (<?= count($logs) ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="5" class="text-center p-3">No log entries found</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?= formatDate($log['created_at'], 'M d, Y') ?><br><small class="text-muted"><?= formatDate($log['created_at'], 'g:i:s A') ?></small></td>
                                    <td>
                                        <?php if ($log['first_name']): ?>
                                            <?= sanitize($log['first_name'] . ' ' . $log['last_name']) ?>
                                            <br><small class="text-muted"><?= sanitize($log['email']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?= sanitize(ucfirst(str_replace('_', ' ', $log['action']))) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['entity_type']): ?>
                                            <?= sanitize(ucfirst($log['entity_type'])) ?>
                                            <?php if ($log['entity_id']): ?> #<?= $log['entity_id'] ?><?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><code style="font-size:0.8rem;"><?= sanitize($log['ip_address'] ?? '-') ?></code></td>
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

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
