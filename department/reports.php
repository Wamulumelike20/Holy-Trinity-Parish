<?php
$pageTitle = 'Department Reports';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$userRole = $_SESSION['user_role'] ?? '';
$deptId = intval($_GET['dept'] ?? 0);

if (!$deptId) {
    setFlash('error', 'Department not specified.');
    redirect('/holy-trinity/portal/dashboard.php');
}

$dept = $db->fetch("SELECT * FROM departments WHERE id = ? AND is_active = 1", [$deptId]);
if (!$dept) {
    setFlash('error', 'Department not found.');
    redirect('/holy-trinity/portal/dashboard.php');
}

// Access check
$isHead = ($dept['head_user_id'] == $_SESSION['user_id']);
$canAccess = $isHead || in_array($userRole, ['priest', 'super_admin', 'admin']);
if (!$canAccess) {
    $isMember = $db->fetch("SELECT id FROM department_members WHERE department_id = ? AND user_id = ?", [$deptId, $_SESSION['user_id']]);
    if (!$isMember) {
        setFlash('error', 'Access denied.');
        redirect('/holy-trinity/portal/dashboard.php');
    }
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'submit_report') {
        $data = [
            'department_id' => $deptId,
            'submitted_by' => $_SESSION['user_id'],
            'report_title' => sanitize($_POST['report_title'] ?? ''),
            'report_content' => sanitize($_POST['report_content'] ?? ''),
            'report_type' => sanitize($_POST['report_type'] ?? 'monthly'),
            'report_period' => sanitize($_POST['report_period'] ?? ''),
            'status' => 'submitted',
        ];

        // Handle attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/uploads/documents/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $filename = 'report-' . $deptId . '-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                $data['attachment_path'] = 'documents/' . $filename;
            }
        }

        $db->insert('department_reports', $data);
        logAudit('department_report_submitted', 'department_report', null, null, json_encode(['dept' => $dept['name']]));

        // Notify all priests about the new report
        $submitterName = sanitize(($_SESSION['user_first'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
        sendNotification(
            'New Department Report',
            "{$submitterName} submitted a {$data['report_type']} report from {$dept['name']}: {$data['report_title']}",
            'report',
            '/holy-trinity/priest/reports.php',
            null, null, 'priest'
        );
        // Also notify super_admin
        sendNotification(
            'New Department Report',
            "{$submitterName} submitted a report from {$dept['name']}: {$data['report_title']}",
            'report',
            '/holy-trinity/priest/reports.php',
            null, null, 'super_admin'
        );

        setFlash('success', 'Report submitted successfully to the Parish Priest.');
        redirect('/holy-trinity/department/reports.php?dept=' . $deptId);
    }
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'new';

$reports = $db->fetchAll(
    "SELECT dr.*, u.first_name, u.last_name, ru.first_name as reviewer_first, ru.last_name as reviewer_last
     FROM department_reports dr
     LEFT JOIN users u ON dr.submitted_by = u.id
     LEFT JOIN users ru ON dr.reviewed_by = ru.id
     WHERE dr.department_id = ? ORDER BY dr.created_at DESC",
    [$deptId]
);

// Get departments for sidebar
if (in_array($userRole, ['priest', 'super_admin', 'admin'])) {
    $allDepts = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
} else {
    $allDepts = getUserDepartments($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?= sanitize($dept['name']) ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Dept</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Departments</div>
                <?php foreach ($allDepts as $d): ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $d['id'] ?>"><i class="fas fa-building"></i> <?= sanitize($d['name']) ?></a>
                <?php endforeach; ?>
                <div class="sidebar-section">Actions</div>
                <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>" class="active"><i class="fas fa-file-alt"></i> Reports</a>
                <a href="/holy-trinity/department/members.php?dept=<?= $deptId ?>"><i class="fas fa-users"></i> Members</a>
                <?php if (in_array($userRole, ['priest', 'super_admin', 'admin'])): ?>
                <div class="sidebar-section">Administration</div>
                <a href="/holy-trinity/priest/dashboard.php"><i class="fas fa-church"></i> Priest Dashboard</a>
                <?php endif; ?>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-user"></i> My Portal</a>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h1 style="font-family:var(--font-heading); color:var(--primary); margin-bottom:0.25rem;">
                        <i class="fas fa-file-alt" style="color:var(--gold);"></i> <?= sanitize($dept['name']) ?> Reports
                    </h1>
                    <p class="text-muted">Submit and track reports sent to the Parish Priest</p>
                </div>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <?php include __DIR__ . '/../includes/notifications.php'; ?>
                    <?php if ($isHead || in_array($userRole, ['priest', 'super_admin', 'admin'])): ?>
                        <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>&action=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Report</a>
                    <?php endif; ?>
                    <a href="/holy-trinity/department/dashboard.php?dept=<?= $deptId ?>" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </div>

            <?php if ($showForm && ($isHead || in_array($userRole, ['priest', 'super_admin', 'admin']))): ?>
            <!-- New Report Form -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-pen"></i> Submit Report to Parish Priest</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" data-validate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="submit_report">

                        <div class="form-group">
                            <label>Report Title <span class="required">*</span></label>
                            <input type="text" name="report_title" class="form-control" required placeholder="e.g., Monthly Activity Report - January 2026">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Report Type</label>
                                <select name="report_type" class="form-control">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annual">Annual</option>
                                    <option value="special">Special</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Report Period</label>
                                <input type="text" name="report_period" class="form-control" placeholder="e.g., January 2026">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Report Content <span class="required">*</span></label>
                            <textarea name="report_content" class="form-control" rows="10" required placeholder="Write your report here. Include activities, achievements, challenges, recommendations..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Attachment (optional)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <div class="form-text">Max 5MB. PDF, DOC, DOCX, XLS, XLSX</div>
                        </div>
                        <div style="display:flex; gap:1rem; justify-content:flex-end;">
                            <a href="/holy-trinity/department/reports.php?dept=<?= $deptId ?>" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit to Parish Priest</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reports List -->
            <div class="card">
                <div class="card-header">
                    <h3>All Reports (<?= count($reports) ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Period</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr><td colspan="6" class="text-center p-3">No reports submitted yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($r['report_title']) ?></strong>
                                        <?php if ($r['attachment_path']): ?>
                                            <br><a href="/holy-trinity/uploads/<?= sanitize($r['attachment_path']) ?>" class="text-muted" style="font-size:0.8rem;" target="_blank"><i class="fas fa-paperclip"></i> Attachment</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-primary"><?= ucfirst($r['report_type']) ?></span></td>
                                    <td><?= sanitize($r['report_period'] ?? '-') ?></td>
                                    <td><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                    <td><?= formatDate($r['created_at']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $r['status'] === 'acknowledged' ? 'success' : ($r['status'] === 'reviewed' ? 'info' : ($r['status'] === 'submitted' ? 'warning' : 'primary')) ?>">
                                            <?= ucfirst($r['status']) ?>
                                        </span>
                                        <?php if ($r['reviewer_first']): ?>
                                            <br><small class="text-muted">by <?= sanitize($r['reviewer_first'] . ' ' . $r['reviewer_last']) ?></small>
                                        <?php endif; ?>
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
</body>
</html>
