<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$user = currentUser();
$userId = $user['id'];
$userRole = $user['role'];

// General parish stats (visible to all)
$totalMembers = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE is_active = 1")['cnt'] ?? 0;
$upcomingEvents = $db->fetchAll("SELECT * FROM events WHERE event_date >= CURDATE() AND is_active = 1 ORDER BY event_date ASC LIMIT 5");
$latestAnnouncements = $db->fetchAll("SELECT a.*, u.first_name, u.last_name FROM announcements a JOIN users u ON a.created_by = u.id WHERE a.is_active = 1 ORDER BY a.created_at DESC LIMIT 5");
$massSchedules = $db->fetchAll("SELECT * FROM mass_schedules WHERE is_active = 1 ORDER BY FIELD(day_of_week,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') LIMIT 10");

// User's departments
$userDepts = getUserDepartments($userId);
$userSCCs = getUserSCCs($userId);
$userLayGroups = getUserLayGroups($userId);

// Build accessible dashboards list based on role
$dashboards = [];

// Everyone gets My Portal
$dashboards[] = [
    'title' => 'My Portal',
    'desc' => 'Appointments, donations, sacramental records & personal info',
    'icon' => 'fa-user-circle',
    'color' => '#1a365d',
    'gradient' => 'linear-gradient(135deg,#1a365d,#2c5282)',
    'url' => '/holy-trinity/portal/dashboard.php',
    'badge' => null,
];

// Staff dashboards (guards, house helpers, general workers)
if (isStaff()) {
    $staffLabels = ['guard' => 'Security Guard', 'house_helper' => 'House Helper', 'general_worker' => 'General Worker'];
    $staffIcons = ['guard' => 'fa-shield-halved', 'house_helper' => 'fa-house-chimney', 'general_worker' => 'fa-hard-hat'];
    $dashboards[] = [
        'title' => 'Staff Dashboard',
        'desc' => 'Clock in/out, attendance, leave requests & payslips',
        'icon' => $staffIcons[$userRole] ?? 'fa-id-badge',
        'color' => '#0f172a',
        'gradient' => 'linear-gradient(135deg,#0f172a,#334155)',
        'url' => '/holy-trinity/staff/dashboard.php',
        'badge' => $staffLabels[$userRole] ?? 'Staff',
    ];
}

// Parish Executive
if (in_array($userRole, ['parish_executive', 'priest', 'super_admin', 'admin'])) {
    $pendingLeave = $db->fetch("SELECT COUNT(*) as cnt FROM leave_requests WHERE status = 'pending'")['cnt'] ?? 0;
    $dashboards[] = [
        'title' => 'Executive Dashboard',
        'desc' => 'Staff attendance, leave approvals, budgets & purchase requests',
        'icon' => 'fa-user-tie',
        'color' => '#0f172a',
        'gradient' => 'linear-gradient(135deg,#0f172a,#1e293b)',
        'url' => '/holy-trinity/executive/dashboard.php',
        'badge' => $pendingLeave > 0 ? $pendingLeave . ' pending' : null,
    ];
}

// Liturgical Coordinator
if (in_array($userRole, ['liturgical_coordinator', 'priest', 'super_admin', 'admin'])) {
    $dashboards[] = [
        'title' => 'Liturgical Dashboard',
        'desc' => 'Liturgical schedules, issues, purchase requests & budgets',
        'icon' => 'fa-book-bible',
        'color' => '#7b2cbf',
        'gradient' => 'linear-gradient(135deg,#7b2cbf,#9d4edd)',
        'url' => '/holy-trinity/liturgical/dashboard.php',
        'badge' => null,
    ];
}

// Priest Dashboard
if (in_array($userRole, ['priest', 'super_admin'])) {
    $dashboards[] = [
        'title' => 'Priest Dashboard',
        'desc' => 'Sacraments, appointments, pastoral overview & reports',
        'icon' => 'fa-church',
        'color' => '#7c3aed',
        'gradient' => 'linear-gradient(135deg,#7c3aed,#a78bfa)',
        'url' => '/holy-trinity/priest/dashboard.php',
        'badge' => null,
    ];
}

