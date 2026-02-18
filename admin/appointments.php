<?php
$pageTitle = 'Manage Appointments';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $aptId = intval($_POST['appointment_id'] ?? 0);
        $action = sanitize($_POST['action']);
        $notes = sanitize($_POST['admin_notes'] ?? '');

        if ($aptId && in_array($action, ['approve', 'decline', 'complete', 'cancel'])) {
            $statusMap = ['approve' => 'approved', 'decline' => 'declined', 'complete' => 'completed', 'cancel' => 'cancelled'];
            $newStatus = $statusMap[$action];

            $db->update('appointments', [
                'status' => $newStatus,
                'admin_notes' => $notes,
            ], 'id = ?', [$aptId]);

            logAudit("appointment_{$action}", 'appointment', $aptId);
            setFlash('success', "Appointment {$newStatus} successfully.");
            redirect('/holy-trinity/admin/appointments.php');
        }
    }
}

// Filters
$status = sanitize($_GET['status'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($status) {
    $where .= " AND a.status = ?";
    $params[] = $status;
}
if ($dateFrom) {
    $where .= " AND a.appointment_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND a.appointment_date <= ?";
    $params[] = $dateTo;
}
if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR a.reference_number LIKE ? OR a.reason LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// For non-admin roles, only show their appointments
if (!isAdmin()) {
    $where .= " AND a.provider_id = ?";
    $params[] = $_SESSION['user_id'];
}

$appointments = $db->fetchAll(
    "SELECT a.*, u.first_name as user_first, u.last_name as user_last, u.email as user_email, u.phone as user_phone,
            p.first_name as prov_first, p.last_name as prov_last, d.name as dept_name
     FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     LEFT JOIN users p ON a.provider_id = p.id
     LEFT JOIN departments d ON a.department_id = d.id
     WHERE {$where}
     ORDER BY a.appointment_date DESC, a.start_time DESC",
    $params
);
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-cross"></i>
                <span>HTP Admin</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/ministries.php"><i class="fas fa-people-group"></i> Ministries</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;">
                <i class="fas fa-bars"></i> Menu
            </button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-calendar-check"></i> Appointments</h1>
                    <p class="text-muted">Manage and review appointment requests</p>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button onclick="printContent('appointmentsTable')" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label style="font-size:0.8rem;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, reference, reason..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="declined" <?= $status === 'declined' ? 'selected' : '' ?>>Declined</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
                        <a href="/holy-trinity/admin/appointments.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="card" id="appointmentsTable">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Parishioner</th>
                                <th>Provider</th>
                                <th>Date & Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr><td colspan="7" class="text-center p-3">No appointments found</td></tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td><code style="font-size:0.8rem;"><?= $apt['reference_number'] ?></code></td>
                                    <td>
                                        <strong><?= sanitize($apt['user_first'] . ' ' . $apt['user_last']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize($apt['user_email']) ?></small>
                                    </td>
                                    <td>
                                        <?= sanitize($apt['prov_first'] . ' ' . $apt['prov_last']) ?>
                                        <?php if ($apt['dept_name']): ?><br><small class="text-muted"><?= sanitize($apt['dept_name']) ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatDate($apt['appointment_date']) ?>
                                        <br><small><?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></small>
                                    </td>
                                    <td><?= sanitize($apt['reason']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : ($apt['status'] === 'completed' ? 'info' : 'error')) ?>">
                                            <?= ucfirst($apt['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                                            <?php if ($apt['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-sm btn-primary" title="Approve"><i class="fas fa-check"></i></button>
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                    <input type="hidden" name="action" value="decline">
                                                    <button class="btn btn-sm btn-accent" title="Decline"><i class="fas fa-times"></i></button>
                                                </form>
                                            <?php elseif ($apt['status'] === 'approved'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                    <input type="hidden" name="action" value="complete">
                                                    <button class="btn btn-sm btn-primary" title="Mark Complete"><i class="fas fa-check-double"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="/holy-trinity/admin/appointment-detail.php?id=<?= $apt['id'] ?>" class="btn btn-sm btn-outline" title="View Details"><i class="fas fa-eye"></i></a>
                                        </div>
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

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
