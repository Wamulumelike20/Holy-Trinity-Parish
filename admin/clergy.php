<?php
$pageTitle = 'Manage Clergy';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'create') {
        $data = [
            'title' => sanitize($_POST['title'] ?? ''),
            'full_name' => sanitize($_POST['full_name'] ?? ''),
            'position' => sanitize($_POST['position'] ?? ''),
            'bio' => sanitize($_POST['bio'] ?? '') ?: null,
            'ordination_date' => $_POST['ordination_date'] ?: null,
            'appointment_date' => $_POST['appointment_date'] ?: null,
            'display_order' => intval($_POST['display_order'] ?? 0),
            'is_active' => 1,
        ];

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/uploads/clergy/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'clergy-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                $data['photo'] = $filename;
            }
        }

        $db->insert('clergy', $data);
        logAudit('clergy_created', 'clergy');
        setFlash('success', 'Clergy profile created.');
        redirect('/holy-trinity/admin/clergy.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['clergy_id'] ?? 0);
        if ($id) {
            $db->delete('clergy', 'id = ?', [$id]);
            logAudit('clergy_deleted', 'clergy', $id);
            setFlash('success', 'Clergy profile removed.');
            redirect('/holy-trinity/admin/clergy.php');
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['clergy_id'] ?? 0);
        if ($id) {
            $c = $db->fetch("SELECT is_active FROM clergy WHERE id = ?", [$id]);
            $db->update('clergy', ['is_active' => $c['is_active'] ? 0 : 1], 'id = ?', [$id]);
            setFlash('success', 'Clergy status updated.');
            redirect('/holy-trinity/admin/clergy.php');
        }
    }
}

$clergyList = $db->fetchAll("SELECT * FROM clergy ORDER BY display_order ASC, full_name ASC");
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
                <a href="/holy-trinity/admin/clergy.php" class="active"><i class="fas fa-user-tie"></i> Clergy</a>
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
                    <h1><i class="fas fa-user-tie"></i> Clergy</h1>
                    <p class="text-muted">Manage clergy profiles displayed on the website</p>
                </div>
                <button onclick="openModal('newClergyModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Clergy</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
                <?php foreach ($clergyList as $c): ?>
                <div class="card" style="<?= !$c['is_active'] ? 'opacity:0.6;' : '' ?>">
                    <div style="background:linear-gradient(135deg, var(--primary), var(--primary-light)); padding:2rem; text-align:center; color:var(--white); position:relative;">
                        <?php if ($c['photo']): ?>
                            <img src="/holy-trinity/uploads/clergy/<?= sanitize($c['photo']) ?>" alt="<?= sanitize($c['full_name']) ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--gold); margin:0 auto;">
                        <?php else: ?>
                            <div style="width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; margin:0 auto; font-size:2.5rem;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div style="margin-top:1rem;">
                            <div style="color:var(--gold-light); font-size:0.85rem; text-transform:uppercase; letter-spacing:1px;"><?= sanitize($c['title']) ?></div>
                            <h3 style="color:var(--white); font-size:1.2rem; margin:0.25rem 0;"><?= sanitize($c['full_name']) ?></h3>
                            <div style="font-size:0.9rem; color:rgba(255,255,255,0.8);"><?= sanitize($c['position']) ?></div>
                        </div>
                        <?php if (!$c['is_active']): ?>
                            <span class="badge badge-error" style="position:absolute; top:1rem; right:1rem;">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($c['bio']): ?>
                            <p style="font-size:0.9rem; color:var(--gray);"><?= sanitize(substr($c['bio'], 0, 150)) ?>...</p>
                        <?php endif; ?>
                        <div style="font-size:0.85rem; color:var(--text-light);">
                            <?php if ($c['ordination_date']): ?>
                                <div><i class="fas fa-calendar" style="color:var(--gold); width:18px;"></i> Ordained: <?= formatDate($c['ordination_date']) ?></div>
                            <?php endif; ?>
                            <div><i class="fas fa-sort-numeric-up" style="color:var(--gold); width:18px;"></i> Display Order: <?= $c['display_order'] ?></div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:1rem; padding-top:0.75rem; border-top:1px solid var(--light-gray);">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="form_action" value="toggle">
                                <input type="hidden" name="clergy_id" value="<?= $c['id'] ?>">
                                <button class="btn btn-sm btn-outline" title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fas fa-<?= $c['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this clergy profile?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="clergy_id" value="<?= $c['id'] ?>">
                                <button class="btn btn-sm btn-accent" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- New Clergy Modal -->
    <div class="modal-overlay" id="newClergyModal">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add Clergy</h3>
                <button class="modal-close" onclick="closeModal('newClergyModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Title <span class="required">*</span></label>
                            <select name="title" class="form-control" required>
                                <option value="Rev. Fr.">Rev. Fr.</option>
                                <option value="Msgr.">Msgr.</option>
                                <option value="Deacon">Deacon</option>
                                <option value="Br.">Br.</option>
                                <option value="Sr.">Sr.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Position <span class="required">*</span></label>
                        <input type="text" name="position" class="form-control" placeholder="e.g., Parish Priest" required>
                    </div>
                    <div class="form-group">
                        <label>Biography</label>
                        <textarea name="bio" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <div class="form-text">Max 5MB. JPG, PNG recommended.</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ordination Date</label>
                            <input type="date" name="ordination_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0" min="0">
                        <div class="form-text">Lower numbers appear first</div>
                    </div>
                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newClergyModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Clergy</button>
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
