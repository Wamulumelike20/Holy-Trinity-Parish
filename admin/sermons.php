<?php
$pageTitle = 'Manage Sermons';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'create') {
        $title = sanitize($_POST['title'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)) . '-' . time();
        $db->insert('sermons', [
            'title' => $title,
            'slug' => $slug,
            'content' => sanitize($_POST['content'] ?? ''),
            'scripture_reference' => sanitize($_POST['scripture_reference'] ?? '') ?: null,
            'preacher_id' => intval($_POST['preacher_id'] ?? 0) ?: null,
            'sermon_date' => $_POST['sermon_date'] ?: date('Y-m-d'),
            'audio_url' => sanitize($_POST['audio_url'] ?? '') ?: null,
            'video_url' => sanitize($_POST['video_url'] ?? '') ?: null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ]);
        logAudit('sermon_created', 'sermon');
        setFlash('success', 'Sermon published successfully.');
        redirect('/holy-trinity/admin/sermons.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['sermon_id'] ?? 0);
        if ($id) {
            $db->delete('sermons', 'id = ?', [$id]);
            logAudit('sermon_deleted', 'sermon', $id);
            setFlash('success', 'Sermon deleted.');
            redirect('/holy-trinity/admin/sermons.php');
        }
    }
}

$sermons = $db->fetchAll(
    "SELECT s.*, c.full_name as preacher_name, c.title as preacher_title
     FROM sermons s LEFT JOIN clergy c ON s.preacher_id = c.id
     ORDER BY s.sermon_date DESC"
);

$clergy = $db->fetchAll("SELECT * FROM clergy WHERE is_active = 1 ORDER BY display_order");
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
                <a href="/holy-trinity/admin/sermons.php" class="active"><i class="fas fa-bible"></i> Sermons</a>
                <a href="/holy-trinity/admin/clergy.php"><i class="fas fa-user-tie"></i> Clergy</a>
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
                    <h1><i class="fas fa-bible"></i> Sermons</h1>
                    <p class="text-muted">Publish and manage sermons and reflections</p>
                </div>
                <button onclick="openModal('newSermonModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Sermon</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Preacher</th>
                                <th>Date</th>
                                <th>Views</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sermons)): ?>
                                <tr><td colspan="6" class="text-center p-3">No sermons published yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($sermons as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($s['title']) ?></strong>
                                        <?php if ($s['scripture_reference']): ?>
                                            <br><small class="text-muted"><i class="fas fa-bible"></i> <?= sanitize($s['scripture_reference']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= sanitize(($s['preacher_title'] ?? '') . ' ' . ($s['preacher_name'] ?? '-')) ?></td>
                                    <td><?= $s['sermon_date'] ? formatDate($s['sermon_date']) : '-' ?></td>
                                    <td><?= number_format($s['views']) ?></td>
                                    <td>
                                        <?php if ($s['is_featured']): ?>
                                            <span class="badge badge-gold">Featured</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this sermon?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="sermon_id" value="<?= $s['id'] ?>">
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

    <!-- New Sermon Modal -->
    <div class="modal-overlay" id="newSermonModal">
        <div class="modal" style="max-width:650px;">
            <div class="modal-header">
                <h3><i class="fas fa-bible"></i> New Sermon</h3>
                <button class="modal-close" onclick="closeModal('newSermonModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-group">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Scripture Reference</label>
                            <input type="text" name="scripture_reference" class="form-control" placeholder="e.g., John 3:16">
                        </div>
                        <div class="form-group">
                            <label>Preacher</label>
                            <select name="preacher_id" class="form-control">
                                <option value="">Select preacher</option>
                                <?php foreach ($clergy as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= sanitize($c['title'] . ' ' . $c['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Sermon Date</label>
                        <input type="date" name="sermon_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Content <span class="required">*</span></label>
                        <textarea name="content" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Audio URL</label>
                            <input type="url" name="audio_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label>Video URL</label>
                            <input type="url" name="video_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; margin-bottom:1rem;">
                        <input type="checkbox" name="is_featured"> Mark as Featured
                    </label>
                    <div class="modal-footer" style="padding:0; border:none;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newSermonModal')">Cancel</button>
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
</body>
</html>
