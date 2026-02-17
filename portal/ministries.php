<?php
$pageTitle = 'My Ministries';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();

// Handle join/leave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = sanitize($_POST['action'] ?? '');
    $ministryId = intval($_POST['ministry_id'] ?? 0);

    if ($action === 'join' && $ministryId) {
        $exists = $db->fetch("SELECT id FROM ministry_members WHERE ministry_id = ? AND user_id = ?", [$ministryId, $_SESSION['user_id']]);
        if (!$exists) {
            $db->insert('ministry_members', ['ministry_id' => $ministryId, 'user_id' => $_SESSION['user_id'], 'role' => 'member']);
            logAudit('ministry_joined', 'ministry', $ministryId);
            setFlash('success', 'You have joined the ministry!');
        }
    } elseif ($action === 'leave' && $ministryId) {
        $db->delete('ministry_members', 'ministry_id = ? AND user_id = ?', [$ministryId, $_SESSION['user_id']]);
        logAudit('ministry_left', 'ministry', $ministryId);
        setFlash('success', 'You have left the ministry.');
    }
    redirect('/holy-trinity/portal/ministries.php');
}

$myMinistries = $db->fetchAll(
    "SELECT m.*, mm.role as member_role, mm.joined_at FROM ministry_members mm
     JOIN ministries m ON mm.ministry_id = m.id WHERE mm.user_id = ? ORDER BY m.name",
    [$_SESSION['user_id']]
);

$availableMinistries = $db->fetchAll(
    "SELECT m.*, (SELECT COUNT(*) FROM ministry_members WHERE ministry_id = m.id) as member_count
     FROM ministries m WHERE m.is_active = 1 AND m.id NOT IN (SELECT ministry_id FROM ministry_members WHERE user_id = ?)
     ORDER BY m.name",
    [$_SESSION['user_id']]
);
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Holy Trinity</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Main</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/holy-trinity/portal/profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="/holy-trinity/portal/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="/holy-trinity/portal/donations.php"><i class="fas fa-hand-holding-heart"></i> My Donations</a>
                <div class="sidebar-section">Faith Life</div>
                <a href="/holy-trinity/portal/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/portal/ministries.php" class="active"><i class="fas fa-people-group"></i> My Ministries</a>
                <div class="sidebar-section">Quick Actions</div>
                <a href="/holy-trinity/appointments/book.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                <a href="/holy-trinity/donations/donate.php"><i class="fas fa-donate"></i> Make Donation</a>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> Visit Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <h1><i class="fas fa-people-group"></i> My Ministries</h1>
            </div>

            <!-- My Ministries -->
            <h3 style="margin-bottom:1rem;">Ministries I've Joined (<?= count($myMinistries) ?>)</h3>
            <?php if (empty($myMinistries)): ?>
                <div class="card mb-4">
                    <div class="card-body text-center" style="padding:3rem;">
                        <i class="fas fa-people-group" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                        <p class="text-muted">You haven't joined any ministries yet. Browse available ministries below.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1rem; margin-bottom:2rem;">
                    <?php foreach ($myMinistries as $min): ?>
                    <div class="card">
                        <div class="card-body">
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                                <div style="width:50px; height:50px; border-radius:var(--radius); background:rgba(212,168,67,0.15); display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-hands-praying" style="color:var(--gold); font-size:1.3rem;"></i>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:0; font-family:var(--font-body);"><?= sanitize($min['name']) ?></h4>
                                    <span class="badge badge-primary"><?= ucfirst($min['member_role']) ?></span>
                                </div>
                            </div>
                            <p style="font-size:0.9rem; color:var(--gray);"><?= sanitize(substr($min['description'] ?? '', 0, 120)) ?></p>
                            <?php if ($min['meeting_schedule']): ?>
                                <p style="font-size:0.85rem; color:var(--text-light);"><i class="fas fa-clock" style="color:var(--gold);"></i> <?= sanitize($min['meeting_schedule']) ?></p>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                                <span class="text-muted" style="font-size:0.8rem;">Joined: <?= formatDate($min['joined_at']) ?></span>
                                <form method="POST" onsubmit="return confirm('Leave this ministry?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="ministry_id" value="<?= $min['id'] ?>">
                                    <button class="btn btn-sm btn-outline" style="color:var(--error); border-color:var(--error);"><i class="fas fa-sign-out-alt"></i> Leave</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Available Ministries -->
            <?php if (!empty($availableMinistries)): ?>
            <h3 style="margin-bottom:1rem;">Available Ministries</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1rem;">
                <?php foreach ($availableMinistries as $min): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                            <div style="width:50px; height:50px; border-radius:var(--radius); background:rgba(26,58,92,0.1); display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-hands-praying" style="color:var(--primary); font-size:1.3rem;"></i>
                            </div>
                            <div>
                                <h4 style="margin-bottom:0; font-family:var(--font-body);"><?= sanitize($min['name']) ?></h4>
                                <span class="text-muted" style="font-size:0.8rem;"><?= $min['member_count'] ?> members</span>
                            </div>
                        </div>
                        <p style="font-size:0.9rem; color:var(--gray);"><?= sanitize(substr($min['description'] ?? '', 0, 120)) ?></p>
                        <?php if ($min['meeting_schedule']): ?>
                            <p style="font-size:0.85rem; color:var(--text-light);"><i class="fas fa-clock" style="color:var(--gold);"></i> <?= sanitize($min['meeting_schedule']) ?></p>
                        <?php endif; ?>
                        <form method="POST" style="margin-top:1rem;">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="join">
                            <input type="hidden" name="ministry_id" value="<?= $min['id'] ?>">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-user-plus"></i> Join Ministry</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
