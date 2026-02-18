<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['parish_executive', 'priest', 'super_admin', 'admin'])) {
    setFlash('error', 'Parish Executive access required.');
    redirect('/holy-trinity/index.php');
}

$db = Database::getInstance();
$pageTitle = 'Parish Executive Dashboard';

// Staff stats
$totalStaff = $db->fetch("SELECT COUNT(*) as cnt FROM staff_profiles WHERE is_active = 1")['cnt'] ?? 0;
$guards = $db->fetch("SELECT COUNT(*) as cnt FROM staff_profiles WHERE staff_type = 'guard' AND is_active = 1")['cnt'] ?? 0;
$helpers = $db->fetch("SELECT COUNT(*) as cnt FROM staff_profiles WHERE staff_type = 'house_helper' AND is_active = 1")['cnt'] ?? 0;
$workers = $db->fetch("SELECT COUNT(*) as cnt FROM staff_profiles WHERE staff_type = 'general_worker' AND is_active = 1")['cnt'] ?? 0;

// Today's attendance
$todayAttendance = $db->fetchAll(
    "SELECT sa.*, u.first_name, u.last_name, u.role, sp.employee_id, sp.position_title
     FROM staff_attendance sa JOIN users u ON sa.staff_user_id = u.id
     LEFT JOIN staff_profiles sp ON sp.user_id = sa.staff_user_id
     WHERE DATE(sa.clock_in) = CURDATE() ORDER BY sa.clock_in DESC"
);
$clockedInCount = count(array_filter($todayAttendance, fn($a) => !$a['clock_out']));

// Pending leave requests
$pendingLeave = $db->fetchAll(
    "SELECT lr.*, u.first_name, u.last_name, u.role, sp.position_title
     FROM leave_requests lr JOIN users u ON lr.staff_user_id = u.id
     LEFT JOIN staff_profiles sp ON sp.user_id = lr.staff_user_id
     WHERE lr.status = 'pending' ORDER BY lr.created_at ASC"
);

// Pending purchase requests
$pendingPurchases = $db->fetchAll(
    "SELECT pr.*, u.first_name, u.last_name FROM purchase_requests pr
     JOIN users u ON pr.requested_by = u.id WHERE pr.status IN ('submitted','under_review') ORDER BY pr.created_at DESC LIMIT 10"
);

// Pending budgets
$pendingBudgets = $db->fetchAll(
    "SELECT b.*, u.first_name, u.last_name FROM budgets b
     JOIN users u ON b.submitted_by = u.id WHERE b.status IN ('submitted','under_review') ORDER BY b.created_at DESC LIMIT 10"
);

// Recent reports sent to executive
$recentReports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name FROM reports r
     JOIN users u ON r.submitted_by = u.id WHERE r.recipient_type IN ('parish_executive','both') AND r.status = 'submitted'
     ORDER BY r.created_at DESC LIMIT 10"
);

// Recent document submissions
$recentDocs = $db->fetchAll(
    "SELECT ds.*, u.first_name, u.last_name FROM document_submissions ds
     JOIN users u ON ds.submitted_by = u.id WHERE ds.recipient_type IN ('parish_executive','both') AND ds.status = 'pending'
     ORDER BY ds.created_at DESC LIMIT 10"
);

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $leaveId = intval($_POST['leave_id'] ?? 0);
        $action = $_POST['leave_action'];
        $notes = sanitize($_POST['approver_notes'] ?? '');

        if (in_array($action, ['approved', 'rejected']) && $leaveId > 0) {
            $db->update('leave_requests', [
                'status' => $action,
                'approved_by' => $_SESSION['user_id'],
                'approver_notes' => $notes,
                'approved_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$leaveId]);

            $leave = $db->fetch("SELECT staff_user_id, leave_type, days_requested FROM leave_requests WHERE id = ?", [$leaveId]);
            if ($leave) {
                sendNotification(
                    'Leave Request ' . ucfirst($action),
                    'Your ' . str_replace('_', ' ', $leave['leave_type']) . ' leave request (' . $leave['days_requested'] . ' days) has been ' . $action . '.',
                    $action === 'approved' ? 'success' : 'warning',
                    '/holy-trinity/staff/leave.php',
                    $leave['staff_user_id']
                );
            }
            setFlash('success', 'Leave request ' . $action . ' successfully.');
        }
    }
    redirect('/holy-trinity/executive/dashboard.php');
}

