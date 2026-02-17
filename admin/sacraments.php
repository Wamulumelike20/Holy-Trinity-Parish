<?php
$pageTitle = 'Sacramental Records';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = sanitize($_POST['form_action'] ?? '');

        if ($action === 'create') {
            $recordType = sanitize($_POST['record_type'] ?? '');
            $refNumber = generateReference(strtoupper(substr($recordType, 0, 3)));

            $data = [
                'record_type' => $recordType,
                'reference_number' => $refNumber,
                'person_first_name' => sanitize($_POST['person_first_name'] ?? ''),
                'person_last_name' => sanitize($_POST['person_last_name'] ?? ''),
                'person_dob' => $_POST['person_dob'] ?: null,
                'person_gender' => sanitize($_POST['person_gender'] ?? '') ?: null,
                'father_name' => sanitize($_POST['father_name'] ?? '') ?: null,
                'mother_name' => sanitize($_POST['mother_name'] ?? '') ?: null,
                'sacrament_date' => sanitize($_POST['sacrament_date'] ?? ''),
                'minister_name' => sanitize($_POST['minister_name'] ?? '') ?: null,
                'place' => sanitize($_POST['place'] ?? 'Holy Trinity Parish'),
                'sponsor1_name' => sanitize($_POST['sponsor1_name'] ?? '') ?: null,
                'sponsor2_name' => sanitize($_POST['sponsor2_name'] ?? '') ?: null,
                'spouse_name' => sanitize($_POST['spouse_name'] ?? '') ?: null,
                'register_number' => sanitize($_POST['register_number'] ?? '') ?: null,
                'page_number' => sanitize($_POST['page_number'] ?? '') ?: null,
                'entry_number' => sanitize($_POST['entry_number'] ?? '') ?: null,
                'notes' => sanitize($_POST['notes'] ?? '') ?: null,
                'recorded_by' => $_SESSION['user_id'],
            ];

            $db->insert('sacramental_records', $data);
            logAudit('sacrament_record_created', 'sacramental_records', null, null, json_encode(['ref' => $refNumber, 'type' => $recordType]));
            setFlash('success', ucfirst($recordType) . ' record created successfully. Reference: ' . $refNumber);
            redirect('/holy-trinity/admin/sacraments.php');
        }
    }
}

