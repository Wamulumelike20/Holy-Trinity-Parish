<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();
if (!isStaff()) { setFlash('error', 'Staff access only.'); redirect('/holy-trinity/index.php'); }

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$staffMeta = [
    'guard' => ['icon' => 'fa-shield-halved', 'color' => '#1a365d', 'gradient' => 'linear-gradient(135deg,#1a365d,#2c5282)'],
    'house_helper' => ['icon' => 'fa-house-chimney', 'color' => '#7b2cbf', 'gradient' => 'linear-gradient(135deg,#7b2cbf,#9d4edd)'],
    'general_worker' => ['icon' => 'fa-hard-hat', 'color' => '#e85d04', 'gradient' => 'linear-gradient(135deg,#e85d04,#f48c06)'],
];
$meta = $staffMeta[$userRole] ?? $staffMeta['general_worker'];

$month = intval($_GET['month'] ?? date('m'));
$year = intval($_GET['year'] ?? date('Y'));

$attendance = $db->fetchAll(
    "SELECT * FROM staff_attendance WHERE staff_user_id = ? AND MONTH(clock_in) = ? AND YEAR(clock_in) = ? ORDER BY clock_in DESC",
    [$userId, $month, $year]
);
$totalHours = array_sum(array_column($attendance, 'hours_worked'));
$daysWorked = count($attendance);
$lateDays = count(array_filter($attendance, fn($a) => $a['status'] === 'late'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title>My Attendance | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>@media print { .no-print{display:none!important;} .sidebar{display:none!important;} .dashboard-layout{display:block!important;} }</style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar no-print" style="background:<?= $meta['gradient'] ?>;">
            <div class="sidebar-brand" style="background:rgba(0,0,0,0.2);"><i class="fas <?= $meta['icon'] ?>"></i><span>Staff Portal</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">My Dashboard</div>
                <a href="/holy-trinity/staff/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="/holy-trinity/staff/attendance.php" class="active"><i class="fas fa-clock"></i> My Attendance</a>
                <a href="/holy-trinity/staff/payslips.php"><i class="fas fa-file-invoice-dollar"></i> My Payslips</a>
                <a href="/holy-trinity/staff/leave.php"><i class="fas fa-calendar-minus"></i> Leave Requests</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user-edit"></i> My Profile</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        <div class="dashboard-main">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <h2 style="font-family:'Cinzel',serif; color:<?= $meta['color'] ?>;"><i class="fas fa-clock"></i> My Attendance</h2>
                <div class="no-print" style="display:flex; gap:0.5rem; align-items:center;">
                    <form method="GET" style="display:flex; gap:0.5rem;">
                        <select name="month" class="form-control" style="width:auto;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="year" class="form-control" style="width:auto;">
                            <?php for ($y = date('Y'); $y >= date('Y')-2; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                    <button onclick="window.print()" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>

            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); margin-bottom:1.5rem;">
                <div class="stat-card" style="border-left:4px solid #22c55e;"><div class="stat-info"><h3><?= $daysWorked ?></h3><p>Days Worked</p></div></div>
                <div class="stat-card" style="border-left:4px solid #0077b6;"><div class="stat-info"><h3><?= number_format($totalHours,1) ?>h</h3><p>Total Hours</p></div></div>
                <div class="stat-card" style="border-left:4px solid #e85d04;"><div class="stat-info"><h3><?= $lateDays ?></h3><p>Late Days</p></div></div>
                <div class="stat-card" style="border-left:4px solid #7b2cbf;"><div class="stat-info"><h3><?= $daysWorked > 0 ? number_format($totalHours/$daysWorked,1) : 0 ?>h</h3><p>Avg/Day</p></div></div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Attendance for <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></h3></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($attendance)): ?>
                        <p class="text-muted text-center" style="padding:2rem;">No attendance records for this period</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Hours</th><th>Shift</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($attendance as $a): ?>
                            <tr>
                                <td><strong><?= formatDate($a['clock_in'], 'D, M j') ?></strong></td>
                                <td><?= formatTime($a['clock_in']) ?></td>
                                <td><?= $a['clock_out'] ? formatTime($a['clock_out']) : '<em class="text-muted">Active</em>' ?></td>
                                <td><?= $a['hours_worked'] ?>h</td>
                                <td><?= ucfirst($a['shift_type']) ?></td>
                                <td><span class="badge badge-<?= $a['status'] === 'present' ? 'success' : ($a['status'] === 'late' ? 'warning' : 'info') ?>"><?= ucfirst($a['status']) ?></span></td>
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
    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
