<?php
$pageTitle = 'My Portal';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$user = currentUser();

// Stats
$appointmentCount = $db->fetch("SELECT COUNT(*) as cnt FROM appointments WHERE user_id = ?", [$user['id']])['cnt'];
$upcomingAppointments = $db->fetch("SELECT COUNT(*) as cnt FROM appointments WHERE user_id = ? AND appointment_date >= CURDATE() AND status IN ('pending','approved')", [$user['id']])['cnt'];
$donationTotal = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE user_id = ? AND payment_status = 'completed'", [$user['id']])['total'];
$ministryCount = $db->fetch("SELECT COUNT(*) as cnt FROM ministry_members WHERE user_id = ?", [$user['id']])['cnt'];

// Recent appointments
$recentAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name as prov_first, u.last_name as prov_last
     FROM appointments a LEFT JOIN users u ON a.provider_id = u.id
     WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5",
    [$user['id']]
);

// Recent donations
$recentDonations = $db->fetchAll(
    "SELECT d.*, dc.name as category_name FROM donations d
     LEFT JOIN donation_categories dc ON d.category_id = dc.id
     WHERE d.user_id = ? ORDER BY d.created_at DESC LIMIT 5",
    [$user['id']]
);

// My ministries
$myMinistries = $db->fetchAll(
    "SELECT m.*, mm.role as member_role FROM ministry_members mm
     JOIN ministries m ON mm.ministry_id = m.id WHERE mm.user_id = ?",
    [$user['id']]
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-cross"></i>
                <span>Holy Trinity</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Main</div>
                <a href="/holy-trinity/portal/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="/holy-trinity/portal/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="/holy-trinity/portal/donations.php"><i class="fas fa-hand-holding-heart"></i> My Donations</a>
                <div class="sidebar-section">Faith Life</div>
                <a href="/holy-trinity/portal/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/portal/ministries.php"><i class="fas fa-people-group"></i> My Ministries</a>
                <div class="sidebar-section">Quick Actions</div>
                <a href="/holy-trinity/appointments/book.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="/holy-trinity/donations/donate.php"><i class="fas fa-donate"></i> Make Donation</a>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> Visit Website</a>
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
                    <h1>Welcome, <?= sanitize($user['first_name']) ?>!</h1>
                    <p class="text-muted">Here's an overview of your parish activities</p>
                </div>
                <div>
                    <span class="badge badge-primary" style="font-size:0.85rem; padding:0.5rem 1rem;">
                        <i class="fas fa-user"></i> <?= ucfirst($user['role']) ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?= $upcomingAppointments ?></h3>
                        <p>Upcoming Appointments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="stat-info">
                        <h3>UGX <?= number_format($donationTotal) ?></h3>
                        <p>Total Donations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-people-group"></i></div>
                    <div class="stat-info">
                        <h3><?= $ministryCount ?></h3>
                        <p>Ministries Joined</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <h3><?= $appointmentCount ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid-2">
                <!-- Recent Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Recent Appointments</h3>
                        <a href="/holy-trinity/portal/appointments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentAppointments)): ?>
                            <div class="text-center p-3">
                                <p class="text-muted">No appointments yet</p>
                                <a href="/holy-trinity/appointments/book.php" class="btn btn-sm btn-primary">Book Now</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentAppointments as $apt): ?>
                            <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--light-gray);">
                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                                    <div>
                                        <strong style="font-size:0.95rem;"><?= sanitize($apt['reason']) ?></strong>
                                        <div class="text-muted" style="font-size:0.8rem;">
                                            <i class="fas fa-calendar"></i> <?= formatDate($apt['appointment_date']) ?> at <?= formatTime($apt['start_time']) ?>
                                            &bull; <i class="fas fa-user"></i> <?= sanitize($apt['prov_first'] . ' ' . $apt['prov_last']) ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : ($apt['status'] === 'completed' ? 'info' : 'error')) ?>">
                                        <?= ucfirst($apt['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Donations -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-hand-holding-heart"></i> Recent Donations</h3>
                        <a href="/holy-trinity/portal/donations.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentDonations)): ?>
                            <div class="text-center p-3">
                                <p class="text-muted">No donations yet</p>
                                <a href="/holy-trinity/donations/donate.php" class="btn btn-sm btn-primary">Donate Now</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentDonations as $don): ?>
                            <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--light-gray);">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <strong style="font-size:0.95rem;">UGX <?= number_format($don['amount']) ?></strong>
                                        <div class="text-muted" style="font-size:0.8rem;">
                                            <?= sanitize($don['category_name'] ?? 'General') ?> &bull; <?= formatDate($don['donation_date']) ?>
                                        </div>
                                    </div>
                                    <span class="badge badge-<?= $don['payment_status'] === 'completed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($don['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Ministries -->
            <?php if (!empty($myMinistries)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3><i class="fas fa-people-group"></i> My Ministries</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:1rem;">
                        <?php foreach ($myMinistries as $min): ?>
                        <div style="padding:1rem; border:1px solid var(--light-gray); border-radius:var(--radius); display:flex; align-items:center; gap:1rem;">
                            <div style="width:48px; height:48px; border-radius:var(--radius); background:rgba(212,168,67,0.15); display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-hands-praying" style="color:var(--gold);"></i>
                            </div>
                            <div>
                                <strong style="font-size:0.95rem;"><?= sanitize($min['name']) ?></strong>
                                <div class="text-muted" style="font-size:0.8rem;">Role: <?= ucfirst(sanitize($min['member_role'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/holy-trinity/assets/js/main.js"></script>
    <script>
        // Show sidebar toggle on mobile
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
