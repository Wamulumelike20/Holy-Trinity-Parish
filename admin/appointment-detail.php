<?php
$pageTitle = 'Appointment Details';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();
$aptId = intval($_GET['id'] ?? 0);

if (!$aptId) {
    setFlash('error', 'Appointment not found.');
    redirect('/holy-trinity/admin/appointments.php');
}

$apt = $db->fetch(
    "SELECT a.*, u.first_name as user_first, u.last_name as user_last, u.email as user_email, u.phone as user_phone,
            p.first_name as prov_first, p.last_name as prov_last, d.name as dept_name
     FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     LEFT JOIN users p ON a.provider_id = p.id
     LEFT JOIN departments d ON a.department_id = d.id
     WHERE a.id = ?",
    [$aptId]
);

if (!$apt) {
    setFlash('error', 'Appointment not found.');
    redirect('/holy-trinity/admin/appointments.php');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['action'] ?? '');
    $notes = sanitize($_POST['admin_notes'] ?? '');

    if (in_array($action, ['approve', 'decline', 'complete', 'cancel'])) {
        $statusMap = ['approve' => 'approved', 'decline' => 'declined', 'complete' => 'completed', 'cancel' => 'cancelled'];
        $db->update('appointments', [
            'status' => $statusMap[$action],
            'admin_notes' => $notes ?: $apt['admin_notes'],
        ], 'id = ?', [$aptId]);
        logAudit("appointment_{$action}", 'appointment', $aptId);
        setFlash('success', "Appointment {$statusMap[$action]} successfully.");
        redirect('/holy-trinity/admin/appointment-detail.php?id=' . $aptId);
    } elseif ($action === 'add_notes') {
        $db->update('appointments', ['admin_notes' => $notes], 'id = ?', [$aptId]);
        setFlash('success', 'Notes updated.');
        redirect('/holy-trinity/admin/appointment-detail.php?id=' . $aptId);
    }
}
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Admin</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
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
                    <h1><i class="fas fa-calendar-check"></i> Appointment Details</h1>
                    <p class="text-muted">Reference: <?= $apt['reference_number'] ?></p>
                </div>
                <a href="/holy-trinity/admin/appointments.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
            </div>

            <div class="grid-2">
                <!-- Appointment Info -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Appointment Information</h3>
                        <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : ($apt['status'] === 'completed' ? 'info' : 'error')) ?>" style="font-size:0.85rem; padding:0.4rem 1rem;">
                            <?= ucfirst($apt['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div style="display:flex; flex-direction:column; gap:1rem; font-size:0.95rem;">
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-hashtag" style="color:var(--gold); width:20px;"></i> Reference</span>
                                <strong><code><?= $apt['reference_number'] ?></code></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-calendar" style="color:var(--gold); width:20px;"></i> Date</span>
                                <strong><?= formatDate($apt['appointment_date'], 'l, F j, Y') ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-clock" style="color:var(--gold); width:20px;"></i> Time</span>
                                <strong><?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-clipboard-list" style="color:var(--gold); width:20px;"></i> Reason</span>
                                <strong><?= sanitize($apt['reason']) ?></strong>
                            </div>
                            <?php if ($apt['dept_name']): ?>
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-building" style="color:var(--gold); width:20px;"></i> Department</span>
                                <strong><?= sanitize($apt['dept_name']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:space-between; padding-bottom:0.75rem; border-bottom:1px solid var(--light-gray);">
                                <span class="text-muted"><i class="fas fa-user-tie" style="color:var(--gold); width:20px;"></i> Provider</span>
                                <strong><?= sanitize($apt['prov_first'] . ' ' . $apt['prov_last']) ?></strong>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted"><i class="fas fa-calendar-plus" style="color:var(--gold); width:20px;"></i> Booked On</span>
                                <strong><?= formatDate($apt['created_at'], 'M d, Y g:i A') ?></strong>
                            </div>
                        </div>

                        <?php if ($apt['description']): ?>
                        <div style="margin-top:1.5rem; padding:1rem; background:var(--off-white); border-radius:var(--radius);">
                            <strong style="font-size:0.85rem; color:var(--text-light);">Additional Details:</strong>
                            <p style="margin-top:0.5rem;"><?= nl2br(sanitize($apt['description'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($apt['document_path']): ?>
                        <div style="margin-top:1rem;">
                            <a href="/holy-trinity/uploads/appointments/<?= sanitize($apt['document_path']) ?>" class="btn btn-sm btn-outline" target="_blank">
                                <i class="fas fa-paperclip"></i> View Attached Document
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Parishioner Info & Actions -->
                <div>
                    <!-- Parishioner Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Parishioner</h3>
                        </div>
                        <div class="card-body">
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                                <div style="width:56px; height:56px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem;">
                                    <?= strtoupper(substr($apt['user_first'],0,1) . substr($apt['user_last'],0,1)) ?>
                                </div>
                                <div>
                                    <strong style="font-size:1.1rem;"><?= sanitize($apt['user_first'] . ' ' . $apt['user_last']) ?></strong>
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        <i class="fas fa-envelope"></i> <?= sanitize($apt['user_email']) ?>
                                    </div>
                                    <?php if ($apt['user_phone']): ?>
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        <i class="fas fa-phone"></i> <?= sanitize($apt['user_phone']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if (in_array($apt['status'], ['pending', 'approved'])): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                <?php if ($apt['status'] === 'pending'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-primary btn-block"><i class="fas fa-check"></i> Approve Appointment</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button class="btn btn-accent btn-block"><i class="fas fa-times"></i> Decline Appointment</button>
                                </form>
                                <?php elseif ($apt['status'] === 'approved'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button class="btn btn-primary btn-block"><i class="fas fa-check-double"></i> Mark as Completed</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Cancel this appointment?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-outline btn-block" style="color:var(--error); border-color:var(--error);"><i class="fas fa-ban"></i> Cancel Appointment</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Admin Notes -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sticky-note"></i> Private Notes</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="add_notes">
                                <div class="form-group">
                                    <textarea name="admin_notes" class="form-control" rows="4" placeholder="Add private notes about this appointment..."><?= sanitize($apt['admin_notes'] ?? '') ?></textarea>
                                    <div class="form-text">These notes are only visible to staff.</div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save Notes</button>
                            </form>
                        </div>
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

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
