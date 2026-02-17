<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();
$user = currentUser();

// Dashboard Stats
$totalParishioners = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'parishioner'")['cnt'];
$totalAppointments = $db->fetch("SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date >= CURDATE()")['cnt'];
$pendingAppointments = $db->fetch("SELECT COUNT(*) as cnt FROM appointments WHERE status = 'pending'")['cnt'];
$totalDonations = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed' AND MONTH(donation_date) = MONTH(CURDATE())")['total'];
$totalDepartments = $db->fetch("SELECT COUNT(*) as cnt FROM departments WHERE is_active = 1")['cnt'];
$totalMinistries = $db->fetch("SELECT COUNT(*) as cnt FROM ministries WHERE is_active = 1")['cnt'];
$totalSacraments = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records")['cnt'];
$totalEvents = $db->fetch("SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE() AND status = 'published'")['cnt'];

// Recent appointments
$recentAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name as user_first, u.last_name as user_last,
            p.first_name as prov_first, p.last_name as prov_last, d.name as dept_name
     FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     LEFT JOIN users p ON a.provider_id = p.id
     LEFT JOIN departments d ON a.department_id = d.id
     ORDER BY a.created_at DESC LIMIT 10"
);

// Recent donations
$recentDonations = $db->fetchAll(
    "SELECT d.*, dc.name as category_name FROM donations d
     LEFT JOIN donation_categories dc ON d.category_id = dc.id
     ORDER BY d.created_at DESC LIMIT 10"
);

// Recent registrations
$recentUsers = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 8");

