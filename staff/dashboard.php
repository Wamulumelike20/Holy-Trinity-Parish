<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['guard', 'house_helper', 'general_worker'])) {
    setFlash('error', 'Staff access only.');
    redirect('/holy-trinity/index.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$profile = getStaffProfile($userId);
$pageTitle = 'Staff Dashboard';

$staffMeta = [
    'guard' => ['label' => 'Security Guard', 'icon' => 'fa-shield-halved', 'color' => '#1a365d', 'gradient' => 'linear-gradient(135deg,#1a365d,#2c5282)'],
    'house_helper' => ['label' => 'House Helper', 'icon' => 'fa-house-chimney', 'color' => '#7b2cbf', 'gradient' => 'linear-gradient(135deg,#7b2cbf,#9d4edd)'],
    'general_worker' => ['label' => 'General Worker', 'icon' => 'fa-hard-hat', 'color' => '#e85d04', 'gradient' => 'linear-gradient(135deg,#e85d04,#f48c06)'],
];
$meta = $staffMeta[$userRole] ?? $staffMeta['general_worker'];

// Handle clock in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        if ($_POST['action'] === 'clock_in') {
            $existing = $db->fetch("SELECT id FROM staff_attendance WHERE staff_user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL", [$userId]);
            if ($existing) {
                setFlash('warning', 'You are already clocked in today.');
            } else {
                $shift = sanitize($_POST['shift_type'] ?? 'day');
                $db->insert('staff_attendance', [
                    'staff_user_id' => $userId,
                    'clock_in' => date('Y-m-d H:i:s'),
                    'shift_type' => $shift,
                    'status' => (date('H') > 8) ? 'late' : 'present',
                ]);
                logAudit('staff_clock_in', 'staff_attendance', null, null, json_encode(['shift' => $shift]));
                setFlash('success', 'Clocked in successfully at ' . date('g:i A'));
            }
        } elseif ($_POST['action'] === 'clock_out') {
            $attendance = $db->fetch("SELECT * FROM staff_attendance WHERE staff_user_id = ? AND DATE(clock_in) = CURDATE() AND clock_out IS NULL ORDER BY id DESC LIMIT 1", [$userId]);
            if ($attendance) {
                $clockIn = new DateTime($attendance['clock_in']);
                $clockOut = new DateTime();
                $hours = round(($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 3600, 2);
                $db->update('staff_attendance', [
                    'clock_out' => date('Y-m-d H:i:s'),
                    'hours_worked' => $hours,
                ], 'id = ?', [$attendance['id']]);
                logAudit('staff_clock_out', 'staff_attendance', $attendance['id']);
                setFlash('success', 'Clocked out at ' . date('g:i A') . '. Hours worked: ' . $hours . 'h');
            } else {
                setFlash('warning', 'You have not clocked in today.');
            }
        }
    }
    redirect('/holy-trinity/staff/dashboard.php');
}

// Current clock status
$todayAttendance = $db->fetch("SELECT * FROM staff_attendance WHERE staff_user_id = ? AND DATE(clock_in) = CURDATE() ORDER BY id DESC LIMIT 1", [$userId]);
$isClockedIn = $todayAttendance && !$todayAttendance['clock_out'];

// This week's attendance
$weekAttendance = $db->fetchAll(
    "SELECT * FROM staff_attendance WHERE staff_user_id = ? AND clock_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY clock_in DESC",
    [$userId]
);
$totalHoursWeek = array_sum(array_column($weekAttendance, 'hours_worked'));

// This month's attendance
$monthAttendance = $db->fetchAll(
    "SELECT * FROM staff_attendance WHERE staff_user_id = ? AND MONTH(clock_in) = MONTH(CURDATE()) AND YEAR(clock_in) = YEAR(CURDATE()) ORDER BY clock_in DESC",
    [$userId]
);
$totalHoursMonth = array_sum(array_column($monthAttendance, 'hours_worked'));
$daysWorkedMonth = count($monthAttendance);

// Recent payslips
$payslips = $db->fetchAll("SELECT * FROM payslips WHERE staff_user_id = ? ORDER BY pay_date DESC LIMIT 6", [$userId]);

