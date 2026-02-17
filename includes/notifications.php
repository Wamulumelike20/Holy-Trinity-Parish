<?php
/**
 * Notification Bell & Panel - Include this in any dashboard
 * Requires: user to be logged in, config/app.php loaded
 */
if (!isLoggedIn()) return;

$notifications = getNotifications(20);
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
$db = Database::getInstance();
?>

<!-- Notification Bell -->
<div class="notification-wrapper" style="position:relative; display:inline-block;">
    <button class="btn btn-sm btn-outline notification-bell" onclick="toggleNotificationPanel()" style="position:relative; padding:0.5rem 0.75rem;">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge" style="position:absolute; top:-4px; right:-4px; background:var(--error); color:#fff; font-size:0.65rem; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </button>

    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel" style="display:none; position:absolute; right:0; top:100%; margin-top:0.5rem; width:380px; max-height:480px; background:var(--white); border-radius:var(--radius); box-shadow:0 10px 40px rgba(0,0,0,0.15); z-index:1000; overflow:hidden;">
        <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--light-gray); display:flex; justify-content:space-between; align-items:center;">
            <h4 style="margin:0; font-size:0.95rem;"><i class="fas fa-bell" style="color:var(--gold);"></i> Notifications</h4>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="/holy-trinity/api/notifications.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" style="background:none; border:none; color:var(--primary); font-size:0.8rem; cursor:pointer; text-decoration:underline;">Mark all read</button>
                </form>
            <?php endif; ?>
        </div>
        <div style="overflow-y:auto; max-height:380px;">
            <?php if (empty($notifications)): ?>
                <div style="padding:2rem; text-align:center; color:var(--gray);">
                    <i class="fas fa-bell-slash" style="font-size:2rem; margin-bottom:0.5rem; display:block; color:var(--light-gray);"></i>
                    <p style="margin:0; font-size:0.9rem;">No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <?php
                    $typeColors = ['info' => '#3b82f6', 'success' => '#10b981', 'warning' => '#f59e0b', 'urgent' => '#ef4444', 'report' => '#7c3aed'];
                    $typeIcons = ['info' => 'fa-info-circle', 'success' => 'fa-check-circle', 'warning' => 'fa-exclamation-triangle', 'urgent' => 'fa-exclamation-circle', 'report' => 'fa-file-alt'];
                    $color = $typeColors[$n['type']] ?? '#3b82f6';
                    $icon = $typeIcons[$n['type']] ?? 'fa-bell';
                    ?>
                    <a href="<?= $n['link'] ? sanitize($n['link']) : '#' ?>" style="display:block; padding:0.85rem 1.25rem; border-bottom:1px solid var(--light-gray); text-decoration:none; color:inherit; background:<?= !$n['is_read'] ? 'rgba(212,168,67,0.06)' : 'transparent' ?>; transition:background 0.2s;" onmouseover="this.style.background='var(--off-white)'" onmouseout="this.style.background='<?= !$n['is_read'] ? 'rgba(212,168,67,0.06)' : 'transparent' ?>'">
                        <div style="display:flex; gap:0.75rem;">
                            <div style="width:32px; height:32px; border-radius:50%; background:<?= $color ?>15; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:0.1rem;">
                                <i class="fas <?= $icon ?>" style="font-size:0.8rem; color:<?= $color ?>;"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                                    <strong style="font-size:0.85rem; color:var(--text-dark); <?= !$n['is_read'] ? 'font-weight:700;' : 'font-weight:500;' ?>"><?= sanitize($n['title']) ?></strong>
                                    <?php if (!$n['is_read']): ?>
                                        <span style="width:8px; height:8px; border-radius:50%; background:var(--gold); flex-shrink:0; margin-top:0.35rem;"></span>
                                    <?php endif; ?>
                                </div>
                                <p style="margin:0.2rem 0 0; font-size:0.8rem; color:var(--gray); line-height:1.4;"><?= sanitize(substr($n['message'], 0, 100)) ?><?= strlen($n['message']) > 100 ? '...' : '' ?></p>
                                <div style="font-size:0.7rem; color:var(--text-light); margin-top:0.3rem;">
                                    <?php if ($n['sender_first']): ?><?= sanitize($n['sender_first']) ?> &bull; <?php endif; ?>
                                    <?= formatDate($n['created_at'], 'M d, g:i A') ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleNotificationPanel() {
    const panel = document.getElementById('notificationPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notification-wrapper');
    const panel = document.getElementById('notificationPanel');
    if (wrapper && panel && !wrapper.contains(e.target)) {
        panel.style.display = 'none';
    }
});
</script>
