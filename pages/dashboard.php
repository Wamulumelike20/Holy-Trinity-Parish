<?php
$pageTitle = 'Parish Dashboard';
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();

// Public data - available to everyone
$upcomingEvents = $db->fetchAll(
    "SELECT * FROM events WHERE event_date >= CURDATE() AND status = 'published' ORDER BY event_date ASC LIMIT 6"
);

$announcements = $db->fetchAll(
    "SELECT * FROM announcements WHERE (expiry_date IS NULL OR expiry_date >= CURDATE())
     AND (publish_date IS NULL OR publish_date <= CURDATE())
     ORDER BY is_pinned DESC, priority DESC, created_at DESC LIMIT 8"
);

$massSchedule = $db->fetchAll(
    "SELECT * FROM mass_schedules WHERE is_active = 1
     ORDER BY FIELD(day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), time ASC"
);

$clergy = $db->fetchAll("SELECT * FROM clergy WHERE is_active = 1 ORDER BY display_order");

$departments = $db->fetchAll(
    "SELECT d.*, (SELECT COUNT(*) FROM department_members WHERE department_id = d.id) as member_count
     FROM departments d WHERE d.is_active = 1 ORDER BY d.name"
);

$ministries = $db->fetchAll(
    "SELECT m.*, (SELECT COUNT(*) FROM ministry_members WHERE ministry_id = m.id) as member_count
     FROM ministries m WHERE m.is_active = 1 ORDER BY m.name"
);

$latestSermon = $db->fetch(
    "SELECT s.*, c.title as preacher_title, c.full_name as preacher_name
     FROM sermons s LEFT JOIN clergy c ON s.preacher_id = c.id
     ORDER BY s.sermon_date DESC LIMIT 1"
);

// Stats
$totalParishioners = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE is_active = 1")['cnt'];
$totalMinistries = $db->fetch("SELECT COUNT(*) as cnt FROM ministries WHERE is_active = 1")['cnt'];
$totalEvents = $db->fetch("SELECT COUNT(*) as cnt FROM events WHERE event_date >= CURDATE() AND status = 'published'")['cnt'];

