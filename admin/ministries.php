<?php
$pageTitle = 'Manage Ministries';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'create') {
        $db->insert('ministries', [
            'name' => sanitize($_POST['name'] ?? ''),
            'description' => sanitize($_POST['description'] ?? '') ?: null,
            'leader_id' => intval($_POST['leader_id'] ?? 0) ?: null,
            'meeting_schedule' => sanitize($_POST['meeting_schedule'] ?? '') ?: null,
            'is_active' => 1,
        ]);
        logAudit('ministry_created', 'ministry');
        setFlash('success', 'Ministry created successfully.');
        redirect('/holy-trinity/admin/ministries.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['ministry_id'] ?? 0);
        if ($id) {
            $db->delete('ministries', 'id = ?', [$id]);
            logAudit('ministry_deleted', 'ministry', $id);
            setFlash('success', 'Ministry deleted.');
            redirect('/holy-trinity/admin/ministries.php');
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['ministry_id'] ?? 0);
        if ($id) {
            $m = $db->fetch("SELECT is_active FROM ministries WHERE id = ?", [$id]);
            $db->update('ministries', ['is_active' => $m['is_active'] ? 0 : 1], 'id = ?', [$id]);
            setFlash('success', 'Ministry status updated.');
            redirect('/holy-trinity/admin/ministries.php');
        }
    }
}

$ministries = $db->fetchAll(
    "SELECT m.*, u.first_name as leader_first, u.last_name as leader_last,
     (SELECT COUNT(*) FROM ministry_members WHERE ministry_id = m.id) as member_count
     FROM ministries m LEFT JOIN users u ON m.leader_id = u.id ORDER BY m.name"
);

$users = $db->fetchAll("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY first_name");
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
                <a href="/holy-trinity/admin/mass-schedule.php"><i class="fas fa-clock"></i> Mass Schedule</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/ministries.php" class="active"><i class="fas fa-people-group"></i> Ministries</a>
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
                    <h1><i class="fas fa-people-group"></i> Ministries</h1>
                    <p class="text-muted">Manage parish ministries and their members</p>
                </div>
                <button onclick="openModal('newMinistryModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Ministry</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ministry</th>
                                <th>Leader</th>
                                <th>Meeting Schedule</th>
                                <th>Members</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ministries)): ?>
                                <tr><td colspan="6" class="text-center p-3">No ministries found</td></tr>
                            <?php else: ?>
                                <?php foreach ($ministries as $m): ?>
                                <tr style="<?= !$m['is_active'] ? 'opacity:0.5;' : '' ?>">
                                    <td>
                                        <strong><?= sanitize($m['name']) ?></strong>
                                        <?php if ($m['description']): ?>
                                            <br><small class="text-muted"><?= sanitize(substr($m['description'], 0, 60)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $m['leader_first'] ? sanitize($m['leader_first'] . ' ' . $m['leader_last']) : '<span class="text-muted">Not assigned</span>' ?></td>
                                    <td><?= sanitize($m['meeting_schedule'] ?? '-') ?></td>
                                    <td><strong><?= $m['member_count'] ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= $m['is_active'] ? 'success' : 'error' ?>"><?= $m['is_active'] ? 'Active' : 'Inactive' ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.25rem;">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="form_action" value="toggle">
                                                <input type="hidden" name="ministry_id" value="<?= $m['id'] ?>">
                                                <button class="btn btn-sm btn-outline" title="Toggle"><i class="fas fa-<?= $m['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this ministry?')">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="ministry_id" value="<?= $m['id'] ?>">
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

    <!-- New Ministry Modal -->
    <div class="modal-overlay" id="newMinistryModal">
        <div class="modal" style="max-width:550px;">
            <div class="modal-header">
                <h3><i class="fas fa-people-group"></i> New Ministry</h3>
                <button class="modal-close" onclick="closeModal('newMinistryModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-group">
                        <label>Ministry Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Leader</label>
                        <select name="leader_id" class="form-control">
                            <option value="">Select leader</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Meeting Schedule</label>
                        <input type="text" name="meeting_schedule" class="form-control" placeholder="e.g., Every Saturday at 3:00 PM">
                    </div>
                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newMinistryModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button>
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
