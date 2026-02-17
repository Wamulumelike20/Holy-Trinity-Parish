<?php
$pageTitle = 'Event Details';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$eventId = intval($_GET['id'] ?? 0);

if (!$eventId) {
    setFlash('error', 'Event not found.');
    redirect('/holy-trinity/pages/events.php');
}

$event = $db->fetch("SELECT * FROM events WHERE id = ? AND status = 'published'", [$eventId]);

if (!$event) {
    setFlash('error', 'Event not found.');
    redirect('/holy-trinity/pages/events.php');
}

$registrationCount = $db->fetch("SELECT COALESCE(SUM(num_attendees),0) as cnt FROM event_registrations WHERE event_id = ? AND status != 'cancelled'", [$eventId])['cnt'];
$spotsLeft = $event['max_attendees'] > 0 ? $event['max_attendees'] - $registrationCount : null;

$success = '';
$error = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $guestName = sanitize($_POST['guest_name'] ?? '');
        $guestEmail = sanitize($_POST['guest_email'] ?? '');
        $guestPhone = sanitize($_POST['guest_phone'] ?? '');
        $numAttendees = max(1, intval($_POST['num_attendees'] ?? 1));

        if (empty($guestName) && !isLoggedIn()) {
            $error = 'Please provide your name.';
        } elseif ($spotsLeft !== null && $numAttendees > $spotsLeft) {
            $error = 'Not enough spots available.';
        } else {
            $db->insert('event_registrations', [
                'event_id' => $eventId,
                'user_id' => isLoggedIn() ? $_SESSION['user_id'] : null,
                'guest_name' => $guestName ?: ($_SESSION['user_name'] ?? ''),
                'guest_email' => $guestEmail ?: ($_SESSION['user_email'] ?? ''),
                'guest_phone' => $guestPhone,
                'num_attendees' => $numAttendees,
                'status' => 'registered',
            ]);
            logAudit('event_registered', 'event', $eventId);
            $success = 'You have been registered for this event!';
            $registrationCount += $numAttendees;
            if ($spotsLeft !== null) $spotsLeft -= $numAttendees;
        }
    }
}
?>

<section class="page-banner">
    <h1><i class="fas fa-calendar-alt"></i> Event Details</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a><span>/</span>
        <a href="/holy-trinity/pages/events.php">Events</a><span>/</span>
        <span><?= sanitize(substr($event['title'], 0, 30)) ?></span>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($success): ?>
            <div class="flash-message flash-success" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-message flash-error" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 350px; gap:2rem;" class="grid-2">
            <!-- Event Details -->
            <div>
                <div class="card">
                    <?php if ($event['image']): ?>
                        <img src="/holy-trinity/uploads/events/<?= sanitize($event['image']) ?>" alt="<?= sanitize($event['title']) ?>" style="width:100%; max-height:400px; object-fit:cover;">
                    <?php else: ?>
                        <div style="background:linear-gradient(135deg, var(--primary), var(--accent)); padding:3rem; text-align:center; color:var(--white);">
                            <i class="fas fa-calendar-alt" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                            <div style="font-family:var(--font-heading); font-size:2rem;"><?= date('M d', strtotime($event['event_date'])) ?></div>
                            <div style="font-size:1.2rem;"><?= date('Y', strtotime($event['event_date'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="card-body" style="padding:2rem;">
                        <?php if ($event['category']): ?>
                            <span class="badge badge-gold mb-2"><?= sanitize($event['category']) ?></span>
                        <?php endif; ?>
                        <h2 style="font-size:1.75rem; margin-bottom:1rem;"><?= sanitize($event['title']) ?></h2>

                        <div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:1.5rem; font-size:0.95rem; color:var(--gray);">
                            <span><i class="fas fa-calendar" style="color:var(--gold);"></i> <?= formatDate($event['event_date'], 'l, F j, Y') ?></span>
                            <?php if ($event['start_time']): ?>
                                <span><i class="fas fa-clock" style="color:var(--gold);"></i> <?= formatTime($event['start_time']) ?>
                                <?php if ($event['end_time']): ?> - <?= formatTime($event['end_time']) ?><?php endif; ?></span>
                            <?php endif; ?>
                            <?php if ($event['location']): ?>
                                <span><i class="fas fa-map-marker-alt" style="color:var(--gold);"></i> <?= sanitize($event['location']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div style="line-height:2; font-size:1rem; color:var(--text);">
                            <?= nl2br(sanitize($event['description'] ?? '')) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Event Info Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Event Info</h3>
                    </div>
                    <div class="card-body" style="font-size:0.9rem;">
                        <div style="display:flex; flex-direction:column; gap:0.75rem;">
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted">Date</span>
                                <strong><?= formatDate($event['event_date']) ?></strong>
                            </div>
                            <?php if ($event['start_time']): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted">Time</span>
                                <strong><?= formatTime($event['start_time']) ?><?php if ($event['end_time']): ?> - <?= formatTime($event['end_time']) ?><?php endif; ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if ($event['location']): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted">Location</span>
                                <strong><?= sanitize($event['location']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted">Registered</span>
                                <strong><?= $registrationCount ?> attendees</strong>
                            </div>
                            <?php if ($spotsLeft !== null): ?>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-muted">Spots Left</span>
                                <strong style="color:<?= $spotsLeft > 0 ? 'var(--success)' : 'var(--error)' ?>;"><?= $spotsLeft > 0 ? $spotsLeft : 'Full' ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Registration Form -->
                <?php if ($event['registration_required'] && strtotime($event['event_date']) >= strtotime('today') && ($spotsLeft === null || $spotsLeft > 0)): ?>
                <div class="card" id="register">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Register</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" data-validate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="register" value="1">

                            <?php if (!isLoggedIn()): ?>
                            <div class="form-group">
                                <label>Your Name <span class="required">*</span></label>
                                <input type="text" name="guest_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="guest_email" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="guest_phone" class="form-control">
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Number of Attendees</label>
                                <input type="number" name="num_attendees" class="form-control" value="1" min="1" max="<?= $spotsLeft ?? 10 ?>">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Register Now
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card mt-3">
                    <div class="card-body text-center" style="padding:1.5rem;">
                        <a href="/holy-trinity/pages/events.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
