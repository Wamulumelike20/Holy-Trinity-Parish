<?php
$pageTitle = 'Parish Priest Dashboard';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['priest', 'super_admin', 'admin']);

$db = Database::getInstance();

// All departments with stats
$departments = $db->fetchAll(
    "SELECT d.*,
     (SELECT COUNT(*) FROM department_members WHERE department_id = d.id) as member_count,
     (SELECT COUNT(*) FROM department_reports WHERE department_id = d.id AND status = 'submitted') as pending_reports,
     u.first_name as head_first, u.last_name as head_last
     FROM departments d LEFT JOIN users u ON d.head_user_id = u.id
     WHERE d.is_active = 1 ORDER BY d.name"
);

// Pending reports across all departments
$pendingReports = $db->fetchAll(
    "SELECT dr.*, d.name as dept_name, u.first_name, u.last_name
     FROM department_reports dr
     INNER JOIN departments d ON dr.department_id = d.id
     LEFT JOIN users u ON dr.submitted_by = u.id
     WHERE dr.status = 'submitted'
     ORDER BY dr.created_at DESC LIMIT 10"
);

// Recent reports (all statuses)
$allRecentReports = $db->fetchAll(
    "SELECT dr.*, d.name as dept_name, u.first_name, u.last_name
     FROM department_reports dr
     INNER JOIN departments d ON dr.department_id = d.id
     LEFT JOIN users u ON dr.submitted_by = u.id
     ORDER BY dr.created_at DESC LIMIT 15"
);

// Overall stats
$totalMembers = $db->fetch("SELECT COUNT(DISTINCT user_id) as cnt FROM department_members")['cnt'];
$totalReports = $db->fetch("SELECT COUNT(*) as cnt FROM department_reports")['cnt'];
$pendingReportCount = $db->fetch("SELECT COUNT(*) as cnt FROM department_reports WHERE status = 'submitted'")['cnt'];
$pendingAppointments = $db->fetch("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")['cnt'];
$totalDonationsMonth = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed' AND MONTH(donation_date) = MONTH(CURDATE()) AND YEAR(donation_date) = YEAR(CURDATE())")['total'];

// Upcoming appointments for the priest
$priestAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name, u.last_name, d.name as dept_name
     FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     LEFT JOIN departments d ON a.department_id = d.id
     WHERE a.appointment_date >= CURDATE() AND a.status IN ('pending','approved')
     ORDER BY a.appointment_date ASC, a.start_time ASC LIMIT 10"
);

