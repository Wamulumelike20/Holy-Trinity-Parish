<?php
$pageTitle = 'Manage Events';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin', 'priest', 'department_head']);

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = sanitize($_POST['form_action'] ?? '');

        if ($action === 'create') {
            $title = sanitize($_POST['title'] ?? '');
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)) . '-' . time();

            $data = [
                'title' => $title,
                'slug' => $slug,
                'description' => sanitize($_POST['description'] ?? ''),
                'event_date' => sanitize($_POST['event_date'] ?? ''),
                'start_time' => $_POST['start_time'] ?: null,
                'end_time' => $_POST['end_time'] ?: null,
                'location' => sanitize($_POST['location'] ?? '') ?: null,
                'category' => sanitize($_POST['category'] ?? '') ?: null,
                'max_attendees' => intval($_POST['max_attendees'] ?? 0),
                'registration_required' => isset($_POST['registration_required']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'status' => sanitize($_POST['status'] ?? 'published'),
                'created_by' => $_SESSION['user_id'],
            ];

            $db->insert('events', $data);
            logAudit('event_created', 'event', null, null, json_encode(['title' => $title]));
            setFlash('success', 'Event created successfully.');
            redirect('/holy-trinity/admin/events.php');
        } elseif ($action === 'delete') {
            $eventId = intval($_POST['event_id'] ?? 0);
            if ($eventId) {
                $db->delete('events', 'id = ?', [$eventId]);
                logAudit('event_deleted', 'event', $eventId);
                setFlash('success', 'Event deleted.');
                redirect('/holy-trinity/admin/events.php');
            }
        }
    }
}

$filter = sanitize($_GET['filter'] ?? 'upcoming');
$where = "1=1";
if ($filter === 'upcoming') $where .= " AND event_date >= CURDATE()";
elseif ($filter === 'past') $where .= " AND event_date < CURDATE()";

$events = $db->fetchAll("SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status != 'cancelled') as reg_count FROM events e WHERE {$where} ORDER BY event_date DESC");
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
                <a href="/holy-trinity/admin/events.php" class="active"><i class="fas fa-calendar-alt"></i> Events</a>
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
                    <h1><i class="fas fa-calendar-alt"></i> Events</h1>
                    <p class="text-muted">Create and manage parish events</p>
                </div>
                <button onclick="openModal('newEventModal')" class="btn btn-primary"><i class="fas fa-plus"></i> New Event</button>
            </div>

            <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem;">
                <a href="?filter=upcoming" class="btn btn-sm <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-outline' ?>">Upcoming</a>
                <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
                <a href="?filter=past" class="btn btn-sm <?= $filter === 'past' ? 'btn-primary' : 'btn-outline' ?>">Past</a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Registrations</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr><td colspan="6" class="text-center p-3">No events found</td></tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($event['title']) ?></strong>
                                        <?php if ($event['category']): ?><br><span class="badge badge-gold"><?= sanitize($event['category']) ?></span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatDate($event['event_date']) ?>
                                        <?php if ($event['start_time']): ?><br><small><?= formatTime($event['start_time']) ?></small><?php endif; ?>
                                    </td>
                                    <td><?= sanitize($event['location'] ?? '-') ?></td>
                                    <td>
                                        <?= $event['reg_count'] ?>
                                        <?php if ($event['max_attendees']): ?> / <?= $event['max_attendees'] ?><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $event['status'] === 'published' ? 'success' : ($event['status'] === 'draft' ? 'warning' : 'error') ?>">
                                            <?= ucfirst($event['status']) ?>
                                        </span>
                                        <?php if ($event['is_featured']): ?><span class="badge badge-gold">Featured</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.25rem;">
                                            <a href="/holy-trinity/pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
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

    <!-- New Event Modal -->
    <div class="modal-overlay" id="newEventModal">
        <div class="modal" style="max-width:650px;">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Create New Event</h3>
                <button class="modal-close" onclick="closeModal('newEventModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="form_action" value="create">

                    <div class="form-group">
                        <label>Event Title <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Event Date <span class="required">*</span></label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="">Select category</option>
                                <option value="Liturgical">Liturgical</option>
                                <option value="Social">Social</option>
                                <option value="Educational">Educational</option>
                                <option value="Youth">Youth</option>
                                <option value="Fundraising">Fundraising</option>
                                <option value="Community">Community</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Main Church, Parish Hall">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Attendees (0 = unlimited)</label>
                            <input type="number" name="max_attendees" class="form-control" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; gap:1.5rem; margin-bottom:1rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="registration_required"> Registration Required
                        </label>
                        <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                            <input type="checkbox" name="is_featured"> Featured Event
                        </label>
                    </div>

                    <div class="modal-footer" style="padding:0; border:none; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('newEventModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Event</button>
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