$priorityColors = ['urgent' => 'error', 'high' => 'warning', 'normal' => 'info', 'low' => 'primary'];
$priorityIcons = ['urgent' => 'fa-exclamation-circle', 'high' => 'fa-exclamation-triangle', 'normal' => 'fa-info-circle', 'low' => 'fa-bell'];
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
    <style>
        .public-dash-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white);
            padding: 2.5rem 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .public-dash-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(212,168,67,0.1);
            border-radius: 50%;
        }
        .public-dash-header h1 {
            font-family: var(--font-heading);
            color: var(--white);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .public-dash-header p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem 1rem;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-decoration: none;
            color: var(--text-dark);
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
        }
        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .quick-link i {
            font-size: 1.8rem;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }
        .quick-link span {
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Simple Top Nav -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div class="top-bar-left">
                <span><i class="fas fa-cross" style="color:var(--gold);"></i> <strong>Holy Trinity Parish</strong></span>
            </div>
            <div class="top-bar-right">
                <?php if (isLoggedIn()): ?>
                    <a href="/holy-trinity/portal/dashboard.php" style="color:inherit; text-decoration:none;"><i class="fas fa-user"></i> My Portal</a>
                    <a href="/holy-trinity/auth/logout.php" style="color:inherit; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="/holy-trinity/auth/login.php" style="color:inherit; text-decoration:none;"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="/holy-trinity/auth/register.php" style="color:inherit; text-decoration:none;"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
                <a href="/holy-trinity/index.php" style="color:inherit; text-decoration:none;"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </div>

    <div style="max-width:1200px; margin:0 auto; padding:2rem 1.5rem;">

        <!-- Header -->
        <div class="public-dash-header">
            <h1><i class="fas fa-church"></i> Holy Trinity Parish Dashboard</h1>
            <p>Stay connected with your parish community. View events, announcements, mass times, and more.</p>
            <div style="display:flex; gap:2rem; margin-top:1.5rem;">
                <div><strong style="font-size:1.5rem; color:var(--gold);"><?= $totalParishioners ?></strong><br><small>Parishioners</small></div>
                <div><strong style="font-size:1.5rem; color:var(--gold);"><?= $totalMinistries ?></strong><br><small>Ministries</small></div>
                <div><strong style="font-size:1.5rem; color:var(--gold);"><?= $totalEvents ?></strong><br><small>Upcoming Events</small></div>
                <div><strong style="font-size:1.5rem; color:var(--gold);"><?= count($departments) ?></strong><br><small>Departments</small></div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="/holy-trinity/appointments/book.php" class="quick-link">
                <i class="fas fa-calendar-check"></i>
                <span>Book Appointment</span>
            </a>
            <a href="/holy-trinity/donations/donate.php" class="quick-link">
                <i class="fas fa-hand-holding-heart"></i>
                <span>Make a Donation</span>
            </a>
            <a href="/holy-trinity/pages/events.php" class="quick-link">
                <i class="fas fa-calendar-alt"></i>
                <span>View Events</span>
            </a>
            <a href="/holy-trinity/pages/sermons.php" class="quick-link">
                <i class="fas fa-bible"></i>
                <span>Sermons</span>
            </a>
            <a href="/holy-trinity/pages/ministries.php" class="quick-link">
                <i class="fas fa-people-group"></i>
                <span>Join a Ministry</span>
            </a>
            <a href="/holy-trinity/pages/contact.php" class="quick-link">
                <i class="fas fa-envelope"></i>
                <span>Contact Us</span>
            </a>
        </div>

        <div style="display:grid; grid-template-columns:1fr 380px; gap:2rem;" class="grid-2">
            <div>
                <!-- Announcements -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn" style="color:var(--gold);"></i> Announcements</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($announcements)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No current announcements</p>
                        <?php else: ?>
                            <?php foreach ($announcements as $ann): ?>
                            <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--light-gray);">
                                <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                                    <i class="fas <?= $priorityIcons[$ann['priority']] ?? 'fa-bell' ?>" style="color:var(--<?= $priorityColors[$ann['priority']] ?? 'primary' ?>); margin-top:0.2rem;"></i>
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                                            <strong style="font-size:0.95rem;"><?php if ($ann['is_pinned']): ?><i class="fas fa-thumbtack" style="color:var(--gold); font-size:0.75rem;"></i> <?php endif; ?><?= sanitize($ann['title']) ?></strong>
                                            <span class="text-muted" style="font-size:0.75rem; white-space:nowrap;"><?= formatDate($ann['publish_date'] ?? $ann['created_at'], 'M d') ?></span>
                                        </div>
                                        <p style="margin:0.3rem 0 0; font-size:0.88rem; color:var(--gray); line-height:1.5;"><?= sanitize(substr($ann['content'], 0, 150)) ?><?= strlen($ann['content']) > 150 ? '...' : '' ?></p>
                                        <?php if ($ann['category']): ?>
                                            <span class="badge badge-gold" style="font-size:0.7rem; margin-top:0.3rem;"><?= sanitize($ann['category']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt" style="color:var(--gold);"></i> Upcoming Events</h3>
                        <a href="/holy-trinity/pages/events.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($upcomingEvents)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No upcoming events</p>
                        <?php else: ?>
                            <?php foreach ($upcomingEvents as $ev): ?>
                            <a href="/holy-trinity/pages/event-detail.php?id=<?= $ev['id'] ?>" style="display:flex; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--light-gray); text-decoration:none; color:inherit; transition:background 0.2s;" onmouseover="this.style.background='var(--off-white)'" onmouseout="this.style.background=''">
                                <div style="min-width:50px; text-align:center; padding:0.5rem; background:var(--primary); color:var(--white); border-radius:var(--radius);">
                                    <div style="font-size:1.2rem; font-weight:700; line-height:1;"><?= date('d', strtotime($ev['event_date'])) ?></div>
                                    <div style="font-size:0.7rem; text-transform:uppercase;"><?= date('M', strtotime($ev['event_date'])) ?></div>
                                </div>
                                <div>
                                    <strong style="font-size:0.95rem;"><?= sanitize($ev['title']) ?></strong>
                                    <div class="text-muted" style="font-size:0.8rem;">
                                        <?php if ($ev['start_time']): ?><i class="fas fa-clock"></i> <?= formatTime($ev['start_time']) ?><?php endif; ?>
                                        <?php if ($ev['location']): ?> &bull; <i class="fas fa-map-marker-alt"></i> <?= sanitize($ev['location']) ?><?php endif; ?>
                                    </div>
                                    <?php if ($ev['category']): ?><span class="badge badge-primary" style="font-size:0.7rem;"><?= sanitize($ev['category']) ?></span><?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Departments -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-building" style="color:var(--gold);"></i> Parish Departments</h3>
                        <a href="/holy-trinity/pages/departments.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
                            <?php foreach ($departments as $d): ?>
                            <div style="padding:1rem; background:var(--off-white); border-radius:var(--radius); text-align:center;">
                                <i class="fas fa-building" style="font-size:1.5rem; color:var(--gold); margin-bottom:0.5rem; display:block;"></i>
                                <strong style="font-size:0.9rem;"><?= sanitize($d['name']) ?></strong>
                                <div class="text-muted" style="font-size:0.8rem;"><?= $d['member_count'] ?> members</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Mass Schedule -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-clock" style="color:var(--gold);"></i> Mass Times</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php
                        $grouped = [];
                        foreach ($massSchedule as $ms) $grouped[$ms['day_of_week']][] = $ms;
                        foreach ($grouped as $day => $masses):
                        ?>
                        <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray);">
                            <strong style="font-size:0.85rem; color:var(--primary);"><?= $day ?></strong>
                            <?php foreach ($masses as $m): ?>
                                <div style="font-size:0.8rem; color:var(--gray); padding-left:0.5rem;">
                                    <?= formatTime($m['time']) ?> &mdash; <?= sanitize($m['mass_type']) ?>
                                    <?php if ($m['language'] !== 'English'): ?><span class="badge badge-gold" style="font-size:0.6rem;"><?= sanitize($m['language']) ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Our Clergy -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-user-tie" style="color:var(--gold);"></i> Our Clergy</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php foreach ($clergy as $c): ?>
                        <div style="padding:0.85rem 1.25rem; border-bottom:1px solid var(--light-gray); display:flex; align-items:center; gap:0.75rem;">
                            <?php if ($c['photo']): ?>
                                <img src="/holy-trinity/uploads/clergy/<?= sanitize($c['photo']) ?>" style="width:42px; height:42px; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:42px; height:42px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                    <?= strtoupper(substr($c['full_name'],0,2)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong style="font-size:0.9rem;"><?= sanitize($c['title'] . ' ' . $c['full_name']) ?></strong>
                                <div class="text-muted" style="font-size:0.8rem;"><?= sanitize($c['position']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Latest Sermon -->
                <?php if ($latestSermon): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-bible" style="color:var(--gold);"></i> Latest Sermon</h3>
                    </div>
                    <div class="card-body">
                        <h4 style="margin-bottom:0.25rem;"><?= sanitize($latestSermon['title']) ?></h4>
                        <?php if ($latestSermon['scripture_reference']): ?>
                            <div class="text-muted" style="font-size:0.85rem; margin-bottom:0.5rem;"><i class="fas fa-book-open"></i> <?= sanitize($latestSermon['scripture_reference']) ?></div>
                        <?php endif; ?>
                        <p style="font-size:0.9rem; color:var(--gray);"><?= sanitize(substr($latestSermon['content'] ?? '', 0, 200)) ?>...</p>
                        <?php if ($latestSermon['preacher_name']): ?>
                            <div class="text-muted" style="font-size:0.8rem;"><i class="fas fa-user"></i> <?= sanitize($latestSermon['preacher_title'] . ' ' . $latestSermon['preacher_name']) ?></div>
                        <?php endif; ?>
                        <a href="/holy-trinity/pages/sermons.php" class="btn btn-sm btn-outline" style="margin-top:0.75rem;">View All Sermons</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ministries -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-people-group" style="color:var(--gold);"></i> Ministries</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php foreach (array_slice($ministries, 0, 6) as $m): ?>
                        <div style="padding:0.65rem 1.25rem; border-bottom:1px solid var(--light-gray); display:flex; justify-content:space-between; align-items:center; font-size:0.88rem;">
                            <span><?= sanitize($m['name']) ?></span>
                            <span class="text-muted" style="font-size:0.75rem;"><?= $m['member_count'] ?> members</span>
                        </div>
                        <?php endforeach; ?>
                        <div style="padding:0.75rem 1.25rem; text-align:center;">
                            <a href="/holy-trinity/pages/ministries.php" class="btn btn-sm btn-outline">View All Ministries</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align:center; padding:2rem 0 1rem; color:var(--gray); font-size:0.85rem; border-top:1px solid var(--light-gray); margin-top:2rem;">
            <p>&copy; <?= date('Y') ?> Holy Trinity Parish. All rights reserved.</p>
        </div>
    </div>
    <script src="/holy-trinity/assets/js/main.js"></script>

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
