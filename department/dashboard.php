<?php
$pageTitle = 'Department Dashboard';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$userRole = $_SESSION['user_role'] ?? '';

// Determine which department to show
$deptId = intval($_GET['dept'] ?? 0);

// If priest or super_admin, they can view any department
// Department heads see their own department(s)
if ($deptId) {
    $dept = $db->fetch("SELECT * FROM departments WHERE id = ? AND is_active = 1", [$deptId]);
} else {
    // Default: first department user belongs to
    $dept = $db->fetch(
        "SELECT d.* FROM departments d WHERE d.head_user_id = ? AND d.is_active = 1 LIMIT 1",
        [$_SESSION['user_id']]
    );
    if (!$dept) {
        $dept = $db->fetch(
            "SELECT d.* FROM departments d INNER JOIN department_members dm ON dm.department_id = d.id WHERE dm.user_id = ? AND d.is_active = 1 LIMIT 1",
            [$_SESSION['user_id']]
        );
    }
}

// Access check: priest/super_admin can see all, others need membership
if (!in_array($userRole, ['priest', 'super_admin', 'admin'])) {
    if (!$dept) {
        setFlash('error', 'You are not assigned to any department.');
        redirect('/holy-trinity/portal/dashboard.php');
    }
    $isMember = $db->fetch(
        "SELECT id FROM department_members WHERE department_id = ? AND user_id = ?",
        [$dept['id'], $_SESSION['user_id']]
    );
    $isHead = ($dept['head_user_id'] == $_SESSION['user_id']);
    if (!$isMember && !$isHead) {
        setFlash('error', 'You do not have access to this department.');
        redirect('/holy-trinity/portal/dashboard.php');
    }
}

if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('/holy-trinity/portal/dashboard.php');
}

$deptId = $dept['id'];
$isHead = ($dept['head_user_id'] == $_SESSION['user_id']) || in_array($userRole, ['priest', 'super_admin', 'admin']);

// Get all departments for sidebar (priest sees all, others see their own)
if (in_array($userRole, ['priest', 'super_admin', 'admin'])) {
    $allDepts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
} else {
    $allDepts = getUserDepartments($_SESSION['user_id']);
}

// Department stats
$memberCount = $db->fetch("SELECT COUNT(*) as cnt FROM department_members WHERE department_id = ?", [$deptId])['cnt'];
$head = $db->fetch("SELECT first_name, last_name, email, phone FROM users WHERE id = ?", [$dept['head_user_id'] ?? 0]);
$recentMembers = $db->fetchAll(
    "SELECT u.first_name, u.last_name, u.email, dm.role, dm.joined_at
     FROM department_members dm INNER JOIN users u ON dm.user_id = u.id
     WHERE dm.department_id = ? ORDER BY dm.joined_at DESC LIMIT 10",
    [$deptId]
);

// Department-specific appointments
$deptAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name, u.last_name FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     WHERE a.department_id = ? AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC LIMIT 10",
    [$deptId]
);

// Department reports
$recentReports = $db->fetchAll(
    "SELECT dr.*, u.first_name, u.last_name FROM department_reports dr
     LEFT JOIN users u ON dr.submitted_by = u.id
     WHERE dr.department_id = ? ORDER BY dr.created_at DESC LIMIT 5",
    [$deptId]
);

// Department documents
$deptDocs = $db->fetchAll(
    "SELECT d.*, u.first_name, u.last_name FROM documents d
     LEFT JOIN users u ON d.uploaded_by = u.id
     WHERE d.department_id = ? ORDER BY d.created_at DESC LIMIT 5",
    [$deptId]
);

// Notifications
$notifications = getNotifications(15);
$unreadCount = getUnreadNotificationCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title><?= sanitize($dept['name']) ?> Dashboard | <?= APP_NAME ?></title>
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
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>" class="<?= $d['id'] == $deptId ? 'active' : '' ?>">
                        <i class="fas fa-building"></i> <?= sanitize($d['name']) ?>
                    </a>
                <?php endforeach; ?>

                <div class="sidebar-section">Actions</div>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>"><i class="fas fa-users"></i> Members</a>

                <?php if (in_array($userRole, ['priest', 'super_admin', 'admin'])): ?>
                <div class="sidebar-section">Administration</div>
                <a href="/holy-trinity/priest/dashboard.php"><i class="fas fa-church"></i> Priest Dashboard</a>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Panel</a>
                <?php endif; ?>

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
                        <i class="fas fa-building" style="color:var(--gold);"></i> <?= sanitize($dept['name']) ?>
                    </h1>
                    <p class="text-muted"><?= sanitize($dept['description'] ?? '') ?></p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                    <?php if ($isHead): ?>
                        <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>&action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Report</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= $memberCount ?></h3><p>Members</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= count($deptAppointments) ?></h3><p>Upcoming Appointments</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info"><h3><?= count($recentReports) ?></h3><p>Recent Reports</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-folder-open"></i></div>
                    <div class="stat-info"><h3><?= count($deptDocs) ?></h3><p>Documents</p></div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Department Head -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie"></i> Department Head</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($head): ?>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <div style="width:56px; height:56px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
                                    <?= strtoupper(substr($head['first_name'],0,1) . substr($head['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <strong style="font-size:1.1rem;"><?= sanitize($head['first_name'] . ' ' . $head['last_name']) ?></strong>
                                    <div class="text-muted" style="font-size:0.85rem;"><i class="fas fa-envelope"></i> <?= sanitize($head['email']) ?></div>
                                    <?php if ($head['phone']): ?>
                                        <div class="text-muted" style="font-size:0.85rem;"><i class="fas fa-phone"></i> <?= sanitize($head['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No department head assigned</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Upcoming Appointments</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($deptAppointments)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No upcoming appointments</p>
                        <?php else: ?>
                            <?php foreach ($deptAppointments as $apt): ?>
                            <div style="padding:0.85rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= sanitize($apt['first_name'] . ' ' . $apt['last_name']) ?></strong>
                                    <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : 'info') ?>"><?= ucfirst($apt['status']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    <?= formatDate($apt['appointment_date']) ?> at <?= formatTime($apt['start_time']) ?> &bull; <?= sanitize($apt['reason']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Members -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Members</h3>
                        <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentMembers)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No members yet</p>
                        <?php else: ?>
                            <?php foreach ($recentMembers as $m): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); display:flex; justify-content:space-between; align-items:center; font-size:0.9rem;">
                                <div>
                                    <strong><?= sanitize($m['first_name'] . ' ' . $m['last_name']) ?></strong>
                                    <br><small class="text-muted"><?= sanitize($m['email']) ?></small>
                                </div>
                                <span class="badge badge-primary"><?= ucfirst($m['role']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> Recent Reports</h3>
                        <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentReports)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No reports submitted</p>
                        <?php else: ?>
                            <?php foreach ($recentReports as $r): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= sanitize($r['report_title']) ?></strong>
                                    <span class="badge badge-<?= $r['status'] === 'acknowledged' ? 'success' : ($r['status'] === 'reviewed' ? 'info' : ($r['status'] === 'submitted' ? 'warning' : 'primary')) ?>"><?= ucfirst($r['status']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    By <?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?> &bull; <?= formatDate($r['created_at']) ?> &bull; <?= ucfirst($r['report_type']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/holy-trinity/assets/js/main.js"></script>

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
