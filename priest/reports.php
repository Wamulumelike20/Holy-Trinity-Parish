<?php
$pageTitle = 'Department Reports';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['priest', 'super_admin', 'admin']);

$db = Database::getInstance();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');
    $reportId = intval($_POST['report_id'] ?? 0);

    if ($reportId && in_array($action, ['review', 'acknowledge'])) {
        $statusMap = ['review' => 'reviewed', 'acknowledge' => 'acknowledged'];
        $notes = sanitize($_POST['priest_notes'] ?? '');

        $db->update('department_reports', [
            'status' => $statusMap[$action],
            'priest_notes' => $notes ?: null,
            'reviewed_by' => $_SESSION['user_id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$reportId]);

        // Notify the submitter
        $report = $db->fetch("SELECT dr.*, d.name as dept_name FROM department_reports dr INNER JOIN departments d ON dr.department_id = d.id WHERE dr.id = ?", [$reportId]);
        if ($report) {
            $priestName = sanitize(($_SESSION['user_first'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
            sendNotification(
                'Report ' . ucfirst($statusMap[$action]),
                "Your report \"{$report['report_title']}\" from {$report['dept_name']} has been {$statusMap[$action]} by {$priestName}." . ($notes ? " Notes: {$notes}" : ''),
                'success',
                '/holy-trinity/department/reports.php?dept=' . $report['department_id'],
                $report['submitted_by']
            );
            // Notify department
            sendNotification(
                'Report ' . ucfirst($statusMap[$action]),
                "The report \"{$report['report_title']}\" has been {$statusMap[$action]} by the Parish Priest.",
                'success',
                '/holy-trinity/department/reports.php?dept=' . $report['department_id'],
                null, $report['department_id']
            );
        }

        logAudit('report_' . $action, 'department_report', $reportId);
        setFlash('success', 'Report ' . $statusMap[$action] . ' successfully.');
        redirect('/holy-trinity/priest/reports.php');
    }
}

// Single report review view
$reviewId = intval($_GET['review'] ?? 0);
$reviewReport = null;
if ($reviewId) {
    $reviewReport = $db->fetch(
        "SELECT dr.*, d.name as dept_name, u.first_name, u.last_name, u.email
         FROM department_reports dr
         INNER JOIN departments d ON dr.department_id = d.id
         LEFT JOIN users u ON dr.submitted_by = u.id
         WHERE dr.id = ?",
        [$reviewId]
    );
}

// Filters
$statusFilter = sanitize($_GET['status'] ?? '');
$deptFilter = intval($_GET['dept'] ?? 0);

$where = "1=1";
$params = [];
if ($statusFilter) { $where .= " AND dr.status = ?"; $params[] = $statusFilter; }
if ($deptFilter) { $where .= " AND dr.department_id = ?"; $params[] = $deptFilter; }

$reports = $db->fetchAll(
    "SELECT dr.*, d.name as dept_name, u.first_name, u.last_name
     FROM department_reports dr
     INNER JOIN departments d ON dr.department_id = d.id
     LEFT JOIN users u ON dr.submitted_by = u.id
     WHERE {$where} ORDER BY dr.created_at DESC",
    $params
);

$departments = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
$pendingCount = $db->fetch("SELECT COUNT(*) as cnt FROM department_reports WHERE status = 'submitted'")['cnt'];
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Parish Priest</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Overview</div>
                <a href="/holy-trinity/priest/dashboard.php"><i class="fas fa-church"></i> Dashboard</a>
                <a href="/holy-trinity/priest/reports.php" class="active"><i class="fas fa-file-alt"></i> All Reports <?php if ($pendingCount): ?><span class="badge badge-warning" style="font-size:0.65rem; padding:0.15rem 0.4rem; margin-left:0.25rem;"><?= $pendingCount ?></span><?php endif; ?></a>
                <div class="sidebar-section">All Departments</div>
                <?php foreach ($departments as $d): ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>"><i class="fas fa-building"></i> <?= sanitize($d['name']) ?></a>
                <?php endforeach; ?>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacraments</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="font-family:var(--font-heading); color:var(--primary); margin-bottom:0.25rem;">
                        <i class="fas fa-file-alt" style="color:var(--gold);"></i> Department Reports
                    </h1>
                    <p class="text-muted">Review and acknowledge reports from all departments</p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                </div>
            </div>

            <?php if ($reviewReport): ?>
            <!-- Single Report Review -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-eye"></i> Review Report</h3>
                    <a href="/holy-trinity/priest/reports.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
                <div class="card-body">
                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid var(--light-gray);">
                        <div>
                            <h2 style="margin-bottom:0.25rem;"><?= sanitize($reviewReport['report_title']) ?></h2>
                            <div class="text-muted">
                                <span class="badge badge-gold"><?= sanitize($reviewReport['dept_name']) ?></span>
                                &bull; <?= ucfirst($reviewReport['report_type']) ?>
                                <?php if ($reviewReport['report_period']): ?>&bull; <?= sanitize($reviewReport['report_period']) ?><?php endif; ?>
                                &bull; Submitted <?= formatDate($reviewReport['created_at'], 'M d, Y g:i A') ?>
                            </div>
                        </div>
                        <span class="badge badge-<?= $reviewReport['status'] === 'acknowledged' ? 'success' : ($reviewReport['status'] === 'reviewed' ? 'info' : 'warning') ?>" style="font-size:0.9rem; padding:0.4rem 1rem; height:fit-content;">
                            <?= ucfirst($reviewReport['status']) ?>
                        </span>
                    </div>

                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
                        <div style="width:40px; height:40px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:700;">
                            <?= strtoupper(substr($reviewReport['first_name'],0,1) . substr($reviewReport['last_name'],0,1)) ?>
                        </div>
                        <div>
                            <strong><?= sanitize($reviewReport['first_name'] . ' ' . $reviewReport['last_name']) ?></strong>
                            <div class="text-muted" style="font-size:0.85rem;"><?= sanitize($reviewReport['email']) ?></div>
                        </div>
                    </div>

                    <div style="background:var(--off-white); padding:1.5rem; border-radius:var(--radius); margin-bottom:1.5rem; line-height:1.8; white-space:pre-wrap;"><?= nl2br(sanitize($reviewReport['report_content'])) ?></div>

                    <?php if ($reviewReport['attachment_path']): ?>
                        <div style="margin-bottom:1.5rem;">
                            <a href="/holy-trinity/uploads/<?= sanitize($reviewReport['attachment_path']) ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-paperclip"></i> View Attachment</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($reviewReport['priest_notes']): ?>
                        <div style="background:rgba(212,168,67,0.1); padding:1rem; border-radius:var(--radius); border-left:4px solid var(--gold); margin-bottom:1.5rem;">
                            <strong style="font-size:0.85rem; color:var(--gold);">Priest's Notes:</strong>
                            <p style="margin:0.5rem 0 0;"><?= nl2br(sanitize($reviewReport['priest_notes'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($reviewReport['status'] === 'submitted' || $reviewReport['status'] === 'reviewed'): ?>
                    <div style="border-top:1px solid var(--light-gray); padding-top:1.5rem;">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="report_id" value="<?= $reviewReport['id'] ?>">
                            <div class="form-group">
                                <label>Notes / Feedback (optional)</label>
                                <textarea name="priest_notes" class="form-control" rows="3" placeholder="Add any feedback or instructions for the department..."><?= sanitize($reviewReport['priest_notes'] ?? '') ?></textarea>
                            </div>
                            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                                <?php if ($reviewReport['status'] === 'submitted'): ?>
                                    <button type="submit" name="form_action" value="review" class="btn btn-outline"><i class="fas fa-eye"></i> Mark as Reviewed</button>
                                    <button type="submit" name="form_action" value="acknowledge" class="btn btn-primary"><i class="fas fa-check-double"></i> Acknowledge</button>
                                <?php else: ?>
                                    <button type="submit" name="form_action" value="acknowledge" class="btn btn-primary"><i class="fas fa-check-double"></i> Acknowledge</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Department</label>
                            <select name="dept" class="form-control">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Pending Review</option>
                                <option value="reviewed" <?= $statusFilter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                <option value="acknowledged" <?= $statusFilter === 'acknowledged' ? 'selected' : '' ?>>Acknowledged</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                        <a href="/holy-trinity/priest/reports.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Reports (<?= count($reports) ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr><td colspan="7" class="text-center p-3">No reports found</td></tr>
                            <?php else: ?>
                                <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($r['report_title']) ?></strong>
                                        <?php if ($r['report_period']): ?><br><small class="text-muted"><?= sanitize($r['report_period']) ?></small><?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-gold"><?= sanitize($r['dept_name']) ?></span></td>
                                    <td><?= ucfirst($r['report_type']) ?></td>
                                    <td><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                    <td><?= formatDate($r['created_at']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $r['status'] === 'acknowledged' ? 'success' : ($r['status'] === 'reviewed' ? 'info' : 'warning') ?>">
                                            <?= ucfirst($r['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/holy-trinity/priest/reports.php?review=<?= $r['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
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

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
