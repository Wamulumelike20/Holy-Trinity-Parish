<?php
$pageTitle = 'Sermons & Reflections';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$sermons = $db->fetchAll(
    "SELECT s.*, c.full_name as preacher_name, c.title as preacher_title
     FROM sermons s LEFT JOIN clergy c ON s.preacher_id = c.id
     ORDER BY s.sermon_date DESC"
);
?>

<section class="page-banner">
    <h1><i class="fas fa-bible"></i> Sermons & Reflections</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a><span>/</span><span>Sermons</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($sermons)): ?>
            <div class="text-center" style="padding:4rem;">
                <i class="fas fa-bible" style="font-size:4rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                <h3>No Sermons Available</h3>
                <p class="text-muted">Sermons and reflections will be posted here. Check back soon!</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:2rem;">
                <?php foreach ($sermons as $sermon): ?>
                <div class="sermon-featured">
                    <?php if ($sermon['scripture_reference']): ?>
                        <div class="scripture"><i class="fas fa-bible"></i> <?= sanitize($sermon['scripture_reference']) ?></div>
                    <?php endif; ?>
                    <h3><?= sanitize($sermon['title']) ?></h3>
                    <div class="preacher">
                        <i class="fas fa-user"></i>
                        <?= sanitize(($sermon['preacher_title'] ?? '') . ' ' . ($sermon['preacher_name'] ?? 'Parish Priest')) ?>
                        <?php if ($sermon['sermon_date']): ?>
                            &bull; <i class="fas fa-calendar"></i> <?= formatDate($sermon['sermon_date']) ?>
                        <?php endif; ?>
                        &bull; <i class="fas fa-eye"></i> <?= number_format($sermon['views']) ?> views
                    </div>
                    <div class="excerpt"><?= nl2br(sanitize($sermon['content'] ?? '')) ?></div>
                    <?php if ($sermon['audio_url'] || $sermon['video_url']): ?>
                        <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                            <?php if ($sermon['audio_url']): ?>
                                <a href="<?= sanitize($sermon['audio_url']) ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-headphones"></i> Listen</a>
                            <?php endif; ?>
                            <?php if ($sermon['video_url']): ?>
                                <a href="<?= sanitize($sermon['video_url']) ?>" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-video"></i> Watch</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
