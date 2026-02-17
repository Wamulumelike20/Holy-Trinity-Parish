<?php
$pageTitle = 'My Donations';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();

$donations = $db->fetchAll(
    "SELECT d.*, dc.name as category_name FROM donations d
     LEFT JOIN donation_categories dc ON d.category_id = dc.id
     WHERE d.user_id = ? ORDER BY d.donation_date DESC",
    [$_SESSION['user_id']]
);

$totalDonated = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE user_id = ? AND payment_status = 'completed'", [$_SESSION['user_id']])['total'];
$thisYear = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE user_id = ? AND payment_status = 'completed' AND YEAR(donation_date) = YEAR(CURDATE())", [$_SESSION['user_id']])['total'];
$thisMonth = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE user_id = ? AND payment_status = 'completed' AND MONTH(donation_date) = MONTH(CURDATE()) AND YEAR(donation_date) = YEAR(CURDATE())", [$_SESSION['user_id']])['total'];
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Holy Trinity</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Main</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="/holy-trinity/portal/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="/holy-trinity/portal/donations.php" class="active"><i class="fas fa-hand-holding-heart"></i> My Donations</a>
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

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-hand-holding-heart"></i> My Donations</h1>
                    <p class="text-muted">Track your giving history</p>
                </div>
                <a href="/holy-trinity/donations/donate.php" class="btn btn-primary"><i class="fas fa-donate"></i> Donate Now</a>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($totalDonated) ?></h3><p>Total Donated</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($thisYear) ?></h3><p>This Year</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($thisMonth) ?></h3><p>This Month</p></div>
                </div>
            </div>

            <!-- Donations Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Donation History</h3>
                    <button onclick="printContent('donationsList')" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="table-responsive" id="donationsList">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Category</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($donations)): ?>
                                <tr><td colspan="6" class="text-center p-3">
                                    <i class="fas fa-hand-holding-heart" style="font-size:2rem; color:var(--gold); display:block; margin-bottom:0.5rem;"></i>
                                    No donations yet. <a href="/holy-trinity/donations/donate.php">Make your first donation</a>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($donations as $don): ?>
                                <tr>
                                    <td><code style="font-size:0.8rem;"><?= $don['transaction_ref'] ?></code></td>
                                    <td><strong>ZMW <?= number_format($don['amount']) ?></strong></td>
                                    <td><?= sanitize($don['category_name'] ?? 'General') ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $don['payment_method'])) ?></td>
                                    <td><?= formatDate($don['donation_date']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $don['payment_status'] === 'completed' ? 'success' : ($don['payment_status'] === 'pending' ? 'warning' : 'error') ?>">
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
