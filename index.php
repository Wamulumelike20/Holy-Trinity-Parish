<?php
$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();

// Fetch upcoming events
$events = $db->fetchAll("SELECT * FROM events WHERE event_date >= CURDATE() AND status = 'published' ORDER BY event_date ASC LIMIT 3");

// Fetch announcements
$announcements = $db->fetchAll("SELECT * FROM announcements WHERE (expiry_date IS NULL OR expiry_date >= CURDATE()) AND (publish_date IS NULL OR publish_date <= CURDATE()) ORDER BY priority DESC, created_at DESC LIMIT 4");

// Fetch featured sermon
$sermon = $db->fetch("SELECT s.*, c.full_name as preacher_name, c.title as preacher_title FROM sermons s LEFT JOIN clergy c ON s.preacher_id = c.id ORDER BY s.is_featured DESC, s.sermon_date DESC LIMIT 1");

// Fetch clergy
$clergy = $db->fetchAll("SELECT * FROM clergy WHERE is_active = 1 ORDER BY display_order ASC LIMIT 3");

// Fetch mass schedule grouped by day
$schedules = $db->fetchAll("SELECT * FROM mass_schedules WHERE is_active = 1 ORDER BY FIELD(day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), time ASC");
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-cross"><i class="fas fa-cross"></i></div>
        <h1>Holy Trinity Parish</h1>
        <p class="subtitle">A Community of Faith, Hope & Love</p>
        <p>Welcome to Holy Trinity Parish. We are a vibrant Catholic community united in worship, fellowship, and service. Come as you are and experience the love of Christ.</p>
        <div class="hero-buttons">
            <a href="/holy-trinity/appointments/book.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-check"></i> Book Appointment
            </a>
            <a href="/holy-trinity/donations/donate.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-hand-holding-heart"></i> Donate Now
            </a>
            <a href="/holy-trinity/pages/ministries.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-hands-praying"></i> Join a Ministry
            </a>
        </div>
    </div>
</section>

<!-- Welcome Message -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Welcome to Our Parish</h2>
            <p>A message from our Parish Priest</p>
        </div>
        <div style="max-width:800px; margin:0 auto; text-align:center;">
            <div style="font-size:3rem; color:var(--gold); margin-bottom:1rem;">
                <i class="fas fa-quote-left"></i>
            </div>
            <p style="font-size:1.1rem; font-style:italic; line-height:2; color:var(--gray);">
                Dear Brothers and Sisters in Christ, welcome to Holy Trinity Parish. Our parish is a place where all are welcome to encounter the living God through the sacraments, prayer, and community. Whether you are a lifelong Catholic or just beginning your journey of faith, we invite you to make this your spiritual home. Together, let us grow in holiness and bring the light of Christ to the world.
            </p>
            <div style="margin-top:1.5rem;">
                <strong style="color:var(--primary); font-family:var(--font-heading);">Rev. Fr. John Mukasa</strong><br>
                <span style="color:var(--text-light); font-size:0.9rem;">Parish Priest, Holy Trinity Parish</span>
            </div>
        </div>
    </div>
</section>

<!-- Quick Actions -->
<section class="section section-cream">
    <div class="container">
        <div class="section-header">
            <h2>How Can We Help You?</h2>
            <p>Quick access to our most popular services</p>
        </div>
        <div class="quick-actions">
            <a href="/holy-trinity/appointments/book.php" class="quick-action-card">
                <i class="fas fa-calendar-check"></i>
                <h4>Book Appointment</h4>
                <p>Schedule a meeting with our clergy or departments</p>
            </a>
            <a href="/holy-trinity/donations/donate.php" class="quick-action-card">
                <i class="fas fa-hand-holding-heart"></i>
                <h4>Make a Donation</h4>
                <p>Support our parish through tithes and offerings</p>
            </a>
            <a href="/holy-trinity/pages/ministries.php" class="quick-action-card">
                <i class="fas fa-people-group"></i>
                <h4>Join a Ministry</h4>
                <p>Get involved and serve in our parish community</p>
            </a>
            <a href="/holy-trinity/pages/events.php" class="quick-action-card">
                <i class="fas fa-calendar-alt"></i>
                <h4>Upcoming Events</h4>
                <p>Stay updated with parish activities and programs</p>
            </a>
            <a href="/holy-trinity/portal/sacraments.php" class="quick-action-card">
                <i class="fas fa-dove"></i>
                <h4>Sacramental Records</h4>
                <p>Access baptism, confirmation, and marriage records</p>
            </a>
            <a href="/holy-trinity/pages/contact.php" class="quick-action-card">
                <i class="fas fa-envelope-open-text"></i>
                <h4>Contact Us</h4>
                <p>Reach out to us for any inquiries or support</p>
            </a>
        </div>
    </div>
</section>

