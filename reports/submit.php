<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Determine what groups/departments the user belongs to
$userDepts = getUserDepartments($userId);
$userSCCs = getUserSCCs($userId);
$userLayGroups = getUserLayGroups($userId);

// Only leaders/heads can submit reports
$canSubmit = !empty($userDepts) || !empty($userSCCs) || !empty($userLayGroups) || in_array($userRole, ['priest','super_admin','admin','parish_executive','department_head','liturgical_coordinator']);

if (!$canSubmit) {
    setFlash('error', 'You must be a member of a department, SCC, or lay group to submit reports.');
    redirect('/holy-trinity/portal/dashboard.php');
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_report') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $sourceType = sanitize($_POST['source_type'] ?? '');
        $sourceId = intval($_POST['source_id'] ?? 0);
        $recipientType = sanitize($_POST['recipient_type'] ?? 'parish_priest');
        $reportTitle = sanitize($_POST['report_title'] ?? '');
        $reportContent = sanitize($_POST['report_content'] ?? '');
        $reportType = sanitize($_POST['report_type'] ?? 'monthly');
        $reportPeriod = sanitize($_POST['report_period'] ?? '');

        if (empty($reportTitle) || empty($reportContent) || empty($sourceType)) {
            setFlash('error', 'Please fill in all required fields.');
        } else {
            $attachPath = null;
            if (!empty($_FILES['attachment']['name'])) {
                $uploadDir = UPLOAD_DIR . 'reports/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                $filename = 'report_' . $sourceType . '_' . $sourceId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                    $attachPath = 'uploads/reports/' . $filename;
                }
            }

            $db->insert('reports', [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'submitted_by' => $userId,
                'recipient_type' => $recipientType,
                'report_title' => $reportTitle,
                'report_content' => $reportContent,
                'report_type' => $reportType,
                'report_period' => $reportPeriod,
                'attachment_path' => $attachPath,
            ]);

            // Get source name
            $sourceName = '';
            if ($sourceType === 'department') {
                $src = $db->fetch("SELECT name FROM departments WHERE id = ?", [$sourceId]);
                $sourceName = $src['name'] ?? 'Department';
            } elseif ($sourceType === 'scc') {
                $src = $db->fetch("SELECT name FROM small_christian_communities WHERE id = ?", [$sourceId]);
                $sourceName = $src['name'] ?? 'SCC';
            } elseif ($sourceType === 'lay_group') {
                $src = $db->fetch("SELECT name FROM lay_groups WHERE id = ?", [$sourceId]);
                $sourceName = $src['name'] ?? 'Lay Group';
            }

            // Notify recipients
            if ($recipientType === 'parish_priest' || $recipientType === 'both') {
                sendNotification('New Report: ' . $reportTitle, $sourceName . ' submitted a ' . $reportType . ' report.', 'report', '/holy-trinity/priest/reports.php', null, null, 'priest');
            }
            if ($recipientType === 'parish_executive' || $recipientType === 'both') {
                sendNotification('New Report: ' . $reportTitle, $sourceName . ' submitted a ' . $reportType . ' report.', 'report', '/holy-trinity/executive/reports.php', null, null, 'parish_executive');
            }

            setFlash('success', 'Report submitted successfully to ' . str_replace('_', ' ', $recipientType) . '.');
            redirect('/holy-trinity/reports/submit.php');
        }
    }
}

// Handle document submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_document') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $sourceType = sanitize($_POST['doc_source_type'] ?? '');
        $sourceId = intval($_POST['doc_source_id'] ?? 0);
        $recipientType = sanitize($_POST['doc_recipient_type'] ?? 'parish_executive');
        $title = sanitize($_POST['doc_title'] ?? '');
        $description = sanitize($_POST['doc_description'] ?? '');

        if (empty($title) || empty($_FILES['document']['name'])) {
            setFlash('error', 'Please provide a title and upload a document.');
        } else {
            $uploadDir = UPLOAD_DIR . 'submissions/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
            $filename = 'doc_' . $sourceType . '_' . $sourceId . '_' . time() . '.' . $ext;
            $filePath = null;

            if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $filename)) {
                $filePath = 'uploads/submissions/' . $filename;
            }

            if ($filePath) {
                $db->insert('document_submissions', [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'submitted_by' => $userId,
                    'recipient_type' => $recipientType,
                    'title' => $title,
                    'description' => $description,
                    'file_path' => $filePath,
                    'file_type' => $ext,
                    'file_size' => $_FILES['document']['size'],
                ]);

                sendNotification('New Document: ' . $title, $_SESSION['user_name'] . ' submitted a document.', 'info',
                    $recipientType === 'parish_priest' ? '/holy-trinity/priest/reports.php' : '/holy-trinity/executive/documents.php',
                    null, null, $recipientType === 'parish_priest' ? 'priest' : 'parish_executive');

                setFlash('success', 'Document submitted successfully.');
                redirect('/holy-trinity/reports/submit.php');
            } else {
                setFlash('error', 'Failed to upload document.');
            }
        }
    }
}

