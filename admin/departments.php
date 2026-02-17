<?php
$pageTitle = 'Manage Departments';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $db->insert('departments', [
            'name' => $name,
            'slug' => $slug,
            'description' => sanitize($_POST['description'] ?? ''),
            'head_user_id' => intval($_POST['head_user_id'] ?? 0) ?: null,
            'email' => sanitize($_POST['email'] ?? '') ?: null,
            'phone' => sanitize($_POST['phone'] ?? '') ?: null,
        ]);
        logAudit('department_created', 'department');
        setFlash('success', 'Department created.');
        redirect('/holy-trinity/admin/departments.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['dept_id'] ?? 0);
        if ($id) {
            $db->delete('departments', 'id = ?', [$id]);
            logAudit('department_deleted', 'department', $id);
            setFlash('success', 'Department deleted.');
            redirect('/holy-trinity/admin/departments.php');
        }
    }
}

$departments = $db->fetchAll(
    "SELECT d.*, u.first_name as head_first, u.last_name as head_last,
     (SELECT COUNT(*) FROM department_members WHERE department_id = d.id) as member_count
     FROM departments d LEFT JOIN users u ON d.head_user_id = u.id ORDER BY d.name"
);

$staffUsers = $db->fetchAll("SELECT id, first_name, last_name, role FROM users WHERE role IN ('priest','department_head','admin','super_admin') AND is_active = 1 ORDER BY first_name");
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
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php" class="active"><i class="fas fa-building"></i> Departments</a>
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
                    <h1><i class="fas fa-building"></i> Departments</h1>
                    <p class="text-muted">Manage parish departments and their members</p>
                </div>
                <button onclick="openModal('newDeptModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Department</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
                <?php foreach ($departments as $dept): ?>
                <div class="card">
                    <div class="card-body" style="padding:1.5rem;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                                <div style="width:50px; height:50px; border-radius:var(--radius); background:rgba(212,168,67,0.15); display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-building" style="font-size:1.3rem; color:var(--gold);"></i>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:0; font-family:var(--font-body);"><?= sanitize($dept['name']) ?></h4>
                                    <span class="text-muted" style="font-size:0.8rem;"><?= $dept['member_count'] ?> members</span>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this department?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                <button class="btn btn-sm btn-outline" style="color:var(--error); border-color:var(--error); padding:0.3rem 0.5rem;"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <p style="font-size:0.9rem; color:var(--gray); margin-bottom:0.75rem;"><?= sanitize(substr($dept['description'] ?? '', 0, 100)) ?></p>
                        <?php if ($dept['head_first']): ?>
                            <div style="font-size:0.85rem; color:var(--text-light);"><i class="fas fa-user-tie" style="color:var(--gold);"></i> Head: <strong><?= sanitize($dept['head_first'] . ' ' . $dept['head_last']) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($dept['email']): ?>
                            <div style="font-size:0.85rem; color:var(--text-light); margin-top:0.25rem;"><i class="fas fa-envelope" style="color:var(--gold);"></i> <?= sanitize($dept['email']) ?></div>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem; padding-top:0.75rem; border-top:1px solid var(--light-gray);">
                            <span class="badge badge-<?= $dept['is_active'] ? 'success' : 'error' ?>"><?= $dept['is_active'] ? 'Active' : 'Inactive' ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- New Department Modal -->
    <div class="modal-overlay" id="newDeptModal">
        <div class="modal" style="max-width:550px;">
            <div class="modal-header">
                <h3><i class="fas fa-building"></i> New Department</h3>
                <button class="modal-close" onclick="closeModal('newDeptModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">
                    <div class="form-group">
                        <label>Department Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Department Head</label>
                        <select name="head_user_id" class="form-control">
                            <option value="">Select head</option>
                            <?php foreach ($staffUsers as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?> (<?= ucfirst($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newDeptModal')">Cancel</button>
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
