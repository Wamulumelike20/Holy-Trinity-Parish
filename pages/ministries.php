<?php
$pageTitle = 'Ministries';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$ministries = $db->fetchAll(
    "SELECT m.*, u.first_name as leader_first, u.last_name as leader_last,
     (SELECT COUNT(*) FROM ministry_members WHERE ministry_id = m.id) as member_count
     FROM ministries m LEFT JOIN users u ON m.leader_id = u.id
     WHERE m.is_active = 1 ORDER BY m.name"
);
?>

<section class="page-banner">
    <h1><i class="fas fa-hands-praying"></i> Our Ministries</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a><span>/</span><span>Ministries</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Get Involved</h2>
            <p>Discover how you can serve and grow in faith through our parish ministries</p>
        </div>

        <?php if (empty($ministries)): ?>
            <div class="text-center" style="padding:4rem;">
                <i class="fas fa-people-group" style="font-size:4rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                <h3>Ministries Coming Soon</h3>
                <p class="text-muted">Our ministry listings are being updated. Check back soon!</p>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:2rem;">
                <?php foreach ($ministries as $min): ?>
                <div class="card">
                    <div style="background:linear-gradient(135deg, var(--primary), var(--primary-light)); padding:2rem; text-align:center; color:var(--white);">
                        <i class="fas fa-hands-praying" style="font-size:2.5rem; color:var(--gold); margin-bottom:0.5rem; display:block;"></i>
                        <h3 style="color:var(--white); margin-bottom:0;"><?= sanitize($min['name']) ?></h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size:0.95rem; color:var(--gray); min-height:60px;"><?= sanitize($min['description'] ?? 'A vibrant ministry serving our parish community.') ?></p>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; font-size:0.85rem; color:var(--text-light); margin-top:1rem;">
                            <?php if ($min['leader_first']): ?>
                                <span><i class="fas fa-user" style="color:var(--gold); width:20px;"></i> Led by: <?= sanitize($min['leader_first'] . ' ' . $min['leader_last']) ?></span>
                            <?php endif; ?>
                            <?php if ($min['meeting_schedule']): ?>
                                <span><i class="fas fa-clock" style="color:var(--gold); width:20px;"></i> <?= sanitize($min['meeting_schedule']) ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-users" style="color:var(--gold); width:20px;"></i> <?= $min['member_count'] ?> members</span>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <a href="/holy-trinity/portal/ministries.php" class="btn btn-sm btn-primary mt-2" style="width:100%;"><i class="fas fa-user-plus"></i> Join Ministry</a>
                        <?php else: ?>
                            <a href="/holy-trinity/auth/register.php" class="btn btn-sm btn-outline mt-2" style="width:100%;"><i class="fas fa-user-plus"></i> Register to Join</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
