<?php
$pageTitle = 'My Appointments';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $cancelId = intval($_POST['cancel_id']);
        $apt = $db->fetch("SELECT * FROM appointments WHERE id = ? AND user_id = ?", [$cancelId, $_SESSION['user_id']]);
        if ($apt && in_array($apt['status'], ['pending', 'approved'])) {
            $db->update('appointments', ['status' => 'cancelled'], 'id = ?', [$cancelId]);
            logAudit('appointment_cancelled', 'appointment', $cancelId);
            setFlash('success', 'Appointment cancelled successfully.');
            redirect('/holy-trinity/portal/appointments.php');
        }
    }
}

$filter = sanitize($_GET['filter'] ?? 'upcoming');
$where = "a.user_id = ?";
$params = [$_SESSION['user_id']];

if ($filter === 'upcoming') {
    $where .= " AND a.appointment_date >= CURDATE() AND a.status IN ('pending','approved')";
} elseif ($filter === 'past') {
    $where .= " AND (a.appointment_date < CURDATE() OR a.status IN ('completed','cancelled','declined'))";
}

$appointments = $db->fetchAll(
    "SELECT a.*, u.first_name as prov_first, u.last_name as prov_last, d.name as dept_name
     FROM appointments a
     LEFT JOIN users u ON a.provider_id = u.id
     LEFT JOIN departments d ON a.department_id = d.id
     WHERE {$where} ORDER BY a.appointment_date DESC, a.start_time DESC",
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
        <aside class="sidebar">
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Holy Trinity</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Main</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="/holy-trinity/portal/appointments.php" class="active"><i class="fas fa-calendar-check"></i> My Appointments</a>
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

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-calendar-check"></i> My Appointments</h1>
                    <p class="text-muted">View and manage your appointment history</p>
                </div>
                <a href="/holy-trinity/appointments/book.php" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Book New</a>
            </div>

            <!-- Filter Tabs -->
            <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem;">
                <a href="?filter=upcoming" class="btn btn-sm <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-outline' ?>">Upcoming</a>
                <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
                <a href="?filter=past" class="btn btn-sm <?= $filter === 'past' ? 'btn-primary' : 'btn-outline' ?>">Past</a>
            </div>

            <?php if (empty($appointments)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding:4rem;">
                        <i class="fas fa-calendar-alt" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                        <h3>No Appointments</h3>
                        <p class="text-muted">You don't have any <?= $filter !== 'all' ? $filter : '' ?> appointments.</p>
                        <a href="/holy-trinity/appointments/book.php" class="btn btn-primary mt-2"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php foreach ($appointments as $apt): ?>
                    <div class="card">
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                                <div style="flex:1;">
                                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                                        <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : ($apt['status'] === 'completed' ? 'info' : 'error')) ?>">
                                            <?= ucfirst($apt['status']) ?>
                                        </span>
                                        <code style="font-size:0.8rem; color:var(--text-light);"><?= $apt['reference_number'] ?></code>
                                    </div>
                                    <h3 style="font-family:var(--font-body); font-size:1.1rem; margin-bottom:0.5rem;"><?= sanitize($apt['reason']) ?></h3>
                                    <div style="display:flex; flex-wrap:wrap; gap:1.5rem; font-size:0.9rem; color:var(--gray);">
                                        <span><i class="fas fa-calendar" style="color:var(--gold);"></i> <?= formatDate($apt['appointment_date']) ?></span>
                                        <span><i class="fas fa-clock" style="color:var(--gold);"></i> <?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></span>
                                        <span><i class="fas fa-user" style="color:var(--gold);"></i> <?= sanitize($apt['prov_first'] . ' ' . $apt['prov_last']) ?></span>
                                        <?php if ($apt['dept_name']): ?>
                                            <span><i class="fas fa-building" style="color:var(--gold);"></i> <?= sanitize($apt['dept_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($apt['description']): ?>
                                        <p style="margin-top:0.5rem; font-size:0.9rem; color:var(--text-light);"><?= sanitize($apt['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:0.5rem;">
                                    <?php if (in_array($apt['status'], ['pending', 'approved']) && strtotime($apt['appointment_date']) >= strtotime('today')): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="cancel_id" value="<?= $apt['id'] ?>">
                                            <button class="btn btn-sm btn-accent"><i class="fas fa-times"></i> Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
