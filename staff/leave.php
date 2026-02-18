<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();
if (!isStaff()) { setFlash('error', 'Staff access only.'); redirect('/holy-trinity/index.php'); }

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$pageTitle = 'Leave Requests';

$staffMeta = [
    'guard' => ['icon' => 'fa-shield-halved', 'color' => '#1a365d', 'gradient' => 'linear-gradient(135deg,#1a365d,#2c5282)'],
    'house_helper' => ['icon' => 'fa-house-chimney', 'color' => '#7b2cbf', 'gradient' => 'linear-gradient(135deg,#7b2cbf,#9d4edd)'],
    'general_worker' => ['icon' => 'fa-hard-hat', 'color' => '#e85d04', 'gradient' => 'linear-gradient(135deg,#e85d04,#f48c06)'],
];
$meta = $staffMeta[$userRole] ?? $staffMeta['general_worker'];

// Handle new leave request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_leave') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $leaveType = sanitize($_POST['leave_type'] ?? '');
        $startDate = sanitize($_POST['start_date'] ?? '');
        $endDate = sanitize($_POST['end_date'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        if (empty($leaveType) || empty($startDate) || empty($endDate) || empty($reason)) {
            setFlash('error', 'Please fill in all required fields.');
        } else {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $days = $start->diff($end)->days + 1;

            if ($end < $start) {
                setFlash('error', 'End date must be after start date.');
            } else {
                $attachPath = null;
                if (!empty($_FILES['attachment']['name'])) {
                    $uploadDir = UPLOAD_DIR . 'leave/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                    $filename = 'leave_' . $userId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                        $attachPath = 'uploads/leave/' . $filename;
                    }
                }

                $db->insert('leave_requests', [
                    'staff_user_id' => $userId,
                    'leave_type' => $leaveType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days_requested' => $days,
                    'reason' => $reason,
                    'attachment_path' => $attachPath,
                ]);

                // Notify parish executive
                sendNotification(
                    'New Leave Request',
                    $_SESSION['user_name'] . ' has requested ' . $days . ' days of ' . str_replace('_', ' ', $leaveType) . ' leave.',
                    'info',
                    '/holy-trinity/executive/leave-management.php',
                    null, null, 'parish_executive'
                );

                setFlash('success', 'Leave request submitted successfully. Awaiting approval from the Parish Executive.');
            }
        }
    }
    redirect('/holy-trinity/staff/leave.php');
}

$leaveRequests = $db->fetchAll("SELECT * FROM leave_requests WHERE staff_user_id = ? ORDER BY created_at DESC", [$userId]);
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
        <aside class="sidebar" style="background:<?= $meta['gradient'] ?>;">
            <div class="sidebar-brand" style="background:rgba(0,0,0,0.2);"><i class="fas <?= $meta['icon'] ?>"></i><span>Staff Portal</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">My Dashboard</div>
                <a href="/holy-trinity/staff/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/holy-trinity/staff/attendance.php"><i class="fas fa-clock"></i> My Attendance</a>
                <a href="/holy-trinity/staff/payslips.php"><i class="fas fa-file-invoice-dollar"></i> My Payslips</a>
                <a href="/holy-trinity/staff/leave.php" class="active"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user-edit"></i> My Profile</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        <div class="dashboard-main">
            <?php $msg = getFlash('success') ?: getFlash('error') ?: getFlash('warning');
            if ($msg): ?>
            <div class="flash-message flash-<?= getFlash('success') ? 'success' : (getFlash('error') ? 'error' : 'warning') ?>" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px;"><?= $msg ?></div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <h2 style="font-family:'Cinzel',serif; color:<?= $meta['color'] ?>;"><i class="fas fa-calendar-minus"></i> Leave Requests</h2>
                <button onclick="document.getElementById('leaveModal').style.display='flex'" class="btn btn-primary"><i class="fas fa-plus"></i> Request Leave</button>
            </div>

            <!-- Leave History -->
            <div class="card">
                <div class="card-body" style="padding:0;">
                    <?php if (empty($leaveRequests)): ?>
                        <p class="text-muted text-center" style="padding:2rem;">No leave requests yet</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Type</th><th>Period</th><th>Days</th><th>Reason</th><th>Status</th><th>Notes</th></tr></thead>
                            <tbody>
                            <?php foreach ($leaveRequests as $lr): ?>
                                <tr>
                                    <td><strong><?= ucfirst(str_replace('_', ' ', $lr['leave_type'])) ?></strong></td>
                                    <td><?= formatDate($lr['start_date'], 'M j') ?> - <?= formatDate($lr['end_date'], 'M j, Y') ?></td>
                                    <td><?= $lr['days_requested'] ?></td>
                                    <td style="max-width:200px;"><?= sanitize(substr($lr['reason'], 0, 80)) ?><?= strlen($lr['reason']) > 80 ? '...' : '' ?></td>
                                    <td><span class="badge badge-<?= $lr['status'] === 'approved' ? 'success' : ($lr['status'] === 'pending' ? 'warning' : ($lr['status'] === 'rejected' ? 'danger' : 'info')) ?>"><?= ucfirst($lr['status']) ?></span></td>
                                    <td style="font-size:0.85rem;"><?= sanitize($lr['approver_notes'] ?? '-') ?></td>
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

    <!-- Leave Request Modal -->
    <div id="leaveModal" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:1rem;">
        <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; padding:2rem; position:relative; max-height:90vh; overflow-y:auto;">
            <button onclick="document.getElementById('leaveModal').style.display='none'" style="position:absolute; top:1rem; right:1rem; background:none; border:none; font-size:1.3rem; cursor:pointer; color:#94a3b8;">&times;</button>
            <h3 style="margin-bottom:1.5rem; color:<?= $meta['color'] ?>;"><i class="fas fa-calendar-plus"></i> Request Leave</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="request_leave">
                <div class="form-group">
                    <label>Leave Type <span class="required">*</span></label>
                    <select name="leave_type" class="form-control" required>
                        <option value="">Select type...</option>
                        <option value="annual">Annual Leave</option>
                        <option value="sick">Sick Leave</option>
                        <option value="compassionate">Compassionate Leave</option>
                        <option value="maternity">Maternity Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="unpaid">Unpaid Leave</option>
                        <option value="study">Study Leave</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group"><label>Start Date <span class="required">*</span></label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="form-group"><label>End Date <span class="required">*</span></label><input type="date" name="end_date" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Reason <span class="required">*</span></label><textarea name="reason" class="form-control" rows="3" required placeholder="Explain the reason for your leave request..."></textarea></div>
                <div class="form-group"><label>Supporting Document (optional)</label><input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.png,.doc,.docx"></div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>
    </div>
    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