// Recent announcements
$announcements = $db->fetchAll("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Parish Priest</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Overview</div>
                <a href="/holy-trinity/priest/dashboard.php" class="active"><i class="fas fa-church"></i> Dashboard</a>
                <a href="/holy-trinity/priest/reports.php"><i class="fas fa-file-alt"></i> All Reports <?php if ($pendingReportCount): ?><span class="badge badge-warning" style="font-size:0.65rem; padding:0.15rem 0.4rem; margin-left:0.25rem;"><?= $pendingReportCount ?></span><?php endif; ?></a>

                <div class="sidebar-section">All Departments</div>
                <?php foreach ($departments as $d): ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>">
                        <i class="fas fa-building"></i> <?= sanitize($d['name']) ?>
                        <?php if ($d['pending_reports']): ?><span class="badge badge-warning" style="font-size:0.6rem; padding:0.1rem 0.35rem; margin-left:0.2rem;"><?= $d['pending_reports'] ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>

                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacraments</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>

                <div class="sidebar-section">System</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Panel</a>
                <a href="/holy-trinity/admin/reports.php"><i class="fas fa-chart-bar"></i> Analytics</a>

                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="font-family:var(--font-heading); color:var(--primary); margin-bottom:0.25rem;">
                        <i class="fas fa-church" style="color:var(--gold);"></i> Parish Priest Dashboard
                    </h1>
                    <p class="text-muted">Welcome, <?= sanitize($_SESSION['user_first'] ?? 'Father') ?>. Overview of all parish departments and activities.</p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-building"></i></div>
                    <div class="stat-info"><h3><?= count($departments) ?></h3><p>Active Departments</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalMembers) ?></h3><p>Dept Members</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info"><h3><?= $pendingReportCount ?></h3><p>Pending Reports</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= $pendingAppointments ?></h3><p>Pending Appointments</p></div>
                </div>
            </div>

            <!-- Department Overview Cards -->
            <h2 style="font-family:var(--font-heading); color:var(--primary); margin:2rem 0 1rem;"><i class="fas fa-building" style="color:var(--gold);"></i> Department Overview</h2>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem; margin-bottom:2rem;">
                <?php foreach ($departments as $d): ?>
                <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>" class="card" style="text-decoration:none; color:inherit; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
                    <div class="card-body" style="padding:1.25rem;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
                            <div style="width:45px; height:45px; border-radius:var(--radius); background:rgba(212,168,67,0.15); display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-building" style="color:var(--gold); font-size:1.1rem;"></i>
                            </div>
                            <?php if ($d['pending_reports']): ?>
                                <span class="badge badge-warning"><?= $d['pending_reports'] ?> pending</span>
                            <?php endif; ?>
                        </div>
                        <h4 style="margin-bottom:0.25rem; font-family:var(--font-body);"><?= sanitize($d['name']) ?></h4>
                        <div style="font-size:0.85rem; color:var(--gray);">
                            <span><i class="fas fa-users" style="color:var(--gold);"></i> <?= $d['member_count'] ?> members</span>
                            <?php if ($d['head_first']): ?>
                                <br><span><i class="fas fa-user-tie" style="color:var(--gold);"></i> <?= sanitize($d['head_first'] . ' ' . $d['head_last']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="grid-2">
                <!-- Pending Reports -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt" style="color:var(--gold);"></i> Pending Reports</h3>
                        <a href="/holy-trinity/priest/reports.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($pendingReports)): ?>
                            <div style="padding:2rem; text-align:center;">
                                <i class="fas fa-check-circle" style="font-size:2rem; color:var(--success); margin-bottom:0.5rem; display:block;"></i>
                                <p class="text-muted">All reports have been reviewed!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingReports as $r): ?>
                            <div style="padding:0.85rem 1.25rem; border-bottom:1px solid var(--light-gray);">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                    <div>
                                        <strong style="font-size:0.9rem;"><?= sanitize($r['report_title']) ?></strong>
                                        <div class="text-muted" style="font-size:0.8rem;">
                                            <span class="badge badge-gold" style="font-size:0.7rem;"><?= sanitize($r['dept_name']) ?></span>
                                            &bull; <?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?>
                                            &bull; <?= formatDate($r['created_at'], 'M d') ?>
                                        </div>
                                    </div>
                                    <a href="/holy-trinity/priest/reports.php?review=<?= $r['id'] ?>" class="btn btn-sm btn-primary" style="white-space:nowrap;"><i class="fas fa-eye"></i> Review</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check" style="color:var(--gold);"></i> Upcoming Appointments</h3>
                        <a href="/holy-trinity/admin/appointments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($priestAppointments)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No upcoming appointments</p>
                        <?php else: ?>
                            <?php foreach ($priestAppointments as $apt): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= sanitize($apt['first_name'] . ' ' . $apt['last_name']) ?></strong>
                                    <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : 'warning' ?>"><?= ucfirst($apt['status']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    <?= formatDate($apt['appointment_date'], 'D, M d') ?> at <?= formatTime($apt['start_time']) ?> &bull; <?= sanitize($apt['reason']) ?>
                                    <?php if ($apt['dept_name']): ?>&bull; <span class="badge badge-gold" style="font-size:0.65rem;"><?= sanitize($apt['dept_name']) ?></span><?php endif; ?>
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