// Handle purchase request approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $prId = intval($_POST['purchase_id'] ?? 0);
        $action = $_POST['purchase_action'];
        $notes = sanitize($_POST['approver_notes'] ?? '');

        if (in_array($action, ['approved', 'rejected']) && $prId > 0) {
            $db->update('purchase_requests', [
                'status' => $action,
                'approved_by' => $_SESSION['user_id'],
                'approver_notes' => $notes,
                'approved_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$prId]);
            setFlash('success', 'Purchase request ' . $action . '.');
        }
    }
    redirect('/holy-trinity/executive/dashboard.php');
}

// Handle budget approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget_action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bId = intval($_POST['budget_id'] ?? 0);
        $action = $_POST['budget_action'];
        $notes = sanitize($_POST['approver_notes'] ?? '');

        if (in_array($action, ['approved', 'rejected']) && $bId > 0) {
            $db->update('budgets', [
                'status' => $action,
                'approved_by' => $_SESSION['user_id'],
                'approver_notes' => $notes,
                'approved_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$bId]);
            setFlash('success', 'Budget ' . $action . '.');
        }
    }
    redirect('/holy-trinity/executive/dashboard.php');
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
    <style>
        .exec-banner { background:linear-gradient(135deg,#0f172a,#1e293b); color:#fff; padding:1.5rem 2rem; border-radius:12px; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .exec-banner h1 { font-family:'Cinzel',serif; font-size:1.4rem; margin:0; }
        .exec-banner p { margin:0.25rem 0 0; opacity:0.7; font-size:0.9rem; }
        .approval-card { border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; margin-bottom:0.75rem; background:#fff; transition:all 0.2s; }
        .approval-card:hover { box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .approval-actions { display:flex; gap:0.5rem; margin-top:0.75rem; }
        .approval-actions form { display:inline; }
        .btn-approve { background:#22c55e; color:#fff; border:none; padding:0.4rem 1rem; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer; }
        .btn-reject { background:#ef4444; color:#fff; border:none; padding:0.4rem 1rem; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer; }
        @media print { .no-print{display:none!important;} .sidebar{display:none!important;} .dashboard-layout{display:block!important;} }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar no-print" style="background:linear-gradient(135deg,#0f172a,#1e293b);">
            <div class="sidebar-brand" style="background:rgba(255,255,255,0.08);">
                <i class="fas fa-user-tie"></i>
                <span>Parish Executive</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Overview</div>
                <a href="/holy-trinity/executive/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/holy-trinity/executive/staff-attendance.php"><i class="fas fa-clock"></i> Staff Attendance</a>
                <a href="/holy-trinity/executive/leave-management.php"><i class="fas fa-calendar-minus"></i> Leave Management</a>
                <div class="sidebar-section">Finance</div>
                <a href="/holy-trinity/executive/purchase-requests.php"><i class="fas fa-shopping-cart"></i> Purchase Requests</a>
                <a href="/holy-trinity/executive/budgets.php"><i class="fas fa-file-invoice"></i> Budgets</a>
                <a href="/holy-trinity/executive/payroll.php"><i class="fas fa-money-bill-wave"></i> Payroll</a>
                <div class="sidebar-section">Communications</div>
                <a href="/holy-trinity/executive/reports.php"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="/holy-trinity/executive/documents.php"><i class="fas fa-folder-open"></i> Documents</a>
                <div class="sidebar-section">Navigation</div>
                <?php if (in_array($userRole, ['priest', 'super_admin', 'admin'])): ?>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                <?php endif; ?>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <?php foreach (['success','error','warning','info'] as $type):
                $msg = getFlash($type); if ($msg): ?>
            <div class="flash-message flash-<?= $type ?>" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px;">
                <i class="fas fa-<?= $type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $msg ?>
                <button onclick="this.parentElement.remove()" style="float:right; background:none; border:none; cursor:pointer; color:inherit;">&times;</button>
            </div>
            <?php endif; endforeach; ?>

            <div class="exec-banner">
                <div>
                    <h1><i class="fas fa-user-tie"></i> Parish Executive Dashboard</h1>
                    <p>Manage staff, approve requests, and oversee parish operations</p>
                </div>
                <span style="font-size:0.85rem; opacity:0.7;"><i class="fas fa-user"></i> <?= sanitize($_SESSION['user_name'] ?? '') ?></span>
            </div>

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); margin-bottom:1.5rem;">
                <div class="stat-card" style="border-left:4px solid #1a365d;">
                    <div class="stat-icon" style="background:#dbeafe; color:#1a365d;"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><h3><?= $totalStaff ?></h3><p>Total Staff</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #22c55e;">
                    <div class="stat-icon" style="background:#d1fae5; color:#22c55e;"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info"><h3><?= $clockedInCount ?></h3><p>Clocked In Now</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #e85d04;">
                    <div class="stat-icon" style="background:#ffedd5; color:#e85d04;"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><h3><?= count($pendingLeave) ?></h3><p>Pending Leave</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #7b2cbf;">
                    <div class="stat-icon" style="background:#ede9fe; color:#7b2cbf;"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info"><h3><?= count($pendingPurchases) ?></h3><p>Purchase Requests</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #0077b6;">
                    <div class="stat-icon" style="background:#e0f2fe; color:#0077b6;"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info"><h3><?= count($recentReports) + count($recentDocs) ?></h3><p>Pending Reviews</p></div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Today's Attendance -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock" style="color:#1a365d;"></i> Today's Attendance</h3>
                        <a href="/holy-trinity/executive/staff-attendance.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($todayAttendance)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No staff clocked in today</p>
                        <?php else: ?>
                            <?php foreach ($todayAttendance as $att): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= sanitize($att['first_name'] . ' ' . $att['last_name']) ?></strong>
                                    <br><small class="text-muted"><?= sanitize($att['position_title'] ?? ucfirst(str_replace('_',' ',$att['role']))) ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <span style="font-size:0.8rem;"><?= formatTime($att['clock_in']) ?> <?= $att['clock_out'] ? '- ' . formatTime($att['clock_out']) : '' ?></span>
                                    <br><span class="badge badge-<?= !$att['clock_out'] ? 'success' : 'info' ?>" style="font-size:0.7rem;"><?= !$att['clock_out'] ? 'Active' : $att['hours_worked'] . 'h' ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Leave Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-minus" style="color:#e85d04;"></i> Pending Leave Requests</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($pendingLeave)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No pending leave requests</p>
                        <?php else: ?>
                            <?php foreach ($pendingLeave as $lr): ?>
                            <div class="approval-card" style="margin:0.75rem;">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <div>
                                        <strong><?= sanitize($lr['first_name'] . ' ' . $lr['last_name']) ?></strong>
                                        <span class="badge badge-warning" style="font-size:0.7rem; margin-left:0.5rem;"><?= ucfirst(str_replace('_',' ',$lr['leave_type'])) ?></span>
                                        <br><small class="text-muted"><?= sanitize($lr['position_title'] ?? ucfirst(str_replace('_',' ',$lr['role']))) ?></small>
                                    </div>
                                    <div style="text-align:right; font-size:0.85rem;">
                                        <strong><?= $lr['days_requested'] ?> days</strong>
                                        <br><small><?= formatDate($lr['start_date'], 'M j') ?> - <?= formatDate($lr['end_date'], 'M j') ?></small>
                                    </div>
                                </div>
                                <p style="font-size:0.85rem; color:#64748b; margin:0.5rem 0;"><?= sanitize(substr($lr['reason'], 0, 100)) ?></p>
                                <div class="approval-actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                                        <input type="hidden" name="approver_notes" value="">
                                        <button type="submit" name="leave_action" value="approved" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                                        <input type="hidden" name="approver_notes" value="">
                                        <button type="submit" name="leave_action" value="rejected" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Purchase Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart" style="color:#7b2cbf;"></i> Purchase Requests</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($pendingPurchases)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No pending purchase requests</p>
                        <?php else: ?>
                            <?php foreach ($pendingPurchases as $pr): ?>
                            <div class="approval-card" style="margin:0.75rem;">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <div>
                                        <strong><?= sanitize($pr['title']) ?></strong>
                                        <br><small class="text-muted">By <?= sanitize($pr['first_name'] . ' ' . $pr['last_name']) ?> &bull; <?= ucfirst($pr['source_type']) ?></small>
                                    </div>
                                    <strong style="color:#7b2cbf;">ZMW <?= number_format($pr['total_amount'], 2) ?></strong>
                                </div>
                                <?php if ($pr['description']): ?><p style="font-size:0.85rem; color:#64748b; margin:0.5rem 0;"><?= sanitize(substr($pr['description'], 0, 80)) ?></p><?php endif; ?>
                                <div class="approval-actions">
                                    <form method="POST"><input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"><input type="hidden" name="purchase_id" value="<?= $pr['id'] ?>"><input type="hidden" name="approver_notes" value=""><button type="submit" name="purchase_action" value="approved" class="btn-approve"><i class="fas fa-check"></i> Approve</button></form>
                                    <form method="POST"><input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"><input type="hidden" name="purchase_id" value="<?= $pr['id'] ?>"><input type="hidden" name="approver_notes" value=""><button type="submit" name="purchase_action" value="rejected" class="btn-reject"><i class="fas fa-times"></i> Reject</button></form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Budgets -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice" style="color:#0077b6;"></i> Pending Budgets</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($pendingBudgets)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No pending budgets</p>
                        <?php else: ?>
                            <?php foreach ($pendingBudgets as $b): ?>
                            <div class="approval-card" style="margin:0.75rem;">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <div>
                                        <strong><?= sanitize($b['title']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize($b['budget_period']) ?> &bull; By <?= sanitize($b['first_name'] . ' ' . $b['last_name']) ?></small>
                                    </div>
                                    <strong style="color:#0077b6;">ZMW <?= number_format($b['total_amount'], 2) ?></strong>
                                </div>
                                <div class="approval-actions">
                                    <form method="POST"><input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"><input type="hidden" name="budget_id" value="<?= $b['id'] ?>"><input type="hidden" name="approver_notes" value=""><button type="submit" name="budget_action" value="approved" class="btn-approve"><i class="fas fa-check"></i> Approve</button></form>
                                    <form method="POST"><input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>"><input type="hidden" name="budget_id" value="<?= $b['id'] ?>"><input type="hidden" name="approver_notes" value=""><button type="submit" name="budget_action" value="rejected" class="btn-reject"><i class="fas fa-times"></i> Reject</button></form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reports & Documents -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt" style="color:#2d6a4f;"></i> Incoming Reports</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentReports)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No pending reports</p>
                        <?php else: ?>
                            <?php foreach ($recentReports as $r): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between;">
                                    <strong><?= sanitize($r['report_title']) ?></strong>
                                    <span class="badge badge-warning"><?= ucfirst($r['report_type']) ?></span>
                                </div>
                                <small class="text-muted">From <?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?> &bull; <?= ucfirst($r['source_type']) ?> &bull; <?= formatDate($r['created_at']) ?></small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-folder-open" style="color:#c2185b;"></i> Document Submissions</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentDocs)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No pending documents</p>
                        <?php else: ?>
                            <?php foreach ($recentDocs as $d): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <strong><?= sanitize($d['title']) ?></strong>
                                        <br><small class="text-muted">From <?= sanitize($d['first_name'] . ' ' . $d['last_name']) ?> &bull; <?= ucfirst($d['source_type']) ?></small>
                                    </div>
                                    <?php if ($d['file_path']): ?>
                                    <a href="/holy-trinity/<?= sanitize($d['file_path']) ?>" class="btn btn-sm btn-outline" download><i class="fas fa-download"></i></a>
                                    <?php endif; ?>
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
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
