<?php
$pageTitle = 'Manage Donations';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

// Filters
$status = sanitize($_GET['status'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($status) { $where .= " AND d.payment_status = ?"; $params[] = $status; }
if ($category) { $where .= " AND d.category_id = ?"; $params[] = $category; }
if ($dateFrom) { $where .= " AND d.donation_date >= ?"; $params[] = $dateFrom; }
if ($dateTo) { $where .= " AND d.donation_date <= ?"; $params[] = $dateTo; }
if ($search) {
    $where .= " AND (d.donor_name LIKE ? OR d.transaction_ref LIKE ? OR d.donor_email LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s]);
}

$donations = $db->fetchAll(
    "SELECT d.*, dc.name as category_name FROM donations d
     LEFT JOIN donation_categories dc ON d.category_id = dc.id
     WHERE {$where} ORDER BY d.donation_date DESC, d.created_at DESC",
    $params
);

$categories = $db->fetchAll("SELECT * FROM donation_categories ORDER BY name");
$totalAmount = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed'")['total'];
$monthlyAmount = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed' AND MONTH(donation_date) = MONTH(CURDATE()) AND YEAR(donation_date) = YEAR(CURDATE())")['total'];
$todayAmount = $db->fetch("SELECT COALESCE(SUM(amount),0) as total FROM donations WHERE payment_status = 'completed' AND donation_date = CURDATE()")['total'];
$donorCount = $db->fetch("SELECT COUNT(DISTINCT COALESCE(user_id, donor_email)) as cnt FROM donations WHERE payment_status = 'completed'")['cnt'];
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
                <a href="/holy-trinity/admin/donations.php" class="active"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-hand-holding-heart"></i> Donations</h1>
                    <p class="text-muted">Track and manage parish donations</p>
                </div>
                <button onclick="printContent('donationsTable')" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print Report</button>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($totalAmount) ?></h3><p>Total Donations</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($monthlyAmount) ?></h3><p>This Month</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-sun"></i></div>
                    <div class="stat-info"><h3>ZMW <?= number_format($todayAmount) ?></h3><p>Today</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= number_format($donorCount) ?></h3><p>Unique Donors</p></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:180px;">
                            <label style="font-size:0.8rem;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Donor, reference..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
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
                        <a href="/holy-trinity/admin/donations.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Donations Table -->
            <div class="card" id="donationsTable">
                <div class="card-header">
                    <h3>Donation Records (<?= count($donations) ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Donor</th>
                                <th>Amount</th>
                                <th>Category</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($donations)): ?>
                                <tr><td colspan="7" class="text-center p-3">No donations found</td></tr>
                            <?php else: ?>
                                <?php foreach ($donations as $don): ?>
                                <tr>
                                    <td><code style="font-size:0.8rem;"><?= $don['transaction_ref'] ?></code></td>
                                    <td>
                                        <?= sanitize($don['donor_name'] ?? 'Anonymous') ?>
                                        <?php if ($don['donor_email']): ?><br><small class="text-muted"><?= sanitize($don['donor_email']) ?></small><?php endif; ?>
                                    </td>
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