// Donation by category this month
$donationByCategory = $db->fetchAll(
    "SELECT dc.name, COALESCE(SUM(d.amount),0) as total
     FROM donation_categories dc
     LEFT JOIN donations d ON d.category_id = dc.id AND d.payment_status = 'completed' AND MONTH(d.donation_date) = MONTH(CURDATE())
     GROUP BY dc.id, dc.name ORDER BY total DESC"
);
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
        <!-- Admin Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-cross"></i>
                <span>HTP Admin</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Overview</a>

                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments
                    <?php if ($pendingAppointments > 0): ?>
                        <span class="badge badge-warning" style="margin-left:auto;"><?= $pendingAppointments ?></span>
                    <?php endif; ?>
                </a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>

                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/ministries.php"><i class="fas fa-people-group"></i> Ministries</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <a href="/holy-trinity/admin/clergy.php"><i class="fas fa-user-tie"></i> Clergy</a>

                <div class="sidebar-section">Content</div>
                <a href="/holy-trinity/admin/sermons.php"><i class="fas fa-bible"></i> Sermons</a>
                <a href="/holy-trinity/admin/documents.php"><i class="fas fa-file-alt"></i> Documents</a>
                <a href="/holy-trinity/admin/mass-schedule.php"><i class="fas fa-clock"></i> Mass Schedule</a>

                <?php if (isAdmin()): ?>
                <div class="sidebar-section">System</div>
                <a href="/holy-trinity/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="/holy-trinity/admin/audit-log.php"><i class="fas fa-history"></i> Audit Log</a>
                <a href="/holy-trinity/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user_role'], ['priest', 'super_admin', 'admin'])): ?>
                <div class="sidebar-section">Dashboards</div>
                <a href="/holy-trinity/priest/dashboard.php"><i class="fas fa-church"></i> Priest Dashboard</a>
                <a href="/holy-trinity/pages/dashboard.php"><i class="fas fa-globe"></i> Public Dashboard</a>
                <?php endif; ?>
                <?php if ($_SESSION['user_role'] === 'department_head'): ?>
                <div class="sidebar-section">My Department</div>
                <a href="/holy-trinity/department/dashboard.php"><i class="fas fa-building"></i> Dept Dashboard</a>
                <?php endif; ?>

                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;">
                <i class="fas fa-bars"></i> Menu
            </button>

            <div class="dashboard-header">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p class="text-muted">Welcome back, <?= sanitize($user['first_name']) ?> &bull; <?= date('l, F j, Y') ?></p>
                </div>
                <div style="display:flex; gap:0.75rem; align-items:center;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                    <a href="/holy-trinity/admin/appointments.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-calendar-plus"></i> Manage Appointments
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($totalParishioners) ?></h3>
                        <p>Registered Parishioners</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?= $pendingAppointments ?></h3>
                        <p>Pending Appointments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="stat-info">
                        <h3>UGX <?= number_format($totalDonations) ?></h3>
                        <p>This Month's Donations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <h3><?= $totalEvents ?></h3>
                        <p>Upcoming Events</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-dove"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($totalSacraments) ?></h3>
                        <p>Sacramental Records</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-building"></i></div>
                    <div class="stat-info">
                        <h3><?= $totalDepartments ?></h3>
                        <p>Active Departments</p>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid-2">
                <!-- Pending Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Recent Appointments</h3>
                        <a href="/holy-trinity/admin/appointments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Parishioner</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAppointments)): ?>
                                    <tr><td colspan="5" class="text-center p-3">No appointments found</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($recentAppointments, 0, 5) as $apt): ?>
                                    <tr>
                                        <td><?= sanitize($apt['user_first'] . ' ' . $apt['user_last']) ?></td>
                                        <td><?= formatDate($apt['appointment_date'], 'M d') ?><br><small><?= formatTime($apt['start_time']) ?></small></td>
                                        <td><?= sanitize(substr($apt['reason'], 0, 20)) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : ($apt['status'] === 'completed' ? 'info' : 'error')) ?>">
                                                <?= ucfirst($apt['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/holy-trinity/admin/appointment-detail.php?id=<?= $apt['id'] ?>" class="btn btn-sm btn-outline">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Donations -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-hand-holding-heart"></i> Recent Donations</h3>
                        <a href="/holy-trinity/admin/donations.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Donor</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentDonations)): ?>
                                    <tr><td colspan="5" class="text-center p-3">No donations found</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($recentDonations, 0, 5) as $don): ?>
                                    <tr>
                                        <td><?= sanitize($don['donor_name'] ?? 'Anonymous') ?></td>
                                        <td><strong>UGX <?= number_format($don['amount']) ?></strong></td>
                                        <td><?= sanitize($don['category_name'] ?? 'General') ?></td>
                                        <td><?= formatDate($don['donation_date'], 'M d') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $don['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($don['payment_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Donation by Category & Recent Users -->
            <div class="grid-2 mt-3">
                <!-- Donations by Category -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Donations by Category (This Month)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($donationByCategory)): ?>
                            <p class="text-muted text-center">No donation data available</p>
                        <?php else: ?>
                            <?php
                            $maxDonation = max(array_column($donationByCategory, 'total')) ?: 1;
                            foreach ($donationByCategory as $dc):
                                $percentage = ($dc['total'] / $maxDonation) * 100;
                            ?>
                            <div style="margin-bottom:1rem;">
                                <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:0.3rem;">
                                    <span><?= sanitize($dc['name']) ?></span>
                                    <strong>UGX <?= number_format($dc['total']) ?></strong>
                                </div>
                                <div style="background:var(--light-gray); border-radius:50px; height:8px; overflow:hidden;">
                                    <div style="background:var(--gold); height:100%; width:<?= $percentage ?>%; border-radius:50px; transition:width 1s ease;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Recent Registrations</h3>
                        <a href="/holy-trinity/admin/users.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php foreach (array_slice($recentUsers, 0, 6) as $u): ?>
                        <div style="padding:0.75rem 1.5rem; border-bottom:1px solid var(--light-gray); display:flex; align-items:center; gap:1rem;">
                            <div style="width:40px; height:40px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.85rem;">
                                <?= strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)) ?>
                            </div>
                            <div style="flex:1;">
                                <strong style="font-size:0.9rem;"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                                <div class="text-muted" style="font-size:0.8rem;"><?= sanitize($u['email']) ?></div>
                            </div>
                            <span class="badge badge-primary"><?= ucfirst($u['role']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/holy-trinity/assets/js/main.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('sidebarToggle').style.display = 'inline-flex';
        }
        window.addEventListener('resize', function() {
            const btn = document.getElementById('sidebarToggle');
            if (btn) btn.style.display = window.innerWidth <= 1024 ? 'inline-flex' : 'none';
        });
    </script>
</body>
</html>
