<?php
/**
 * Shared Department Dashboard Template
 * Individual department files include this after setting $deptSlug
 */

require_once __DIR__ . '/../../config/app.php';
requireLogin();

$db = Database::getInstance();
$userRole = $_SESSION['user_role'] ?? '';

// Load department by slug
$dept = $db->fetch("SELECT * FROM departments WHERE slug = ? AND is_active = 1", [$deptSlug]);
if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('/holy-trinity/department/login.php');
}

$deptId = $dept['id'];

// Access check: must be dept head, dept admin member, or priest/admin
$isHead = ($dept['head_user_id'] == $_SESSION['user_id']);
$membership = $db->fetch(
    "SELECT * FROM department_members WHERE department_id = ? AND user_id = ?",
    [$deptId, $_SESSION['user_id']]
);
$isPrivileged = in_array($userRole, ['priest', 'super_admin', 'admin']);

if (!$isHead && !$membership && !$isPrivileged) {
    setFlash('error', 'You do not have access to this department dashboard.');
    redirect('/holy-trinity/department/login.php');
}

$isDeptAdmin = $isHead || $isPrivileged || ($membership && in_array($membership['role'], ['admin', 'head', 'leader']));

// Department metadata
$deptMeta = [
    'parish-office'    => ['icon' => 'fa-church',              'color' => '#1a365d', 'gradient' => 'linear-gradient(135deg, #1a365d, #2c5282)',  'light' => '#dbeafe'],
    'finance'          => ['icon' => 'fa-coins',               'color' => '#2d6a4f', 'gradient' => 'linear-gradient(135deg, #2d6a4f, #40916c)',  'light' => '#d1fae5'],
    'catechism'        => ['icon' => 'fa-book-bible',          'color' => '#7b2cbf', 'gradient' => 'linear-gradient(135deg, #7b2cbf, #9d4edd)',  'light' => '#ede9fe'],
    'youth-ministry'   => ['icon' => 'fa-people-group',        'color' => '#e85d04', 'gradient' => 'linear-gradient(135deg, #e85d04, #f48c06)',  'light' => '#ffedd5'],
    'choir'            => ['icon' => 'fa-music',               'color' => '#d62828', 'gradient' => 'linear-gradient(135deg, #d62828, #e63946)',  'light' => '#fee2e2'],
    'marriage-family'  => ['icon' => 'fa-heart',               'color' => '#c2185b', 'gradient' => 'linear-gradient(135deg, #c2185b, #e91e63)',  'light' => '#fce4ec'],
    'social-outreach'  => ['icon' => 'fa-hands-holding-heart', 'color' => '#0077b6', 'gradient' => 'linear-gradient(135deg, #0077b6, #00b4d8)',  'light' => '#e0f2fe'],
];

$meta = $deptMeta[$deptSlug] ?? ['icon' => 'fa-building', 'color' => '#475569', 'gradient' => 'linear-gradient(135deg, #475569, #64748b)', 'light' => '#f1f5f9'];

// Stats
$memberCount = $db->fetch("SELECT COUNT(*) as cnt FROM department_members WHERE department_id = ?", [$deptId])['cnt'];
$head = $db->fetch("SELECT first_name, last_name, email, phone FROM users WHERE id = ?", [$dept['head_user_id'] ?? 0]);

$recentMembers = $db->fetchAll(
    "SELECT u.first_name, u.last_name, u.email, u.phone, dm.role, dm.joined_at
     FROM department_members dm INNER JOIN users u ON dm.user_id = u.id
     WHERE dm.department_id = ? ORDER BY dm.joined_at DESC LIMIT 10",
    [$deptId]
);

$deptAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name, u.last_name FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     WHERE a.department_id = ? AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC LIMIT 10",
    [$deptId]
);

$recentReports = $db->fetchAll(
    "SELECT dr.*, u.first_name, u.last_name FROM department_reports dr
     LEFT JOIN users u ON dr.submitted_by = u.id
     WHERE dr.department_id = ? ORDER BY dr.created_at DESC LIMIT 5",
    [$deptId]
);

$deptDocs = $db->fetchAll(
    "SELECT d.*, u.first_name, u.last_name FROM documents d
     LEFT JOIN users u ON d.uploaded_by = u.id
     WHERE d.department_id = ? ORDER BY d.created_at DESC LIMIT 5",
    [$deptId]
);

$notifications = getNotifications(15);
$unreadCount = getUnreadNotificationCount();

