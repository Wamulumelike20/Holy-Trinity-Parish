<?php
$pageTitle = 'Events & Announcements';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Fetch events
$filter = sanitize($_GET['filter'] ?? 'upcoming');
$category = sanitize($_GET['category'] ?? '');

$where = "status = 'published'";
$params = [];

if ($filter === 'upcoming') {
    $where .= " AND event_date >= CURDATE()";
} elseif ($filter === 'past') {
    $where .= " AND event_date < CURDATE()";
}

if ($category) {
    $where .= " AND category = ?";
    $params[] = $category;
}

$events = $db->fetchAll("SELECT * FROM events WHERE {$where} ORDER BY event_date " . ($filter === 'past' ? 'DESC' : 'ASC'), $params);

// Fetch announcements
$announcements = $db->fetchAll("SELECT * FROM announcements WHERE (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY priority DESC, created_at DESC LIMIT 10");

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != '' ORDER BY category");
?>

<!-- Page Banner -->
<section class="page-banner">
    <h1><i class="fas fa-calendar-alt"></i> Events & Announcements</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a>
        <span>/</span>
        <span>Events</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="eventsTab">
                <i class="fas fa-calendar-alt"></i> Events
            </button>
            <button class="tab-btn" data-tab="announcementsTab">
                <i class="fas fa-bullhorn"></i> Announcements
            </button>
            <button class="tab-btn" data-tab="calendarTab">
                <i class="fas fa-calendar"></i> Calendar View
            </button>
        </div>

        <!-- Events Tab -->
        <div class="tab-content active" id="eventsTab">
            <!-- Filters -->
            <div style="display:flex; gap:1rem; margin-bottom:2rem; flex-wrap:wrap; align-items:center;">
                <a href="?filter=upcoming" class="btn btn-sm <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-outline' ?>">Upcoming</a>
                <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All Events</a>
                <a href="?filter=past" class="btn btn-sm <?= $filter === 'past' ? 'btn-primary' : 'btn-outline' ?>">Past Events</a>
                <?php if (!empty($categories)): ?>
                    <select onchange="window.location.href='?filter=<?= $filter ?>&category='+this.value" class="form-control" style="width:auto; padding:0.4rem 2rem 0.4rem 0.75rem;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= sanitize($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>><?= sanitize($cat['category']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <?php if (empty($events)): ?>
                <div class="text-center" style="padding:4rem 2rem;">
                    <i class="fas fa-calendar-xmark" style="font-size:4rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                    <h3>No Events Found</h3>
                    <p class="text-muted">There are no events matching your criteria. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="event-date-badge">
                            <div class="month"><?= date('M', strtotime($event['event_date'])) ?></div>
                            <div class="day"><?= date('d', strtotime($event['event_date'])) ?></div>
                        </div>
                        <div class="event-card-body">
                            <h3><?= sanitize($event['title']) ?></h3>
                            <div class="event-meta">
                                <?php if ($event['start_time']): ?>
                                    <span><i class="fas fa-clock"></i> <?= formatTime($event['start_time']) ?>
                                    <?php if ($event['end_time']): ?> - <?= formatTime($event['end_time']) ?><?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($event['location']): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= sanitize($event['location']) ?></span>
                                <?php endif; ?>
                                <?php if ($event['category']): ?>
                                    <span class="badge badge-gold"><?= sanitize($event['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p><?= sanitize(substr($event['description'] ?? '', 0, 200)) ?></p>
                            <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                                <a href="/holy-trinity/pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if ($event['registration_required'] && strtotime($event['event_date']) >= strtotime('today')): ?>
                                    <a href="/holy-trinity/pages/event-detail.php?id=<?= $event['id'] ?>#register" class="btn btn-sm btn-primary">
                                        <i class="fas fa-user-plus"></i> Register
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Announcements Tab -->
        <div class="tab-content" id="announcementsTab">
            <?php if (empty($announcements)): ?>
                <div class="text-center" style="padding:4rem 2rem;">
                    <i class="fas fa-bullhorn" style="font-size:4rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                    <h3>No Announcements</h3>
                    <p class="text-muted">There are no current announcements.</p>
                </div>
            <?php else: ?>
                <div class="announcements-list">
                    <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-item <?= $ann['priority'] ?>">
                        <i class="fas fa-<?= $ann['priority'] === 'urgent' ? 'exclamation-triangle' : ($ann['priority'] === 'high' ? 'exclamation-circle' : 'bullhorn') ?>"></i>
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem;">
                                <h4><?= sanitize($ann['title']) ?></h4>
                                <span class="badge badge-<?= $ann['priority'] === 'urgent' ? 'error' : ($ann['priority'] === 'high' ? 'warning' : 'info') ?>"><?= ucfirst($ann['priority']) ?></span>
                            </div>
                            <p><?= nl2br(sanitize($ann['content'])) ?></p>
                            <span class="date"><i class="fas fa-calendar"></i> <?= formatDate($ann['created_at']) ?>
                                <?php if ($ann['category']): ?> &bull; <i class="fas fa-tag"></i> <?= sanitize($ann['category']) ?><?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Calendar Tab -->
        <div class="tab-content" id="calendarTab">
            <div class="card">
                <div class="card-body">
                    <div id="eventCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple event calendar
    const calendarContainer = document.getElementById('eventCalendar');
    if (calendarContainer) {
        const events = <?= json_encode(array_map(function($e) {
            return ['title' => $e['title'], 'date' => $e['event_date'], 'id' => $e['id']];
        }, $events)) ?>;

        let currentDate = new Date();

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

            let html = `
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                    <button class="btn btn-sm btn-outline" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <h3 style="margin:0;">${monthNames[month]} ${year}</h3>
                    <button class="btn btn-sm btn-outline" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="calendar-grid">
            `;

            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
                html += `<div class="calendar-header-cell">${d}</div>`;
            });

            for (let i = 0; i < firstDay; i++) {
                html += `<div class="calendar-cell other-month"></div>`;
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                const dayEvents = events.filter(e => e.date === dateStr);

                html += `<div class="calendar-cell ${isToday ? 'today' : ''}">
                    <span class="day-number">${day}</span>`;
                dayEvents.forEach(e => {
                    html += `<a href="/holy-trinity/pages/event-detail.php?id=${e.id}" class="calendar-event">${e.title}</a>`;
                });
                html += `</div>`;
            }

            html += '</div>';
            calendarContainer.innerHTML = html;
        }

        window.changeMonth = function(delta) {
            currentDate.setMonth(currentDate.getMonth() + delta);
            renderCalendar();
        };

        renderCalendar();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