<!-- Mass Schedule -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Mass & Service Times</h2>
            <p>Join us for worship throughout the week</p>
        </div>
        <div class="mass-schedule-grid">
            <?php
            $grouped = [];
            foreach ($schedules as $s) {
                $grouped[$s['day_of_week']][] = $s;
            }
            foreach ($grouped as $day => $times):
            ?>
            <div class="schedule-card">
                <div class="day"><i class="fas fa-calendar-day"></i> <?= $day ?></div>
                <?php foreach ($times as $t): ?>
                <div class="time-slot">
                    <i class="fas fa-clock"></i>
                    <span class="time"><?= formatTime($t['time']) ?></span>
                    <span><?= sanitize($t['mass_type']) ?></span>
                    <?php if ($t['language'] !== 'English'): ?>
                        <span class="badge badge-gold"><?= sanitize($t['language']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Sermon -->
<?php if ($sermon): ?>
<section class="section section-cream">
    <div class="container">
        <div class="section-header">
            <h2>Weekly Reflection</h2>
            <p>Nourish your faith with our latest sermon</p>
        </div>
        <div class="sermon-featured">
            <?php if ($sermon['scripture_reference']): ?>
                <div class="scripture"><i class="fas fa-bible"></i> <?= sanitize($sermon['scripture_reference']) ?></div>
            <?php endif; ?>
            <h3><?= sanitize($sermon['title']) ?></h3>
            <div class="preacher">
                <i class="fas fa-user"></i>
                <?= sanitize(($sermon['preacher_title'] ?? '') . ' ' . ($sermon['preacher_name'] ?? 'Parish Priest')) ?>
                <?php if ($sermon['sermon_date']): ?>
                    &bull; <?= formatDate($sermon['sermon_date']) ?>
                <?php endif; ?>
            </div>
            <div class="excerpt">
                <?= nl2br(sanitize(substr($sermon['content'] ?? '', 0, 500))) ?>
                <?php if (strlen($sermon['content'] ?? '') > 500): ?>...<?php endif; ?>
            </div>
            <div style="margin-top:1.5rem;">
                <a href="/holy-trinity/pages/sermons.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-book-open"></i> Read More Sermons
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Upcoming Events -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Upcoming Events</h2>
            <p>Don't miss out on our parish activities</p>
        </div>
        <?php if (empty($events)): ?>
            <div class="text-center" style="padding:3rem;">
                <i class="fas fa-calendar-alt" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                <p class="text-muted">No upcoming events at the moment. Check back soon!</p>
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
                            <span><i class="fas fa-clock"></i> <?= formatTime($event['start_time']) ?></span>
                        <?php endif; ?>
                        <?php if ($event['location']): ?>
                            <span><i class="fas fa-map-marker-alt"></i> <?= sanitize($event['location']) ?></span>
                        <?php endif; ?>
                        <?php if ($event['category']): ?>
                            <span><i class="fas fa-tag"></i> <?= sanitize($event['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p><?= sanitize(substr($event['description'] ?? '', 0, 120)) ?>...</p>
                    <?php if ($event['registration_required']): ?>
                        <a href="/holy-trinity/pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/holy-trinity/pages/events.php" class="btn btn-outline">
                <i class="fas fa-calendar-alt"></i> View All Events
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Announcements -->
<?php if (!empty($announcements)): ?>
<section class="section section-cream">
    <div class="container">
        <div class="section-header">
            <h2>Announcements</h2>
            <p>Stay informed about parish news and updates</p>
        </div>
        <div class="announcements-list">
            <?php foreach ($announcements as $ann): ?>
            <div class="announcement-item <?= $ann['priority'] ?>">
                <i class="fas fa-bullhorn"></i>
                <div>
                    <h4><?= sanitize($ann['title']) ?></h4>
                    <p><?= sanitize(substr($ann['content'], 0, 200)) ?></p>
                    <span class="date"><i class="fas fa-calendar"></i> <?= formatDate($ann['created_at']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Our Clergy -->
<?php if (!empty($clergy)): ?>
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Our Clergy</h2>
            <p>Meet the shepherds of our parish</p>
        </div>
        <div class="clergy-grid">
            <?php foreach ($clergy as $c): ?>
            <div class="clergy-card">
                <div class="clergy-photo">
                    <?php if ($c['photo']): ?>
                        <img src="/holy-trinity/uploads/clergy/<?= $c['photo'] ?>" alt="<?= sanitize($c['full_name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="clergy-info">
                    <span class="title"><?= sanitize($c['title']) ?></span>
                    <h3><?= sanitize($c['full_name']) ?></h3>
                    <p class="position"><?= sanitize($c['position']) ?></p>
                    <p><?= sanitize(substr($c['bio'] ?? '', 0, 150)) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/holy-trinity/pages/about.php" class="btn btn-outline">
                <i class="fas fa-church"></i> Learn More About Us
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<section class="section section-dark" style="text-align:center;">
    <div class="container">
        <div style="max-width:700px; margin:0 auto;">
            <i class="fas fa-cross" style="font-size:2.5rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
            <h2 style="color:var(--white); font-size:2rem; margin-bottom:1rem;">Join Our Parish Family</h2>
            <p style="color:rgba(255,255,255,0.8); font-size:1.1rem; margin-bottom:2rem;">
                Whether you're new to the area or looking for a spiritual home, we welcome you with open arms. Register today and become part of our vibrant faith community.
            </p>
            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="/holy-trinity/auth/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> Register Now
                </a>
                <a href="/holy-trinity/pages/contact.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