// Department-specific extra stats
$extraStats = [];
if ($deptSlug === 'finance') {
    $totalDonations = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE status = 'completed'");
    $monthlyDonations = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $extraStats['total_donations'] = $totalDonations['total'];
    $extraStats['monthly_donations'] = $monthlyDonations['total'];
} elseif ($deptSlug === 'catechism') {
    $extraStats['programs'] = $db->fetch("SELECT COUNT(*) as cnt FROM ministries")['cnt'] ?? 0;
} elseif ($deptSlug === 'youth-ministry') {
    $extraStats['events'] = $db->fetch("SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE() AND status = 'published'")['cnt'] ?? 0;
} elseif ($deptSlug === 'choir') {
    $massCount = $db->fetch("SELECT COUNT(*) as cnt FROM mass_schedules WHERE is_active = 1")['cnt'] ?? 0;
    $extraStats['masses'] = $massCount;
}

// All departments for sidebar
$allDepts = $db->fetchAll("SELECT id, name, slug FROM departments WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../../includes/pwa-head.php"; ?>
    <title><?= sanitize($dept['name']) ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        .dept-sidebar {
            background: <?= $meta['gradient'] ?>;
        }
        .dept-sidebar .sidebar-brand {
            background: rgba(0,0,0,0.2);
        }
        .dept-sidebar .sidebar-menu a:hover,
        .dept-sidebar .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
        }
        .dept-header-banner {
            background: <?= $meta['gradient'] ?>;
            color: #fff;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .dept-header-banner h1 {
            font-family: 'Cinzel', serif;
            font-size: 1.4rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .dept-header-banner p {
            margin: 0.25rem 0 0;
            opacity: 0.85;
            font-size: 0.9rem;
        }
        .dept-stat-card {
            border-left: 4px solid <?= $meta['color'] ?>;
        }
        .dept-stat-card .stat-icon {
            background: <?= $meta['light'] ?>;
            color: <?= $meta['color'] ?>;
        }
        .dept-badge-role {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: <?= $meta['light'] ?>;
            color: <?= $meta['color'] ?>;
        }
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            background: <?= $meta['light'] ?>;
            color: <?= $meta['color'] ?>;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .quick-action-btn:hover {
            background: <?= $meta['color'] ?>;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar dept-sidebar">
            <div class="sidebar-brand">
                <i class="fas <?= $meta['icon'] ?>"></i>
                <span><?= sanitize($dept['name']) ?></span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">My Department</div>
                <a href="/holy-trinity/department/dashboards/<?= $deptSlug ?>.php" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>">
                    <i class="fas fa-users"></i> Members
                </a>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>&action=new">
                    <i class="fas fa-plus-circle"></i> New Report
                </a>

                <?php if (count($allDepts) > 1 && $isPrivileged): ?>
                <div class="sidebar-section">Other Departments</div>
                <?php foreach ($allDepts as $d):
                    if ($d['slug'] === $deptSlug) continue;
                    $dm = $deptMeta[$d['slug']] ?? ['icon' => 'fa-building'];
                ?>
                    <a href="/holy-trinity/department/dashboards/<?= $d['slug'] ?>.php">
                        <i class="fas <?= $dm['icon'] ?>"></i> <?= sanitize($d['name']) ?>
                    </a>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($isPrivileged): ?>
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
            <!-- Department Header Banner -->
            <div class="dept-header-banner">
                <div>
                    <h1><i class="fas <?= $meta['icon'] ?>"></i> <?= sanitize($dept['name']) ?></h1>
                    <p><?= sanitize($dept['description'] ?? 'Department Dashboard') ?></p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../../includes/notifications.php'; ?>
                    <span style="font-size:0.85rem; opacity:0.8;">
                        <i class="fas fa-user"></i> <?= sanitize($_SESSION['user_name'] ?? '') ?>
                    </span>
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if ($isDeptAdmin): ?>
            <div style="display:flex; gap:0.75rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>" class="quick-action-btn">
                    <i class="fas fa-user-plus"></i> Manage Members
                </a>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>&action=new" class="quick-action-btn">
                    <i class="fas fa-file-circle-plus"></i> Submit Report
                </a>
                <a href="/holy-trinity/appointments/book.php" class="quick-action-btn">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </a>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= $memberCount ?></h3><p>Members</p></div>
                </div>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= count($deptAppointments) ?></h3><p>Upcoming Appointments</p></div>
                </div>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info"><h3><?= count($recentReports) ?></h3><p>Reports</p></div>
                </div>
                <?php if ($deptSlug === 'finance' && isset($extraStats['total_donations'])): ?>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($extraStats['total_donations']) ?></h3><p>Total Donations</p></div>
                </div>
                <?php elseif ($deptSlug === 'choir' && isset($extraStats['masses'])): ?>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-music"></i></div>
                    <div class="stat-info"><h3><?= $extraStats['masses'] ?></h3><p>Weekly Masses</p></div>
                </div>
                <?php elseif ($deptSlug === 'youth-ministry' && isset($extraStats['events'])): ?>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info"><h3><?= $extraStats['events'] ?></h3><p>Upcoming Events</p></div>
                </div>
                <?php else: ?>
                <div class="stat-card dept-stat-card">
                    <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="stat-info"><h3><?= count($deptDocs) ?></h3><p>Documents</p></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <!-- Department Head -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie" style="color:<?= $meta['color'] ?>;"></i> Department Head</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($head): ?>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <div style="width:56px; height:56px; border-radius:50%; background:<?= $meta['gradient'] ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
                                    <?= strtoupper(substr($head['first_name'],0,1) . substr($head['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <strong style="font-size:1.05rem;"><?= sanitize($head['first_name'] . ' ' . $head['last_name']) ?></strong>
                                    <div class="text-muted" style="font-size:0.85rem;"><i class="fas fa-envelope"></i> <?= sanitize($head['email']) ?></div>
                                    <?php if ($head['phone']): ?>
                                        <div class="text-muted" style="font-size:0.85rem;"><i class="fas fa-phone"></i> <?= sanitize($head['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted"><i class="fas fa-info-circle"></i> No department head assigned yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check" style="color:<?= $meta['color'] ?>;"></i> Upcoming Appointments</h3>
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
                        <h3><i class="fas fa-users" style="color:<?= $meta['color'] ?>;"></i> Members</h3>
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
                                <span class="dept-badge-role"><?= ucfirst($m['role']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt" style="color:<?= $meta['color'] ?>;"></i> Recent Reports</h3>
                        <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentReports)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No reports submitted yet</p>
                        <?php else: ?>
                            <?php foreach ($recentReports as $r): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= sanitize($r['report_title']) ?></strong>
                                    <span class="badge badge-<?= $r['status'] === 'acknowledged' ? 'success' : ($r['status'] === 'reviewed' ? 'info' : ($r['status'] === 'submitted' ? 'warning' : 'primary')) ?>"><?= ucfirst($r['status']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    By <?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?> &bull; <?= formatDate($r['created_at']) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($deptSlug === 'finance' && isset($extraStats['monthly_donations'])): ?>
                <!-- Finance: Monthly Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line" style="color:<?= $meta['color'] ?>;"></i> Financial Summary</h3>
                    </div>
                    <div class="card-body">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div style="text-align:center; padding:1rem; background:<?= $meta['light'] ?>; border-radius:8px;">
                                <div style="font-size:1.3rem; font-weight:700; color:<?= $meta['color'] ?>;">ZMW <?= number_format($extraStats['total_donations']) ?></div>
                                <div class="text-muted" style="font-size:0.8rem;">Total All Time</div>
                            </div>
                            <div style="text-align:center; padding:1rem; background:<?= $meta['light'] ?>; border-radius:8px;">
                                <div style="font-size:1.3rem; font-weight:700; color:<?= $meta['color'] ?>;">ZMW <?= number_format($extraStats['monthly_donations']) ?></div>
                                <div class="text-muted" style="font-size:0.8rem;">This Month</div>
                            </div>
                        </div>
                        <div style="margin-top:1rem; text-align:center;">
                            <a href="/holy-trinity/admin/donations.php" class="btn btn-sm btn-outline">View All Donations</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Documents -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-folder-open" style="color:<?= $meta['color'] ?>;"></i> Documents</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($deptDocs)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No documents uploaded yet</p>
                        <?php else: ?>
                            <?php foreach ($deptDocs as $doc): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); display:flex; justify-content:space-between; align-items:center; font-size:0.9rem;">
                                <div>
                                    <i class="fas fa-file-pdf" style="color:<?= $meta['color'] ?>;"></i>
                                    <strong><?= sanitize($doc['title'] ?? $doc['file_name'] ?? 'Document') ?></strong>
                                    <br><small class="text-muted">By <?= sanitize($doc['first_name'] . ' ' . $doc['last_name']) ?> &bull; <?= formatDate($doc['created_at']) ?></small>
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

    <?php include_once __DIR__ . "/../../includes/pwa-sw.php"; ?>
</body>
</html>
