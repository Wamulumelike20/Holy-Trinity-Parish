<?php
$pageTitle = 'My Sacramental Records';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$user = currentUser();

$records = $db->fetchAll(
    "SELECT * FROM sacramental_records WHERE (person_first_name LIKE ? AND person_last_name LIKE ?) OR (spouse_name LIKE ?) ORDER BY sacrament_date DESC",
    ['%' . $user['first_name'] . '%', '%' . $user['last_name'] . '%', '%' . $user['first_name'] . ' ' . $user['last_name'] . '%']
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
                <a href="/holy-trinity/portal/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="/holy-trinity/portal/donations.php"><i class="fas fa-hand-holding-heart"></i> My Donations</a>
                <div class="sidebar-section">Faith Life</div>
                <a href="/holy-trinity/portal/sacraments.php" class="active"><i class="fas fa-dove"></i> Sacramental Records</a>
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
                    <h1><i class="fas fa-dove"></i> Sacramental Records</h1>
                    <p class="text-muted">View your sacramental records on file</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body" style="background:rgba(212,168,67,0.08); border-left:4px solid var(--gold);">
                    <p style="margin:0; font-size:0.9rem;"><i class="fas fa-info-circle" style="color:var(--gold);"></i> Records shown are matched by your registered name. If you don't see your records, please contact the Parish Office for assistance.</p>
                </div>
            </div>

            <?php if (empty($records)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding:4rem;">
                        <i class="fas fa-dove" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                        <h3>No Records Found</h3>
                        <p class="text-muted">No sacramental records were found matching your name. Please contact the Parish Office if you believe this is an error.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php foreach ($records as $rec): ?>
                    <div class="card">
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                                <div>
                                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                                        <span class="badge badge-<?= $rec['record_type'] === 'baptism' ? 'info' : ($rec['record_type'] === 'confirmation' ? 'success' : ($rec['record_type'] === 'marriage' ? 'gold' : 'primary')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $rec['record_type'])) ?>
                                        </span>
                                        <code style="font-size:0.8rem; color:var(--text-light);"><?= $rec['reference_number'] ?></code>
                                    </div>
                                    <h3 style="font-family:var(--font-body); font-size:1.1rem; margin-bottom:0.5rem;">
                                        <?= sanitize($rec['person_first_name'] . ' ' . $rec['person_last_name']) ?>
                                    </h3>
                                    <div style="display:flex; flex-wrap:wrap; gap:1.5rem; font-size:0.9rem; color:var(--gray);">
                                        <span><i class="fas fa-calendar" style="color:var(--gold);"></i> <?= formatDate($rec['sacrament_date']) ?></span>
                                        <span><i class="fas fa-map-marker-alt" style="color:var(--gold);"></i> <?= sanitize($rec['place'] ?? 'Holy Trinity Parish') ?></span>
                                        <?php if ($rec['minister_name']): ?>
                                            <span><i class="fas fa-user" style="color:var(--gold);"></i> <?= sanitize($rec['minister_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($rec['sponsor1_name'] || $rec['sponsor2_name']): ?>
                                        <div style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-light);">
                                            <strong>Sponsors:</strong>
                                            <?= sanitize(implode(', ', array_filter([$rec['sponsor1_name'], $rec['sponsor2_name']]))) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($rec['spouse_name']): ?>
                                        <div style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-light);">
                                            <strong>Spouse:</strong> <?= sanitize($rec['spouse_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="/holy-trinity/admin/sacrament-certificate.php?id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline" target="_blank">
                                    <i class="fas fa-file-pdf"></i> View Certificate
                                </a>
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
