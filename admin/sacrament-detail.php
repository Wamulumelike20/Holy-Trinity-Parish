<?php
$pageTitle = 'Sacrament Record Detail';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();
$recordId = intval($_GET['id'] ?? 0);

if (!$recordId) {
    setFlash('error', 'Record not found.');
    redirect('/holy-trinity/admin/sacraments.php');
}

$record = $db->fetch("SELECT sr.*, u.first_name as recorded_first, u.last_name as recorded_last FROM sacramental_records sr LEFT JOIN users u ON sr.recorded_by = u.id WHERE sr.id = ?", [$recordId]);

if (!$record) {
    setFlash('error', 'Record not found.');
    redirect('/holy-trinity/admin/sacraments.php');
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');
    if ($action === 'update') {
        $data = [
            'person_first_name' => sanitize($_POST['person_first_name'] ?? ''),
            'person_last_name' => sanitize($_POST['person_last_name'] ?? ''),
            'person_dob' => $_POST['person_dob'] ?: null,
            'father_name' => sanitize($_POST['father_name'] ?? '') ?: null,
            'mother_name' => sanitize($_POST['mother_name'] ?? '') ?: null,
            'sacrament_date' => sanitize($_POST['sacrament_date'] ?? ''),
            'minister_name' => sanitize($_POST['minister_name'] ?? '') ?: null,
            'place' => sanitize($_POST['place'] ?? ''),
            'sponsor1_name' => sanitize($_POST['sponsor1_name'] ?? '') ?: null,
            'sponsor2_name' => sanitize($_POST['sponsor2_name'] ?? '') ?: null,
            'spouse_name' => sanitize($_POST['spouse_name'] ?? '') ?: null,
            'register_number' => sanitize($_POST['register_number'] ?? '') ?: null,
            'page_number' => sanitize($_POST['page_number'] ?? '') ?: null,
            'entry_number' => sanitize($_POST['entry_number'] ?? '') ?: null,
            'notes' => sanitize($_POST['notes'] ?? '') ?: null,
        ];
        $db->update('sacramental_records', $data, 'id = ?', [$recordId]);
        logAudit('sacrament_record_updated', 'sacramental_records', $recordId);
        setFlash('success', 'Record updated successfully.');
        redirect('/holy-trinity/admin/sacrament-detail.php?id=' . $recordId);
    }
}

$typeLabels = [
    'baptism' => 'Baptism', 'confirmation' => 'Confirmation', 'marriage' => 'Marriage',
    'funeral' => 'Funeral', 'first_communion' => 'First Communion', 'anointing' => 'Anointing of the Sick',
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
                <a href="/holy-trinity/admin/sacraments.php" class="active"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
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
                    <h1><i class="fas fa-dove"></i> <?= $typeLabels[$record['record_type']] ?? 'Sacrament' ?> Record</h1>
                    <p class="text-muted">Reference: <?= $record['reference_number'] ?></p>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <a href="/holy-trinity/admin/sacrament-certificate.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-file-pdf"></i> Certificate</a>
                    <a href="/holy-trinity/admin/sacraments.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Record</h3>
                    <span class="badge badge-<?= $record['record_type'] === 'baptism' ? 'info' : ($record['record_type'] === 'confirmation' ? 'success' : ($record['record_type'] === 'marriage' ? 'gold' : 'primary')) ?>">
                        <?= $typeLabels[$record['record_type']] ?? ucfirst($record['record_type']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" data-validate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="form_action" value="update">

                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="person_first_name" class="form-control" value="<?= sanitize($record['person_first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="person_last_name" class="form-control" value="<?= sanitize($record['person_last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="person_dob" class="form-control" value="<?= $record['person_dob'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Sacrament Date <span class="required">*</span></label>
                                <input type="date" name="sacrament_date" class="form-control" value="<?= $record['sacrament_date'] ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Father's Name</label>
                                <input type="text" name="father_name" class="form-control" value="<?= sanitize($record['father_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Mother's Name</label>
                                <input type="text" name="mother_name" class="form-control" value="<?= sanitize($record['mother_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Minister</label>
                                <input type="text" name="minister_name" class="form-control" value="<?= sanitize($record['minister_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Place</label>
                                <input type="text" name="place" class="form-control" value="<?= sanitize($record['place'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Sponsor/Godparent 1</label>
                                <input type="text" name="sponsor1_name" class="form-control" value="<?= sanitize($record['sponsor1_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Sponsor/Godparent 2</label>
                                <input type="text" name="sponsor2_name" class="form-control" value="<?= sanitize($record['sponsor2_name'] ?? '') ?>">
                            </div>
                        </div>

                        <?php if ($record['record_type'] === 'marriage'): ?>
                        <div class="form-group">
                            <label>Spouse Name</label>
                            <input type="text" name="spouse_name" class="form-control" value="<?= sanitize($record['spouse_name'] ?? '') ?>">
                        </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Register Number</label>
                                <input type="text" name="register_number" class="form-control" value="<?= sanitize($record['register_number'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Page / Entry #</label>
                                <div style="display:flex; gap:0.5rem;">
                                    <input type="text" name="page_number" class="form-control" placeholder="Page" value="<?= sanitize($record['page_number'] ?? '') ?>">
                                    <input type="text" name="entry_number" class="form-control" placeholder="Entry" value="<?= sanitize($record['entry_number'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?= sanitize($record['notes'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--light-gray);">
                            <div class="text-muted" style="font-size:0.85rem;">
                                <?php if ($record['recorded_first']): ?>
                                    Recorded by: <?= sanitize($record['recorded_first'] . ' ' . $record['recorded_last']) ?> &bull;
                                <?php endif; ?>
                                Created: <?= formatDate($record['created_at'], 'M d, Y g:i A') ?>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Record</button>
                        </div>
                    </form>
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
</body>
</html>
