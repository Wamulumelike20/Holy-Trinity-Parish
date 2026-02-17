<?php
$pageTitle = 'Mass Schedule';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['form_action'] ?? '');

    if ($action === 'create') {
        $db->insert('mass_schedules', [
            'day_of_week' => sanitize($_POST['day_of_week'] ?? ''),
            'time' => sanitize($_POST['time'] ?? ''),
            'mass_type' => sanitize($_POST['mass_type'] ?? ''),
            'language' => sanitize($_POST['language'] ?? 'English'),
            'location' => sanitize($_POST['location'] ?? 'Main Church'),
            'is_active' => 1,
        ]);
        logAudit('mass_schedule_created', 'mass_schedule');
        setFlash('success', 'Mass schedule added.');
        redirect('/holy-trinity/admin/mass-schedule.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['schedule_id'] ?? 0);
        if ($id) {
            $db->delete('mass_schedules', 'id = ?', [$id]);
            logAudit('mass_schedule_deleted', 'mass_schedule', $id);
            setFlash('success', 'Schedule removed.');
            redirect('/holy-trinity/admin/mass-schedule.php');
        }
    } elseif ($action === 'toggle') {
        $id = intval($_POST['schedule_id'] ?? 0);
        if ($id) {
            $schedule = $db->fetch("SELECT is_active FROM mass_schedules WHERE id = ?", [$id]);
            $db->update('mass_schedules', ['is_active' => $schedule['is_active'] ? 0 : 1], 'id = ?', [$id]);
            setFlash('success', 'Schedule updated.');
            redirect('/holy-trinity/admin/mass-schedule.php');
        }
    }
}

$schedules = $db->fetchAll("SELECT * FROM mass_schedules ORDER BY FIELD(day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), time ASC");
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
                <a href="/holy-trinity/admin/mass-schedule.php" class="active"><i class="fas fa-clock"></i> Mass Schedule</a>
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
                    <h1><i class="fas fa-clock"></i> Mass Schedule</h1>
                    <p class="text-muted">Manage weekly Mass and service times</p>
                </div>
                <button onclick="openModal('newScheduleModal')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Schedule</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Service Type</th>
                                <th>Language</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                            <tr style="<?= !$s['is_active'] ? 'opacity:0.5;' : '' ?>">
                                <td><strong><?= sanitize($s['day_of_week']) ?></strong></td>
                                <td><?= formatTime($s['time']) ?></td>
                                <td><?= sanitize($s['mass_type']) ?></td>
                                <td><?= sanitize($s['language']) ?></td>
                                <td><?= sanitize($s['location']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $s['is_active'] ? 'success' : 'error' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.25rem;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="toggle">
                                            <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                            <button class="btn btn-sm btn-outline" title="Toggle"><i class="fas fa-<?= $s['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                            <button class="btn btn-sm btn-accent" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Schedule Modal -->
    <div class="modal-overlay" id="newScheduleModal">
        <div class="modal" style="max-width:500px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add Mass Schedule</h3>
                <button class="modal-close" onclick="closeModal('newScheduleModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">
                    <div class="form-group">
                        <label>Day of Week <span class="required">*</span></label>
                        <select name="day_of_week" class="form-control" required>
                            <option value="">Select day</option>
                            <?php foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day): ?>
                                <option value="<?= $day ?>"><?= $day ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Time <span class="required">*</span></label>
                            <input type="time" name="time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Language</label>
                            <input type="text" name="language" class="form-control" value="English">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Service Type <span class="required">*</span></label>
                        <select name="mass_type" class="form-control" required>
                            <option value="Holy Mass">Holy Mass</option>
                            <option value="Weekday Mass">Weekday Mass</option>
                            <option value="Vigil Mass">Vigil Mass</option>
                            <option value="Evening Mass">Evening Mass</option>
                            <option value="Confessions">Confessions</option>
                            <option value="Adoration & Benediction">Adoration & Benediction</option>
                            <option value="Stations of the Cross">Stations of the Cross</option>
                            <option value="Rosary">Rosary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" value="Main Church">
                    </div>
                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newScheduleModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add</button>
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
