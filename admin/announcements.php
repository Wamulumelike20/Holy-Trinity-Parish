<?php
$pageTitle = 'Manage Announcements';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = sanitize($_POST['form_action'] ?? '');

        if ($action === 'create') {
            $db->insert('announcements', [
                'title' => sanitize($_POST['title'] ?? ''),
                'content' => sanitize($_POST['content'] ?? ''),
                'category' => sanitize($_POST['category'] ?? '') ?: null,
                'priority' => sanitize($_POST['priority'] ?? 'normal'),
                'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
                'publish_date' => $_POST['publish_date'] ?: date('Y-m-d'),
                'expiry_date' => $_POST['expiry_date'] ?: null,
                'created_by' => $_SESSION['user_id'],
            ]);
            logAudit('announcement_created', 'announcement');
            setFlash('success', 'Announcement created successfully.');
            redirect('/holy-trinity/admin/announcements.php');
        } elseif ($action === 'delete') {
            $id = intval($_POST['announcement_id'] ?? 0);
            if ($id) {
                $db->delete('announcements', 'id = ?', [$id]);
                logAudit('announcement_deleted', 'announcement', $id);
                setFlash('success', 'Announcement deleted.');
                redirect('/holy-trinity/admin/announcements.php');
            }
        }
    }
}

$announcements = $db->fetchAll(
    "SELECT a.*, u.first_name, u.last_name FROM announcements a
     LEFT JOIN users u ON a.created_by = u.id ORDER BY a.is_pinned DESC, a.created_at DESC"
);
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Admin</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a>
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
                    <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
                    <p class="text-muted">Create and manage parish announcements</p>
                </div>
                <button onclick="openModal('newAnnouncementModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Announcement</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Published</th>
                                <th>Expires</th>
                                <th>By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr><td colspan="7" class="text-center p-3">No announcements</td></tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                <tr>
                                    <td>
                                        <?php if ($ann['is_pinned']): ?><i class="fas fa-thumbtack" style="color:var(--gold);"></i> <?php endif; ?>
                                        <strong><?= sanitize($ann['title']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize(substr($ann['content'], 0, 60)) ?>...</small>
                                    </td>
                                    <td><?= sanitize($ann['category'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $ann['priority'] === 'urgent' ? 'error' : ($ann['priority'] === 'high' ? 'warning' : ($ann['priority'] === 'normal' ? 'info' : 'primary')) ?>">
                                            <?= ucfirst($ann['priority']) ?>
                                        </span>
                                    </td>
                                    <td><?= $ann['publish_date'] ? formatDate($ann['publish_date'], 'M d') : formatDate($ann['created_at'], 'M d') ?></td>
                                    <td><?= $ann['expiry_date'] ? formatDate($ann['expiry_date'], 'M d') : 'None' ?></td>
                                    <td><?= sanitize(($ann['first_name'] ?? '') . ' ' . ($ann['last_name'] ?? '')) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                            <button class="btn btn-sm btn-accent" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
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

    <!-- New Announcement Modal -->
    <div class="modal-overlay" id="newAnnouncementModal">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3><i class="fas fa-bullhorn"></i> New Announcement</h3>
                <button class="modal-close" onclick="closeModal('newAnnouncementModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-group">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Content <span class="required">*</span></label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="">General</option>
                                <option value="Mass">Mass</option>
                                <option value="Event">Event</option>
                                <option value="Ministry">Ministry</option>
                                <option value="Finance">Finance</option>
                                <option value="Youth">Youth</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Publish Date</label>
                            <input type="date" name="publish_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; margin-bottom:1rem;">
                        <input type="checkbox" name="is_pinned"> Pin this announcement
                    </label>
                    <div class="modal-footer" style="padding:0; border:none;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newAnnouncementModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Publish</button>
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

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