// Filters
$type = sanitize($_GET['type'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$year = sanitize($_GET['year'] ?? '');

$where = "1=1";
$params = [];

if ($type) {
    $where .= " AND record_type = ?";
    $params[] = $type;
}
if ($search) {
    $where .= " AND (person_first_name LIKE ? OR person_last_name LIKE ? OR reference_number LIKE ? OR father_name LIKE ? OR mother_name LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
}
if ($year) {
    $where .= " AND YEAR(sacrament_date) = ?";
    $params[] = $year;
}

$records = $db->fetchAll("SELECT * FROM sacramental_records WHERE {$where} ORDER BY sacrament_date DESC, created_at DESC", $params);

// Stats
$baptismCount = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records WHERE record_type = 'baptism'")['cnt'];
$confirmationCount = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records WHERE record_type = 'confirmation'")['cnt'];
$marriageCount = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records WHERE record_type = 'marriage'")['cnt'];
$funeralCount = $db->fetch("SELECT COUNT(*) as cnt FROM sacramental_records WHERE record_type = 'funeral'")['cnt'];
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
                    <h1><i class="fas fa-dove"></i> Sacramental Records</h1>
                    <p class="text-muted">Manage baptism, confirmation, marriage, and funeral records</p>
                </div>
                <button onclick="openModal('newRecordModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Record</button>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-water"></i></div>
                    <div class="stat-info"><h3><?= $baptismCount ?></h3><p>Baptisms</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-fire"></i></div>
                    <div class="stat-info"><h3><?= $confirmationCount ?></h3><p>Confirmations</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-rings-wedding"></i></div>
                    <div class="stat-info"><h3><?= $marriageCount ?></h3><p>Marriages</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-cross"></i></div>
                    <div class="stat-info"><h3><?= $funeralCount ?></h3><p>Funerals</p></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label style="font-size:0.8rem;">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, reference..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Type</label>
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                <option value="baptism" <?= $type === 'baptism' ? 'selected' : '' ?>>Baptism</option>
                                <option value="confirmation" <?= $type === 'confirmation' ? 'selected' : '' ?>>Confirmation</option>
                                <option value="marriage" <?= $type === 'marriage' ? 'selected' : '' ?>>Marriage</option>
                                <option value="funeral" <?= $type === 'funeral' ? 'selected' : '' ?>>Funeral</option>
                                <option value="first_communion" <?= $type === 'first_communion' ? 'selected' : '' ?>>First Communion</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label style="font-size:0.8rem;">Year</label>
                            <select name="year" class="form-control">
                                <option value="">All Years</option>
                                <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="/holy-trinity/admin/sacraments.php" class="btn btn-sm btn-outline">Clear</a>
                    </form>
                </div>
            </div>

            <!-- Records Table -->
            <div class="card" id="recordsTable">
                <div class="card-header">
                    <h3>Records (<?= count($records) ?>)</h3>
                    <button onclick="printContent('recordsTable')" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Parents</th>
                                <th>Date</th>
                                <th>Minister</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="7" class="text-center p-3">No records found</td></tr>
                            <?php else: ?>
                                <?php foreach ($records as $rec): ?>
                                <tr>
                                    <td><code style="font-size:0.8rem;"><?= $rec['reference_number'] ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $rec['record_type'] === 'baptism' ? 'info' : ($rec['record_type'] === 'confirmation' ? 'success' : ($rec['record_type'] === 'marriage' ? 'gold' : 'primary')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $rec['record_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($rec['person_first_name'] . ' ' . $rec['person_last_name']) ?></strong>
                                        <?php if ($rec['person_dob']): ?><br><small class="text-muted">DOB: <?= formatDate($rec['person_dob']) ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rec['father_name']): ?><small>F: <?= sanitize($rec['father_name']) ?></small><br><?php endif; ?>
                                        <?php if ($rec['mother_name']): ?><small>M: <?= sanitize($rec['mother_name']) ?></small><?php endif; ?>
                                    </td>
                                    <td><?= formatDate($rec['sacrament_date']) ?></td>
                                    <td><?= sanitize($rec['minister_name'] ?? '-') ?></td>
                                    <td>
                                        <div style="display:flex; gap:0.25rem;">
                                            <a href="/holy-trinity/admin/sacrament-detail.php?id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="/holy-trinity/admin/sacrament-certificate.php?id=<?= $rec['id'] ?>" class="btn btn-sm btn-primary" title="Certificate"><i class="fas fa-file-pdf"></i></a>
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

    <!-- New Record Modal -->
    <div class="modal-overlay" id="newRecordModal">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> New Sacramental Record</h3>
                <button class="modal-close" onclick="closeModal('newRecordModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-group">
                        <label>Record Type <span class="required">*</span></label>
                        <select name="record_type" class="form-control" required id="recordType">
                            <option value="">Select type</option>
                            <option value="baptism">Baptism</option>
                            <option value="confirmation">Confirmation</option>
                            <option value="marriage">Marriage</option>
                            <option value="funeral">Funeral</option>
                            <option value="first_communion">First Communion</option>
                            <option value="anointing">Anointing of the Sick</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="person_first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="person_last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="person_dob" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="person_gender" class="form-control">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sacrament Date <span class="required">*</span></label>
                            <input type="date" name="sacrament_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Minister</label>
                            <input type="text" name="minister_name" class="form-control" placeholder="Celebrant name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Place</label>
                        <input type="text" name="place" class="form-control" value="Holy Trinity Parish">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sponsor/Godparent 1</label>
                            <input type="text" name="sponsor1_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sponsor/Godparent 2</label>
                            <input type="text" name="sponsor2_name" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" id="spouseField" style="display:none;">
                        <label>Spouse Name</label>
                        <input type="text" name="spouse_name" class="form-control">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Register Number</label>
                            <input type="text" name="register_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Page / Entry #</label>
                            <div style="display:flex; gap:0.5rem;">
                                <input type="text" name="page_number" class="form-control" placeholder="Page">
                                <input type="text" name="entry_number" class="form-control" placeholder="Entry">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newRecordModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/holy-trinity/assets/js/main.js"></script>
    <script>
        document.getElementById('recordType')?.addEventListener('change', function() {
            document.getElementById('spouseField').style.display = this.value === 'marriage' ? 'block' : 'none';
        });
        if (window.innerWidth <= 1024) document.getElementById('sidebarToggle').style.display = 'inline-flex';
        window.addEventListener('resize', function() {
            const btn = document.getElementById('sidebarToggle');
            if (btn) btn.style.display = window.innerWidth <= 1024 ? 'inline-flex' : 'none';
        });
    </script>
</body>
</html>