// My submitted reports
$myReports = $db->fetchAll("SELECT * FROM reports WHERE submitted_by = ? ORDER BY created_at DESC LIMIT 20", [$userId]);
$myDocSubmissions = $db->fetchAll("SELECT * FROM document_submissions WHERE submitted_by = ? ORDER BY created_at DESC LIMIT 20", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title>Submit Reports | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        .report-banner { background:linear-gradient(135deg,#2d6a4f,#40916c); color:#fff; padding:1.5rem 2rem; border-radius:12px; margin-bottom:1.5rem; }
        .report-banner h1 { font-family:'Cinzel',serif; font-size:1.4rem; margin:0; }
        .tab-nav { display:flex; gap:0; margin-bottom:1.5rem; border-bottom:2px solid #e2e8f0; }
        .tab-btn { padding:0.75rem 1.5rem; background:none; border:none; font-size:0.9rem; font-weight:600; color:#64748b; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.2s; }
        .tab-btn.active { color:#2d6a4f; border-bottom-color:#2d6a4f; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container" style="padding:2rem 1rem; max-width:900px; margin:0 auto;">
        <?php foreach (['success','error','warning'] as $type): $msg = getFlash($type); if ($msg): ?>
        <div class="flash-message flash-<?= $type ?>" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px;"><?= $msg ?><button onclick="this.parentElement.remove()" style="float:right;background:none;border:none;cursor:pointer;color:inherit;">&times;</button></div>
        <?php endif; endforeach; ?>

        <div class="report-banner">
            <h1><i class="fas fa-file-alt"></i> Reports & Documents</h1>
            <p style="opacity:0.8; margin:0.25rem 0 0; font-size:0.9rem;">Submit reports to the Parish Priest and documents to the Parish Executive</p>
        </div>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('report')"><i class="fas fa-file-alt"></i> Submit Report</button>
            <button class="tab-btn" onclick="showTab('document')"><i class="fas fa-folder-open"></i> Submit Document</button>
            <button class="tab-btn" onclick="showTab('history')"><i class="fas fa-history"></i> My Submissions</button>
        </div>

        <!-- Submit Report Tab -->
        <div id="tab-report" class="tab-content active">
            <div class="card">
                <div class="card-header"><h3>Submit a Report</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="submit_report">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="form-group">
                                <label>Submitting As <span class="required">*</span></label>
                                <select name="source_type" id="sourceType" class="form-control" required onchange="updateSourceOptions()">
                                    <option value="">Select...</option>
                                    <?php if (!empty($userDepts)): ?><option value="department">Department</option><?php endif; ?>
                                    <?php if (!empty($userSCCs)): ?><option value="scc">Small Christian Community</option><?php endif; ?>
                                    <?php if (!empty($userLayGroups)): ?><option value="lay_group">Lay Group</option><?php endif; ?>
                                    <?php if ($userRole === 'liturgical_coordinator'): ?><option value="liturgical">Liturgical</option><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Group/Department <span class="required">*</span></label>
                                <select name="source_id" id="sourceId" class="form-control" required>
                                    <option value="0">Select source type first...</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="form-group">
                                <label>Send To <span class="required">*</span></label>
                                <select name="recipient_type" class="form-control" required>
                                    <option value="parish_priest">Parish Priest</option>
                                    <option value="parish_executive">Parish Executive</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Report Type</label>
                                <select name="report_type" class="form-control">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annual">Annual</option>
                                    <option value="activity">Activity Report</option>
                                    <option value="financial">Financial Report</option>
                                    <option value="special">Special Report</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group"><label>Report Period</label><input type="text" name="report_period" class="form-control" placeholder="e.g. January 2026 or Q1 2026"></div>
                        <div class="form-group"><label>Report Title <span class="required">*</span></label><input type="text" name="report_title" class="form-control" required placeholder="Title of your report"></div>
                        <div class="form-group"><label>Report Content <span class="required">*</span></label><textarea name="report_content" class="form-control" rows="8" required placeholder="Write your report here..."></textarea></div>
                        <div class="form-group"><label>Attachment (optional)</label><input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.xls,.jpg,.png"></div>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Submit Report</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Submit Document Tab -->
        <div id="tab-document" class="tab-content">
            <div class="card">
                <div class="card-header"><h3>Submit a Document</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="submit_document">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="form-group">
                                <label>Submitting As</label>
                                <select name="doc_source_type" id="docSourceType" class="form-control" required onchange="updateDocSourceOptions()">
                                    <option value="">Select...</option>
                                    <?php if (!empty($userDepts)): ?><option value="department">Department</option><?php endif; ?>
                                    <?php if (!empty($userSCCs)): ?><option value="scc">Small Christian Community</option><?php endif; ?>
                                    <?php if (!empty($userLayGroups)): ?><option value="lay_group">Lay Group</option><?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Group/Department</label>
                                <select name="doc_source_id" id="docSourceId" class="form-control" required>
                                    <option value="0">Select source type first...</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Send To</label>
                            <select name="doc_recipient_type" class="form-control" required>
                                <option value="parish_executive">Parish Executive</option>
                                <option value="parish_priest">Parish Priest</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Document Title <span class="required">*</span></label><input type="text" name="doc_title" class="form-control" required placeholder="Title of the document"></div>
                        <div class="form-group"><label>Description</label><textarea name="doc_description" class="form-control" rows="3" placeholder="Brief description..."></textarea></div>
                        <div class="form-group"><label>Upload Document <span class="required">*</span></label><input type="file" name="document" class="form-control" required accept=".pdf,.doc,.docx,.xlsx,.xls,.jpg,.png,.pptx"></div>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-upload"></i> Submit Document</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div id="tab-history" class="tab-content">
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h3><i class="fas fa-file-alt"></i> My Reports</h3></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($myReports)): ?>
                        <p class="text-muted text-center" style="padding:1.5rem;">No reports submitted yet</p>
                    <?php else: ?>
                    <div class="table-responsive"><table class="table">
                        <thead><tr><th>Title</th><th>Type</th><th>To</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($myReports as $r): ?>
                        <tr>
                            <td><strong><?= sanitize($r['report_title']) ?></strong><br><small class="text-muted"><?= ucfirst($r['source_type']) ?></small></td>
                            <td><?= ucfirst($r['report_type']) ?></td>
                            <td><?= ucfirst(str_replace('_',' ',$r['recipient_type'])) ?></td>
                            <td><?= formatDate($r['created_at']) ?></td>
                            <td><span class="badge badge-<?= $r['status'] === 'acknowledged' ? 'success' : ($r['status'] === 'reviewed' ? 'info' : ($r['status'] === 'returned' ? 'danger' : 'warning')) ?>"><?= ucfirst($r['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-folder-open"></i> My Document Submissions</h3></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($myDocSubmissions)): ?>
                        <p class="text-muted text-center" style="padding:1.5rem;">No documents submitted yet</p>
                    <?php else: ?>
                    <div class="table-responsive"><table class="table">
                        <thead><tr><th>Title</th><th>To</th><th>Date</th><th>Status</th><th>File</th></tr></thead>
                        <tbody>
                        <?php foreach ($myDocSubmissions as $d): ?>
                        <tr>
                            <td><strong><?= sanitize($d['title']) ?></strong></td>
                            <td><?= ucfirst(str_replace('_',' ',$d['recipient_type'])) ?></td>
                            <td><?= formatDate($d['created_at']) ?></td>
                            <td><span class="badge badge-<?= $d['status'] === 'approved' ? 'success' : ($d['status'] === 'reviewed' ? 'info' : ($d['status'] === 'rejected' ? 'danger' : 'warning')) ?>"><?= ucfirst($d['status']) ?></span></td>
                            <td><a href="/holy-trinity/<?= sanitize($d['file_path']) ?>" class="btn btn-sm btn-outline" download><i class="fas fa-download"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    const depts = <?= json_encode(array_map(fn($d) => ['id'=>$d['id'],'name'=>$d['name']], $userDepts)) ?>;
    const sccs = <?= json_encode(array_map(fn($s) => ['id'=>$s['id'],'name'=>$s['name']], $userSCCs)) ?>;
    const layGroups = <?= json_encode(array_map(fn($g) => ['id'=>$g['id'],'name'=>$g['name']], $userLayGroups)) ?>;

    function updateSourceOptions() {
        const type = document.getElementById('sourceType').value;
        const sel = document.getElementById('sourceId');
        sel.innerHTML = '';
        let items = type === 'department' ? depts : type === 'scc' ? sccs : type === 'lay_group' ? layGroups : [{id:0,name:'Liturgical Committee'}];
        items.forEach(i => { const o = document.createElement('option'); o.value = i.id; o.textContent = i.name; sel.appendChild(o); });
    }
    function updateDocSourceOptions() {
        const type = document.getElementById('docSourceType').value;
        const sel = document.getElementById('docSourceId');
        sel.innerHTML = '';
        let items = type === 'department' ? depts : type === 'scc' ? sccs : type === 'lay_group' ? layGroups : [];
        items.forEach(i => { const o = document.createElement('option'); o.value = i.id; o.textContent = i.name; sel.appendChild(o); });
    }
    function showTab(name) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }
    </script>
</body>
</html>
