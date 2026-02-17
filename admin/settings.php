<?php
$pageTitle = 'System Settings';
require_once __DIR__ . '/../config/app.php';
requireLogin();
requireRole(['admin', 'super_admin']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        if ($existing) {
            $db->update('settings', ['setting_value' => sanitize($value)], 'setting_key = ?', [$key]);
        }
    }
    logAudit('settings_updated', 'settings');
    setFlash('success', 'Settings updated successfully.');
    redirect('/holy-trinity/admin/settings.php');
}

$settings = $db->fetchAll("SELECT * FROM settings ORDER BY setting_group, setting_key");
$grouped = [];
foreach ($settings as $s) {
    $grouped[$s['setting_group']][] = $s;
}
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>HTP Admin</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Dashboard</div>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
                <div class="sidebar-section">Management</div>
                <a href="/holy-trinity/admin/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                <a href="/holy-trinity/admin/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/admin/donations.php"><i class="fas fa-hand-holding-heart"></i> Donations</a>
                <a href="/holy-trinity/admin/events.php"><i class="fas fa-calendar-alt"></i> Events</a>
                <a href="/holy-trinity/admin/announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
                <div class="sidebar-section">Organization</div>
                <a href="/holy-trinity/admin/departments.php"><i class="fas fa-building"></i> Departments</a>
                <a href="/holy-trinity/admin/users.php"><i class="fas fa-users"></i> Users</a>
                <div class="sidebar-section">System</div>
                <a href="/holy-trinity/admin/settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                <a href="/holy-trinity/admin/audit-log.php"><i class="fas fa-history"></i> Audit Log</a>
                <a href="/holy-trinity/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <div class="sidebar-section">Account</div>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> View Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <button class="btn btn-sm btn-outline" id="sidebarToggle" style="display:none; margin-bottom:1rem;"><i class="fas fa-bars"></i> Menu</button>

            <div class="dashboard-header">
                <div>
                    <h1><i class="fas fa-cog"></i> System Settings</h1>
                    <p class="text-muted">Configure parish system settings</p>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <?php
                $groupIcons = ['general' => 'fa-globe', 'contact' => 'fa-address-book', 'finance' => 'fa-coins', 'system' => 'fa-server'];
                foreach ($grouped as $group => $items):
                ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas <?= $groupIcons[$group] ?? 'fa-cog' ?>"></i> <?= ucfirst($group) ?> Settings</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($items as $item): ?>
                        <div class="form-group">
                            <label style="text-transform:capitalize;"><?= str_replace('_', ' ', $item['setting_key']) ?></label>
                            <?php if (strlen($item['setting_value'] ?? '') > 100): ?>
                                <textarea name="settings[<?= sanitize($item['setting_key']) ?>]" class="form-control" rows="3"><?= sanitize($item['setting_value']) ?></textarea>
                            <?php elseif ($item['setting_value'] === '0' || $item['setting_value'] === '1'): ?>
                                <select name="settings[<?= sanitize($item['setting_key']) ?>]" class="form-control">
                                    <option value="0" <?= $item['setting_value'] === '0' ? 'selected' : '' ?>>Disabled</option>
                                    <option value="1" <?= $item['setting_value'] === '1' ? 'selected' : '' ?>>Enabled</option>
                                </select>
                            <?php else: ?>
                                <input type="text" name="settings[<?= sanitize($item['setting_key']) ?>]" class="form-control" value="<?= sanitize($item['setting_value']) ?>">
                            <?php endif; ?>
                            <div class="form-text">Key: <code><?= sanitize($item['setting_key']) ?></code> &bull; Last updated: <?= formatDate($item['updated_at']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="display:flex; gap:1rem; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Settings</button>
                </div>
            </form>
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
