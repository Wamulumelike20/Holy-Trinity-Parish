<?php
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

// Monthly donation trends (last 12 months)
$donationTrends = $db->fetchAll(
    "SELECT DATE_FORMAT(donation_date, '%Y-%m') as month, DATE_FORMAT(donation_date, '%b %Y') as label,
            COALESCE(SUM(amount),0) as total, COUNT(*) as count
     FROM donations WHERE payment_status = 'completed' AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month ASC"
);

// Donation by category
$donationByCategory = $db->fetchAll(
    "SELECT dc.name, COALESCE(SUM(d.amount),0) as total, COUNT(d.id) as count
     FROM donation_categories dc
     LEFT JOIN donations d ON d.category_id = dc.id AND d.payment_status = 'completed'
     GROUP BY dc.id, dc.name ORDER BY total DESC"
);

// Appointment stats
$appointmentStats = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM appointments GROUP BY status"
);

// Sacrament stats by year
$sacramentByYear = $db->fetchAll(
    "SELECT YEAR(sacrament_date) as year, record_type, COUNT(*) as count
     FROM sacramental_records WHERE YEAR(sacrament_date) >= YEAR(CURDATE()) - 3
     GROUP BY year, record_type ORDER BY year DESC, record_type"
);

// Registration trends
$registrationTrends = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, DATE_FORMAT(created_at, '%b %Y') as label, COUNT(*) as count
     FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month ASC"
);

// Department membership
$deptMembership = $db->fetchAll(
    "SELECT d.name, COUNT(dm.id) as member_count FROM departments d
     LEFT JOIN department_members dm ON dm.department_id = d.id
     GROUP BY d.id, d.name ORDER BY member_count DESC"
);

// Ministry membership
$ministryMembership = $db->fetchAll(
    "SELECT m.name, COUNT(mm.id) as member_count FROM ministries m
     LEFT JOIN ministry_members mm ON mm.ministry_id = m.id
     WHERE m.is_active = 1 GROUP BY m.id, m.name ORDER BY member_count DESC"
);

// Overall totals
$totalUsers = $db->fetch("SELECT COUNT(*) as cnt FROM users")['cnt'];
$totalDonations = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed'")['total'];
$totalAppointments = $db->fetch("SELECT COUNT(*) as cnt FROM appointments")['cnt'];
$totalSacraments = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records")['cnt'];
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
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">System</div>
                <a href="/holy-trinity/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="/holy-trinity/admin/audit-log.php"><i class="fas fa-history"></i> Audit Log</a>
                <a href="/holy-trinity/admin/reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                    <p class="text-muted">Comprehensive parish statistics and insights</p>
                </div>
                <button onclick="window.print()" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print Report</button>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalUsers) ?></h3><p>Total Users</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                    <div class="stat-info"><h3>UGX <?= number_format($totalDonations) ?></h3><p>Total Donations</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalAppointments) ?></h3><p>Total Appointments</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-dove"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalSacraments) ?></h3><p>Sacramental Records</p></div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Donation Trends -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Monthly Donations (Last 12 Months)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($donationTrends)): ?>
                            <p class="text-muted text-center">No donation data available</p>
                        <?php else: ?>
                            <?php $maxAmount = max(array_column($donationTrends, 'total')) ?: 1; ?>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($donationTrends as $dt): ?>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.2rem;">
                                        <span><?= $dt['label'] ?></span>
                                        <strong>UGX <?= number_format($dt['total']) ?> (<?= $dt['count'] ?>)</strong>
                                    </div>
                                    <div style="background:var(--light-gray); border-radius:50px; height:6px; overflow:hidden;">
                                        <div style="background:linear-gradient(90deg, var(--primary), var(--gold)); height:100%; width:<?= ($dt['total'] / $maxAmount) * 100 ?>%; border-radius:50px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Donations by Category -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Donations by Category</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($donationByCategory)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <?php
                            $colors = ['#1a3a5c', '#d4a843', '#8b1a1a', '#10b981', '#3b82f6', '#7c3aed'];
                            $totalCatDonations = array_sum(array_column($donationByCategory, 'total')) ?: 1;
                            foreach ($donationByCategory as $i => $dc):
                                $pct = round(($dc['total'] / $totalCatDonations) * 100, 1);
                                $color = $colors[$i % count($colors)];
                            ?>
                            <div style="margin-bottom:1rem;">
                                <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:0.3rem;">
                                    <span><span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:<?= $color ?>; margin-right:0.5rem;"></span><?= sanitize($dc['name']) ?></span>
                                    <strong>UGX <?= number_format($dc['total']) ?> (<?= $pct ?>%)</strong>
                                </div>
                                <div style="background:var(--light-gray); border-radius:50px; height:8px; overflow:hidden;">
                                    <div style="background:<?= $color ?>; height:100%; width:<?= $pct ?>%; border-radius:50px;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Appointment Status -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Appointment Status</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointmentStats)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:1rem;">
                                <?php foreach ($appointmentStats as $as): ?>
                                <div style="text-align:center; padding:1rem; background:var(--off-white); border-radius:var(--radius);">
                                    <div style="font-size:1.75rem; font-weight:700; color:var(--primary);"><?= $as['count'] ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-light); text-transform:uppercase;"><?= ucfirst($as['status']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registration Trends -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> New Registrations (Last 12 Months)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registrationTrends)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <?php $maxReg = max(array_column($registrationTrends, 'count')) ?: 1; ?>
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php foreach ($registrationTrends as $rt): ?>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.2rem;">
                                        <span><?= $rt['label'] ?></span>
                                        <strong><?= $rt['count'] ?> users</strong>
                                    </div>
                                    <div style="background:var(--light-gray); border-radius:50px; height:6px; overflow:hidden;">
                                        <div style="background:var(--primary); height:100%; width:<?= ($rt['count'] / $maxReg) * 100 ?>%; border-radius:50px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ministry Membership -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-people-group"></i> Ministry Membership</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ministryMembership)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <?php $maxMem = max(array_column($ministryMembership, 'member_count')) ?: 1; ?>
                            <?php foreach ($ministryMembership as $mm): ?>
                            <div style="margin-bottom:0.75rem;">
                                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.2rem;">
                                    <span><?= sanitize($mm['name']) ?></span>
                                    <strong><?= $mm['member_count'] ?> members</strong>
                                </div>
                                <div style="background:var(--light-gray); border-radius:50px; height:6px; overflow:hidden;">
                                    <div style="background:var(--gold); height:100%; width:<?= ($mm['member_count'] / $maxMem) * 100 ?>%; border-radius:50px;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sacraments by Year -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-dove"></i> Sacraments by Year</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sacramentByYear)): ?>
                            <p class="text-muted text-center">No data available</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr><th>Year</th><th>Type</th><th>Count</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sacramentByYear as $sy): ?>
                                        <tr>
                                            <td><?= $sy['year'] ?></td>
                                            <td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', $sy['record_type'])) ?></span></td>
                                            <td><strong><?= $sy['count'] ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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
