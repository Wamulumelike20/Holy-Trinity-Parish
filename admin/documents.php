<?php
$pageTitle = 'Manage Documents';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'upload' && isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = APP_ROOT . '/uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $file = $_FILES['document'];
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            setFlash('error', 'File size exceeds 5MB limit.');
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'jpg', 'png'];
            if (!in_array($ext, $allowed)) {
                setFlash('error', 'File type not allowed.');
            } else {
                $filename = 'doc-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $db->insert('documents', [
                        'title' => sanitize($_POST['title'] ?? $file['name']),
                        'file_path' => 'documents/' . $filename,
                        'file_type' => $ext,
                        'file_size' => $file['size'],
                        'department_id' => intval($_POST['department_id'] ?? 0) ?: null,
                        'uploaded_by' => $_SESSION['user_id'],
                        'category' => sanitize($_POST['category'] ?? '') ?: null,
                        'is_public' => isset($_POST['is_public']) ? 1 : 0,
                    ]);
                    logAudit('document_uploaded', 'document');
                    setFlash('success', 'Document uploaded successfully.');
                } else {
                    setFlash('error', 'Failed to upload file.');
                }
            }
        }
        redirect('/holy-trinity/admin/documents.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['doc_id'] ?? 0);
        if ($id) {
            $doc = $db->fetch("SELECT file_path FROM documents WHERE id = ?", [$id]);
            if ($doc) {
                $filePath = APP_ROOT . '/uploads/' . $doc['file_path'];
                if (file_exists($filePath)) unlink($filePath);
                $db->delete('documents', 'id = ?', [$id]);
                logAudit('document_deleted', 'document', $id);
                setFlash('success', 'Document deleted.');
            }
            redirect('/holy-trinity/admin/documents.php');
        }
    }
}

$category = sanitize($_GET['category'] ?? '');
$deptFilter = sanitize($_GET['department'] ?? '');

$where = "1=1";
$params = [];
if ($category) { $where .= " AND d.category = ?"; $params[] = $category; }
if ($deptFilter) { $where .= " AND d.department_id = ?"; $params[] = $deptFilter; }

$documents = $db->fetchAll(
    "SELECT d.*, u.first_name, u.last_name, dept.name as dept_name
     FROM documents d
     LEFT JOIN users u ON d.uploaded_by = u.id
     LEFT JOIN departments dept ON d.department_id = dept.id
     WHERE {$where} ORDER BY d.created_at DESC",
    $params
);

$departments = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");

$fileIcons = [
    'pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
    'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel', 'ppt' => 'fa-file-powerpoint',
    'pptx' => 'fa-file-powerpoint', 'txt' => 'fa-file-lines', 'csv' => 'fa-file-csv',
    'jpg' => 'fa-file-image', 'png' => 'fa-file-image',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Content</div>
                <a href="/holy-trinity/admin/sermons.php"><i class="fas fa-bible"></i> Sermons</a>
                <a href="/holy-trinity/admin/clergy.php"><i class="fas fa-user-tie"></i> Clergy</a>
                <a href="/holy-trinity/admin/documents.php" class="active"><i class="fas fa-file-alt"></i> Documents</a>
                <a href="/holy-trinity/admin/mass-schedule.php"><i class="fas fa-clock"></i> Mass Schedule</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/ministries.php"><i class="fas fa-people-group"></i> Ministries</a>
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
                    <h1><i class="fas fa-file-alt"></i> Documents</h1>
                    <p class="text-muted">Upload and manage parish documents</p>
                </div>
                <button onclick="openModal('uploadModal')" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Document</button>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Department</label>
                            <select name="department" class="form-control">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>><?= sanitize($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <option value="Report" <?= $category === 'Report' ? 'selected' : '' ?>>Reports</option>
                                <option value="Minutes" <?= $category === 'Minutes' ? 'selected' : '' ?>>Minutes</option>
                                <option value="Policy" <?= $category === 'Policy' ? 'selected' : '' ?>>Policies</option>
                                <option value="Form" <?= $category === 'Form' ? 'selected' : '' ?>>Forms</option>
                                <option value="Certificate" <?= $category === 'Certificate' ? 'selected' : '' ?>>Certificates</option>
                                <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
                        <a href="/holy-trinity/admin/documents.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Documents (<?= count($documents) ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Department</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                                <tr><td colspan="7" class="text-center p-3">No documents found</td></tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <i class="fas <?= $fileIcons[$doc['file_type']] ?? 'fa-file' ?>" style="font-size:1.5rem; color:var(--gold);"></i>
                                            <div>
                                                <strong><?= sanitize($doc['title']) ?></strong>
                                                <br><small class="text-muted">.<?= strtoupper($doc['file_type']) ?></small>
                                                <?php if ($doc['is_public']): ?><span class="badge badge-success" style="font-size:0.65rem;">Public</span><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= sanitize($doc['dept_name'] ?? 'General') ?></td>
                                    <td><?= sanitize($doc['category'] ?? '-') ?></td>
                                    <td><?= number_format($doc['file_size'] / 1024, 1) ?> KB</td>
                                    <td><?= sanitize(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? '')) ?></td>
                                    <td><?= formatDate($doc['created_at'], 'M d, Y') ?></td>
                                    <td>
                                        <div style="display:flex; gap:0.25rem;">
                                            <a href="/holy-trinity/uploads/<?= sanitize($doc['file_path']) ?>" class="btn btn-sm btn-outline" target="_blank" title="Download"><i class="fas fa-download"></i></a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this document?')">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                <button class="btn btn-sm btn-accent" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
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

    <!-- Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal" style="max-width:550px;">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> Upload Document</h3>
                <button class="modal-close" onclick="closeModal('uploadModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="upload">

                    <div class="form-group">
                        <label>Document Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>File <span class="required">*</span></label>
                        <input type="file" name="document" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.png">
                        <div class="form-text">Max 5MB. Accepted: PDF, DOC, DOCX, XLS, XLSX, PPT, TXT, CSV, JPG, PNG</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">General</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= sanitize($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="">Select</option>
                                <option value="Report">Report</option>
                                <option value="Minutes">Minutes</option>
                                <option value="Policy">Policy</option>
                                <option value="Form">Form</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; margin-bottom:1rem;">
                        <input type="checkbox" name="is_public"> Make publicly accessible
                    </label>
                    <div class="modal-footer" style="padding:0; border:none;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('uploadModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                </form>
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
</body>
</html>