// Admin Dashboard
if (in_array($userRole, ['super_admin', 'admin'])) {
    $dashboards[] = [
        'title' => 'Admin Dashboard',
        'desc' => 'Users, settings, sacraments, donations & system management',
        'icon' => 'fa-tachometer-alt',
        'color' => '#dc2626',
        'gradient' => 'linear-gradient(135deg,#dc2626,#ef4444)',
        'url' => '/holy-trinity/admin/dashboard.php',
        'badge' => null,
    ];
}

// Department dashboards — only for members/heads
foreach ($userDepts as $dept) {
    $slug = $dept['slug'] ?? strtolower(str_replace([' ', '&', "'"], ['-', '', ''], $dept['name']));
    $deptFile = '/holy-trinity/department/dashboards/' . $slug . '.php';
    $dashboards[] = [
        'title' => $dept['name'],
        'desc' => 'Department activities, members, reports & communications',
        'icon' => 'fa-building',
        'color' => '#2d6a4f',
        'gradient' => 'linear-gradient(135deg,#2d6a4f,#40916c)',
        'url' => $deptFile,
        'badge' => ucfirst($dept['member_role'] ?? 'member'),
    ];
}

// Reports submission (for anyone in a dept, SCC, or lay group)
if (!empty($userDepts) || !empty($userSCCs) || !empty($userLayGroups) || in_array($userRole, ['priest','super_admin','admin','parish_executive','department_head','liturgical_coordinator'])) {
    $dashboards[] = [
        'title' => 'Submit Reports',
        'desc' => 'Send reports to Parish Priest & documents to Parish Executive',
        'icon' => 'fa-file-alt',
        'color' => '#0077b6',
        'gradient' => 'linear-gradient(135deg,#0077b6,#0096c7)',
        'url' => '/holy-trinity/reports/submit.php',
        'badge' => null,
    ];
}