// Leave requests
$leaveRequests = $db->fetchAll("SELECT * FROM leave_requests WHERE staff_user_id = ? ORDER BY created_at DESC LIMIT 10", [$userId]);
$pendingLeave = count(array_filter($leaveRequests, fn($l) => $l['status'] === 'pending'));
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
        .clock-card { background:<?= $meta['gradient'] ?>; color:#fff; border-radius:16px; padding:2rem; text-align:center; margin-bottom:1.5rem; }
        .clock-time { font-size:3rem; font-weight:700; font-family:'Cinzel',serif; }
        .clock-date { opacity:0.8; font-size:0.95rem; margin-bottom:1.5rem; }
        .clock-btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.85rem 2.5rem; border-radius:50px; font-size:1rem; font-weight:700; border:none; cursor:pointer; transition:all 0.3s; }
        .clock-in-btn { background:#22c55e; color:#fff; }
        .clock-out-btn { background:#ef4444; color:#fff; }
        .clock-btn:hover { transform:scale(1.05); box-shadow:0 8px 25px rgba(0,0,0,0.2); }
        .clock-status { display:inline-flex; align-items:center; gap:0.5rem; padding:0.4rem 1rem; border-radius:20px; font-size:0.85rem; font-weight:600; margin-bottom:1rem; }
        .status-in { background:rgba(34,197,94,0.2); color:#22c55e; }
        .status-out { background:rgba(239,68,68,0.2); color:#ef4444; }
        .payslip-download { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.8rem; background:#e0f2fe; color:#0077b6; border-radius:6px; font-size:0.8rem; font-weight:600; text-decoration:none; transition:all 0.2s; }
        .payslip-download:hover { background:#0077b6; color:#fff; }
        .print-btn { display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:<?= $meta['color'] ?>; color:#fff; border:none; border-radius:6px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.2s; text-decoration:none; }
        .print-btn:hover { opacity:0.9; transform:translateY(-1px); }
        @media print { .no-print { display:none !important; } .dashboard-layout { display:block !important; } .sidebar { display:none !important; } }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar" style="background:<?= $meta['gradient'] ?>;">
            <div class="sidebar-brand" style="background:rgba(0,0,0,0.2);">
                <i class="fas <?= $meta['icon'] ?>"></i>
                <span>Staff Portal</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">My Dashboard</div>
                <a href="/holy-trinity/staff/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/holy-trinity/staff/attendance.php"><i class="fas fa-clock"></i> My Attendance</a>
                <a href="/holy-trinity/staff/payslips.php"><i class="fas fa-file-invoice-dollar"></i> My Payslips</a>
                <a href="/holy-trinity/staff/leave.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user-edit"></i> My Profile</a>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <?php
            $flashTypes = ['success', 'error', 'warning', 'info'];
            foreach ($flashTypes as $type):
                $msg = getFlash($type);
                if ($msg):
            ?>
            <div class="flash-message flash-<?= $type ?>" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px;">
                <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <?= $msg ?>
                <button onclick="this.parentElement.remove()" style="float:right; background:none; border:none; cursor:pointer; color:inherit;">&times;</button>
            </div>
            <?php endif; endforeach; ?>

            <!-- Clock In/Out Card -->
            <div class="clock-card no-print">
                <div class="clock-status <?= $isClockedIn ? 'status-in' : 'status-out' ?>">
                    <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                    <?= $isClockedIn ? 'Currently Clocked In' : 'Not Clocked In' ?>
                </div>
                <div class="clock-time" id="liveTime"><?= date('H:i:s') ?></div>
                <div class="clock-date"><?= date('l, F j, Y') ?></div>

                <?php if ($isClockedIn): ?>
                    <p style="opacity:0.8; font-size:0.85rem; margin-bottom:1rem;">
                        Clocked in at <?= formatTime($todayAttendance['clock_in']) ?> (<?= ucfirst($todayAttendance['shift_type']) ?> shift)
                    </p>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="clock_out">
                        <button type="submit" class="clock-btn clock-out-btn" onclick="return confirm('Are you sure you want to clock out?')">
                            <i class="fas fa-right-from-bracket"></i> Clock Out
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" style="display:inline-flex; align-items:center; gap:1rem; flex-wrap:wrap; justify-content:center;">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="clock_in">
                        <select name="shift_type" style="padding:0.5rem 1rem; border-radius:8px; border:1px solid rgba(255,255,255,0.3); background:rgba(255,255,255,0.15); color:#fff; font-size:0.9rem;">
                            <option value="day">Day Shift</option>
                            <option value="night">Night Shift</option>
                            <option value="morning">Morning Shift</option>
                            <option value="afternoon">Afternoon Shift</option>
                        </select>
                        <button type="submit" class="clock-btn clock-in-btn">
                            <i class="fas fa-right-to-bracket"></i> Clock In
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); margin-bottom:1.5rem;">
                <div class="stat-card" style="border-left:4px solid <?= $meta['color'] ?>;">
                    <div class="stat-icon" style="background:rgba(26,54,93,0.1); color:<?= $meta['color'] ?>;"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalHoursWeek, 1) ?>h</h3><p>This Week</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #22c55e;">
                    <div class="stat-icon" style="background:rgba(34,197,94,0.1); color:#22c55e;"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= $daysWorkedMonth ?></h3><p>Days This Month</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #0077b6;">
                    <div class="stat-icon" style="background:rgba(0,119,182,0.1); color:#0077b6;"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info"><h3><?= number_format($totalHoursMonth, 1) ?>h</h3><p>Hours This Month</p></div>
                </div>
                <div class="stat-card" style="border-left:4px solid #e85d04;">
                    <div class="stat-icon" style="background:rgba(232,93,4,0.1); color:#e85d04;"><i class="fas fa-calendar-minus"></i></div>
                    <div class="stat-info"><h3><?= $pendingLeave ?></h3><p>Pending Leave</p></div>
                </div>
            </div>

            <div class="grid-2">
                <!-- Recent Attendance -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock" style="color:<?= $meta['color'] ?>;"></i> Recent Attendance</h3>
                        <a href="/holy-trinity/staff/attendance.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($weekAttendance)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No attendance records this week</p>
                        <?php else: ?>
                            <?php foreach (array_slice($weekAttendance, 0, 7) as $att): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= formatDate($att['clock_in'], 'D, M j') ?></strong>
                                    <br><small class="text-muted">
                                        <?= formatTime($att['clock_in']) ?> - <?= $att['clock_out'] ? formatTime($att['clock_out']) : '<em>Active</em>' ?>
                                    </small>
                                </div>
                                <div style="text-align:right;">
                                    <span class="badge badge-<?= $att['status'] === 'present' ? 'success' : ($att['status'] === 'late' ? 'warning' : 'info') ?>"><?= ucfirst($att['status']) ?></span>
                                    <br><small class="text-muted"><?= $att['hours_worked'] ?>h</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payslips -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar" style="color:#0077b6;"></i> Recent Payslips</h3>
                        <a href="/holy-trinity/staff/payslips.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($payslips)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No payslips available yet</p>
                        <?php else: ?>
                            <?php foreach ($payslips as $ps): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= sanitize($ps['pay_period']) ?></strong>
                                    <br><small class="text-muted"><?= formatDate($ps['pay_date']) ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <strong style="color:#22c55e;">ZMW <?= number_format($ps['net_pay'], 2) ?></strong>
                                    <br><a href="/holy-trinity/staff/payslip-download.php?id=<?= $ps['id'] ?>" class="payslip-download"><i class="fas fa-download"></i> Download</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Leave Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-minus" style="color:#e85d04;"></i> Leave Requests</h3>
                        <a href="/holy-trinity/staff/leave.php" class="btn btn-sm btn-outline">Request Leave</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($leaveRequests)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No leave requests</p>
                        <?php else: ?>
                            <?php foreach (array_slice($leaveRequests, 0, 5) as $lr): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= ucfirst(str_replace('_', ' ', $lr['leave_type'])) ?></strong>
                                    <span class="badge badge-<?= $lr['status'] === 'approved' ? 'success' : ($lr['status'] === 'pending' ? 'warning' : ($lr['status'] === 'rejected' ? 'danger' : 'info')) ?>"><?= ucfirst($lr['status']) ?></span>
                                </div>
                                <small class="text-muted"><?= formatDate($lr['start_date']) ?> - <?= formatDate($lr['end_date']) ?> (<?= $lr['days_requested'] ?> days)</small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user" style="color:<?= $meta['color'] ?>;"></i> My Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($profile): ?>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.9rem;">
                            <div><strong>Employee ID:</strong><br><?= sanitize($profile['employee_id'] ?? 'N/A') ?></div>
                            <div><strong>Position:</strong><br><?= sanitize($profile['position_title'] ?? ucfirst(str_replace('_', ' ', $userRole))) ?></div>
                            <div><strong>Hire Date:</strong><br><?= $profile['hire_date'] ? formatDate($profile['hire_date']) : 'N/A' ?></div>
                            <div><strong>Contract:</strong><br><?= ucfirst($profile['contract_type'] ?? 'N/A') ?></div>
                            <div><strong>Email:</strong><br><?= sanitize($profile['email'] ?? '') ?></div>
                            <div><strong>Phone:</strong><br><?= sanitize($profile['phone'] ?? '') ?></div>
                        </div>
                        <?php else: ?>
                            <p class="text-muted">Profile not set up yet. Contact the parish office.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Live clock
    function updateClock() {
        const el = document.getElementById('liveTime');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
        }
    }
    setInterval(updateClock, 1000);
    </script>
    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
