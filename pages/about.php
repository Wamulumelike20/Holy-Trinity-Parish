<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$clergy = $db->fetchAll("SELECT * FROM clergy WHERE is_active = 1 ORDER BY display_order ASC");
?>

<!-- Page Banner -->
<section class="page-banner">
    <h1><i class="fas fa-church"></i> About Us</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a>
        <span>/</span>
        <span>About Us</span>
    </div>
</section>

<!-- Parish History -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Our Parish History</h2>
            <p>A legacy of faith, service, and community</p>
        </div>
        <div style="max-width:900px; margin:0 auto;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:center;" class="grid-2">
                <div>
                    <h3 style="color:var(--primary);">A Community Built on Faith</h3>
                    <p>Holy Trinity Parish was established as a beacon of Catholic faith in the heart of Lusaka. From its humble beginnings, the parish has grown into a vibrant community of believers united in their devotion to the Holy Trinity — Father, Son, and Holy Spirit.</p>
                    <p>Over the decades, our parish has been a place of worship, learning, and service. We have witnessed countless baptisms, confirmations, marriages, and celebrations of the Eucharist that have strengthened the faith of our community.</p>
                    <p>Today, Holy Trinity Parish continues to be a spiritual home for thousands of families, offering a wide range of ministries, programs, and services that cater to the spiritual, social, and material needs of our parishioners and the wider community.</p>
                </div>
                <div style="background:linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius:var(--radius-lg); padding:3rem; text-align:center; color:var(--white);">
                    <i class="fas fa-cross" style="font-size:4rem; color:var(--gold); margin-bottom:1.5rem; display:block;"></i>
                    <h3 style="color:var(--white); font-size:1.3rem;">Our Patron</h3>
                    <h2 style="color:var(--gold-light); font-size:1.8rem;">The Holy Trinity</h2>
                    <p style="font-style:italic; color:rgba(255,255,255,0.85); margin-top:1rem;">
                        "Go therefore and make disciples of all nations, baptizing them in the name of the Father and of the Son and of the Holy Spirit."
                    </p>
                    <p style="color:var(--gold-light); font-size:0.9rem;">— Matthew 28:19</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="section section-cream">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;" class="grid-2">
            <div class="card">
                <div class="card-body" style="text-align:center; padding:2.5rem;">
                    <i class="fas fa-bullseye" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                    <h3>Our Mission</h3>
                    <p style="color:var(--gray); font-size:1rem; line-height:1.9;">
                        To proclaim the Gospel of Jesus Christ, celebrate the sacraments, and build a community of faith through worship, education, and service. We strive to be a welcoming parish where all people can encounter the love of God and grow in holiness.
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-body" style="text-align:center; padding:2.5rem;">
                    <i class="fas fa-eye" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                    <h3>Our Vision</h3>
                    <p style="color:var(--gray); font-size:1rem; line-height:1.9;">
                        To be a vibrant, Spirit-filled Catholic community that transforms lives through the power of the Gospel. We envision a parish where every member is an active disciple of Christ, contributing their gifts to the building of God's Kingdom on earth.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Our Core Values</h2>
        </div>
        <div class="quick-actions" style="max-width:1000px; margin:0 auto;">
            <div class="quick-action-card">
                <i class="fas fa-pray"></i>
                <h4>Faith</h4>
                <p>Rooted in the teachings of the Catholic Church and Sacred Scripture</p>
            </div>
            <div class="quick-action-card">
                <i class="fas fa-heart"></i>
                <h4>Love</h4>
                <p>Showing Christ's love through compassion and service to all</p>
            </div>
            <div class="quick-action-card">
                <i class="fas fa-people-group"></i>
                <h4>Community</h4>
                <p>Building strong bonds of fellowship and mutual support</p>
            </div>
            <div class="quick-action-card">
                <i class="fas fa-hands-helping"></i>
                <h4>Service</h4>
                <p>Reaching out to those in need with generosity and humility</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Clergy -->
<section class="section section-cream">
    <div class="container">
        <div class="section-header">
            <h2>Our Clergy</h2>
            <p>Meet the shepherds who guide our parish family</p>
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
                    <p><?= sanitize($c['bio'] ?? '') ?></p>
                    <?php if ($c['ordination_date']): ?>
                        <p style="font-size:0.85rem; color:var(--text-light);">
                            <i class="fas fa-calendar"></i> Ordained: <?= formatDate($c['ordination_date']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Leadership Structure -->
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Parish Leadership</h2>
            <p>Our organizational structure</p>
        </div>
        <div style="max-width:800px; margin:0 auto;">
            <div class="card">
                <div class="card-body">
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="announcement-item" style="border-left-color:var(--gold);">
                            <i class="fas fa-crown" style="color:var(--gold);"></i>
                            <div>
                                <h4>Parish Priest</h4>
                                <p>Chief shepherd and administrator of the parish, responsible for spiritual leadership and overall management.</p>
                            </div>
                        </div>
                        <div class="announcement-item" style="border-left-color:var(--primary);">
                            <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                            <div>
                                <h4>Assistant Parish Priests</h4>
                                <p>Support the Parish Priest in pastoral duties, sacramental ministry, and parish programs.</p>
                            </div>
                        </div>
                        <div class="announcement-item" style="border-left-color:var(--accent);">
                            <i class="fas fa-users" style="color:var(--accent);"></i>
                            <div>
                                <h4>Parish Pastoral Council</h4>
                                <p>Advisory body that assists the Parish Priest in planning and decision-making for the parish community.</p>
                            </div>
                        </div>
                        <div class="announcement-item" style="border-left-color:var(--success);">
                            <i class="fas fa-building-columns" style="color:var(--success);"></i>
                            <div>
                                <h4>Parish Finance Committee</h4>
                                <p>Oversees the financial affairs of the parish, ensuring transparency and accountability.</p>
                            </div>
                        </div>
                        <div class="announcement-item" style="border-left-color:var(--info);">
                            <i class="fas fa-sitemap" style="color:var(--info);"></i>
                            <div>
                                <h4>Department Heads</h4>
                                <p>Lead various parish departments and ministries, coordinating activities and programs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