// Notification count
$unreadNotifs = $db->fetch("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", [$userId])['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title>Home | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f1f5f9; min-height:100vh; }

        .app-header {
            background:linear-gradient(135deg,#1a365d 0%,#2c5282 50%,#d4a843 100%);
            color:#fff; padding:1.25rem 1.5rem; position:sticky; top:0; z-index:100;
            display:flex; justify-content:space-between; align-items:center;
            box-shadow:0 2px 12px rgba(0,0,0,0.15);
        }
        .app-header h1 { font-family:'Cinzel',serif; font-size:1.15rem; font-weight:600; }
        .app-header .subtitle { font-size:0.75rem; opacity:0.8; margin-top:2px; }
        .header-actions { display:flex; gap:0.75rem; align-items:center; }
        .header-actions a { color:#fff; text-decoration:none; position:relative; font-size:1.1rem; }
        .notif-badge { position:absolute; top:-6px; right:-8px; background:#ef4444; color:#fff; font-size:0.6rem; width:16px; height:16px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; }

        .app-content { padding:1rem; max-width:900px; margin:0 auto; }

        .welcome-card {
            background:#fff; border-radius:16px; padding:1.25rem 1.5rem; margin-bottom:1.25rem;
            box-shadow:0 1px 4px rgba(0,0,0,0.06); display:flex; align-items:center; gap:1rem;
        }
        .welcome-avatar {
            width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,#1a365d,#d4a843);
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.3rem; font-weight:700; flex-shrink:0;
        }
        .welcome-info h2 { font-size:1.05rem; color:#1e293b; font-weight:600; }
        .welcome-info p { font-size:0.8rem; color:#64748b; margin-top:2px; }

        .section-title { font-size:0.85rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin:1.25rem 0 0.75rem; padding-left:0.25rem; }

        .dash-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
        @media(max-width:500px) { .dash-grid { grid-template-columns:1fr; } }

        .dash-card {
            background:#fff; border-radius:14px; padding:1.15rem; cursor:pointer;
            box-shadow:0 1px 4px rgba(0,0,0,0.06); transition:all 0.2s; text-decoration:none; color:inherit;
            display:flex; flex-direction:column; gap:0.6rem; position:relative; overflow:hidden;
            border:1px solid #f1f5f9;
        }
        .dash-card:hover { transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,0.1); }
        .dash-card:active { transform:scale(0.98); }
        .dash-card-icon {
            width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:1.1rem; flex-shrink:0;
        }
        .dash-card-title { font-size:0.92rem; font-weight:700; color:#1e293b; line-height:1.2; }
        .dash-card-desc { font-size:0.75rem; color:#94a3b8; line-height:1.3; }
        .dash-card-badge {
            position:absolute; top:0.75rem; right:0.75rem; font-size:0.65rem; font-weight:700;
            padding:0.2rem 0.5rem; border-radius:20px; background:#f1f5f9; color:#64748b;
        }
        .dash-card-badge.alert { background:#fef2f2; color:#ef4444; }

        .info-card {
            background:#fff; border-radius:14px; padding:1rem 1.25rem; margin-bottom:0.75rem;
            box-shadow:0 1px 4px rgba(0,0,0,0.06); border:1px solid #f1f5f9;
        }
        .info-card h3 { font-size:0.9rem; color:#1e293b; margin-bottom:0.5rem; }
        .info-item { padding:0.5rem 0; border-bottom:1px solid #f8fafc; font-size:0.82rem; }
        .info-item:last-child { border-bottom:none; }
        .info-item strong { color:#1e293b; }
        .info-item .meta { color:#94a3b8; font-size:0.75rem; }

        .quick-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem; margin-bottom:1.25rem; }
        .quick-stat {
            background:#fff; border-radius:12px; padding:0.75rem; text-align:center;
            box-shadow:0 1px 4px rgba(0,0,0,0.06); border:1px solid #f1f5f9;
        }
        .quick-stat h4 { font-size:1.2rem; color:#1a365d; font-weight:700; }
        .quick-stat p { font-size:0.7rem; color:#94a3b8; margin-top:2px; }

        .app-nav {
            position:fixed; bottom:0; left:0; right:0; background:#fff; border-top:1px solid #e2e8f0;
            display:flex; justify-content:space-around; padding:0.5rem 0 calc(0.5rem + env(safe-area-inset-bottom));
            z-index:100; box-shadow:0 -2px 8px rgba(0,0,0,0.05);
        }
        .app-nav a {
            display:flex; flex-direction:column; align-items:center; gap:0.15rem;
            text-decoration:none; color:#94a3b8; font-size:0.65rem; font-weight:600; padding:0.25rem 0.75rem;
            transition:color 0.2s;
        }
        .app-nav a.active { color:#1a365d; }
        .app-nav a i { font-size:1.15rem; }

        .app-content { padding-bottom:5rem; }

        .empty-state { text-align:center; padding:1.5rem; color:#94a3b8; font-size:0.85rem; }

        @media(min-width:768px) {
            .quick-stats { grid-template-columns:repeat(4,1fr); }
        }
    </style>
</head>
<body>
    <!-- App Header -->
    <div class="app-header">
        <div>
            <h1><i class="fas fa-cross" style="margin-right:0.4rem;"></i> Holy Trinity Parish</h1>
            <div class="subtitle">Kabwe, Zambia</div>
        </div>
        <div class="header-actions">
            <?php if ($unreadNotifs > 0): ?>
            <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-bell"></i><span class="notif-badge"><?= $unreadNotifs ?></span></a>
            <?php else: ?>
            <a href="/holy-trinity/portal/dashboard.php"><i class="far fa-bell"></i></a>
            <?php endif; ?>
            <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user-circle"></i></a>
        </div>
    </div>

    <div class="app-content">
        <!-- Welcome -->
        <div class="welcome-card">
            <div class="welcome-avatar">
                <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
            </div>
            <div class="welcome-info">
                <h2>Welcome, <?= sanitize($user['first_name']) ?>!</h2>
                <p><i class="fas fa-circle" style="color:#22c55e; font-size:0.5rem; vertical-align:middle;"></i> <?= ucfirst(str_replace('_',' ',$userRole)) ?></p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat">
                <h4><?= count($upcomingEvents) ?></h4>
                <p>Upcoming Events</p>
            </div>
            <div class="quick-stat">
                <h4><?= count($massSchedules) ?></h4>
                <p>Mass Times</p>
            </div>
            <div class="quick-stat">
                <h4><?= count($latestAnnouncements) ?></h4>
                <p>Announcements</p>
            </div>
        </div>

        <!-- My Dashboards -->
        <div class="section-title"><i class="fas fa-th-large"></i> My Dashboards</div>
        <div class="dash-grid">
            <?php foreach ($dashboards as $d): ?>
            <a href="<?= $d['url'] ?>" class="dash-card">
                <?php if ($d['badge']): ?>
                    <span class="dash-card-badge <?= strpos($d['badge'],'pending') !== false ? 'alert' : '' ?>"><?= $d['badge'] ?></span>
                <?php endif; ?>
                <div class="dash-card-icon" style="background:<?= $d['gradient'] ?>;">
                    <i class="fas <?= $d['icon'] ?>"></i>
                </div>
                <div>
                    <div class="dash-card-title"><?= sanitize($d['title']) ?></div>
                    <div class="dash-card-desc"><?= sanitize($d['desc']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Announcements -->
        <?php if (!empty($latestAnnouncements)): ?>
        <div class="section-title"><i class="fas fa-bullhorn"></i> Latest Announcements</div>
        <div class="info-card">
            <?php foreach ($latestAnnouncements as $ann): ?>
            <div class="info-item">
                <strong><?= sanitize($ann['title']) ?></strong>
                <div class="meta"><?= formatDate($ann['created_at']) ?> &bull; <?= sanitize($ann['first_name'] . ' ' . $ann['last_name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Upcoming Events -->
        <?php if (!empty($upcomingEvents)): ?>
        <div class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming Events</div>
        <div class="info-card">
            <?php foreach ($upcomingEvents as $evt): ?>
            <div class="info-item">
                <strong><?= sanitize($evt['title']) ?></strong>
                <div class="meta"><i class="fas fa-calendar"></i> <?= formatDate($evt['event_date']) ?> <?= $evt['start_time'] ? '&bull; ' . formatTime($evt['start_time']) : '' ?> <?= $evt['location'] ? '&bull; <i class="fas fa-map-marker-alt"></i> ' . sanitize($evt['location']) : '' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Mass Schedule -->
        <?php if (!empty($massSchedules)): ?>
        <div class="section-title"><i class="fas fa-church"></i> Mass Schedule</div>
        <div class="info-card">
            <?php foreach ($massSchedules as $ms): ?>
            <div class="info-item">
                <strong><?= sanitize($ms['day_of_week']) ?></strong> — <?= formatTime($ms['start_time']) ?>
                <span class="meta"><?= sanitize($ms['mass_type'] ?? '') ?> <?= $ms['location'] ? '&bull; ' . sanitize($ms['location']) : '' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="section-title"><i class="fas fa-link"></i> Quick Actions</div>
        <div class="dash-grid">
            <a href="/holy-trinity/appointments/book.php" class="dash-card" style="flex-direction:row; align-items:center; gap:0.75rem;">
                <div class="dash-card-icon" style="background:linear-gradient(135deg,#0077b6,#0096c7); width:38px; height:38px; font-size:0.95rem;">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="dash-card-title" style="font-size:0.85rem;">Book Appointment</div>
            </a>
            <a href="/holy-trinity/donations/donate.php" class="dash-card" style="flex-direction:row; align-items:center; gap:0.75rem;">
                <div class="dash-card-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a); width:38px; height:38px; font-size:0.95rem;">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="dash-card-title" style="font-size:0.85rem;">Make Donation</div>
            </a>
            <a href="/holy-trinity/pages/dashboard.php" class="dash-card" style="flex-direction:row; align-items:center; gap:0.75rem;">
                <div class="dash-card-icon" style="background:linear-gradient(135deg,#d4a843,#b8860b); width:38px; height:38px; font-size:0.95rem;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="dash-card-title" style="font-size:0.85rem;">Parish Dashboard</div>
            </a>
            <a href="/holy-trinity/index.php" class="dash-card" style="flex-direction:row; align-items:center; gap:0.75rem;">
                <div class="dash-card-icon" style="background:linear-gradient(135deg,#64748b,#475569); width:38px; height:38px; font-size:0.95rem;">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="dash-card-title" style="font-size:0.85rem;">Visit Website</div>
            </a>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="app-nav">
        <a href="/holy-trinity/app/dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
        <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-user"></i> Portal</a>
        <a href="/holy-trinity/appointments/book.php"><i class="fas fa-calendar-plus"></i> Book</a>
        <a href="/holy-trinity/donations/donate.php"><i class="fas fa-heart"></i> Donate</a>
        <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>

    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
