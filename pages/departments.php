<?php
$pageTitle = 'Departments';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$departments = $db->fetchAll(
    "SELECT d.*, u.first_name as head_first, u.last_name as head_last,
     (SELECT COUNT(*) FROM department_members WHERE department_id = d.id) as member_count
     FROM departments d LEFT JOIN users u ON d.head_user_id = u.id
     WHERE d.is_active = 1 ORDER BY d.name"
);

$icons = [
    'parish-office' => 'fa-building',
    'finance' => 'fa-coins',
    'catechism' => 'fa-book-bible',
    'youth-ministry' => 'fa-child',
    'choir' => 'fa-music',
    'marriage-family' => 'fa-heart',
    'social-outreach' => 'fa-hands-helping',
];
?>

<section class="page-banner">
    <h1><i class="fas fa-building"></i> Parish Departments</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a><span>/</span><span>Departments</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Our Departments</h2>
            <p>The organizational units that keep our parish running smoothly</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:2rem;">
            <?php foreach ($departments as $dept): ?>
            <div class="card">
                <div class="card-body" style="padding:2rem;">
                    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem;">
                        <div style="width:56px; height:56px; border-radius:var(--radius); background:rgba(212,168,67,0.15); display:flex; align-items:center; justify-content:center;">
                            <i class="fas <?= $icons[$dept['slug']] ?? 'fa-building' ?>" style="font-size:1.5rem; color:var(--gold);"></i>
                        </div>
                        <div>
                            <h3 style="margin-bottom:0; font-size:1.15rem;"><?= sanitize($dept['name']) ?></h3>
                            <span class="text-muted" style="font-size:0.85rem;"><?= $dept['member_count'] ?> members</span>
                        </div>
                    </div>
                    <p style="font-size:0.95rem; color:var(--gray);"><?= sanitize($dept['description'] ?? '') ?></p>
                    <?php if ($dept['head_first']): ?>
                        <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid var(--light-gray); font-size:0.9rem;">
                            <strong>Head:</strong> <?= sanitize($dept['head_first'] . ' ' . $dept['head_last']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($dept['email']): ?>
                        <div style="font-size:0.85rem; color:var(--text-light); margin-top:0.5rem;">
                            <i class="fas fa-envelope" style="color:var(--gold);"></i> <?= sanitize($dept['email']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
