<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$user = currentUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = sanitize($_POST['form_action'] ?? '');

        if ($action === 'update_profile') {
            $data = [
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'gender' => sanitize($_POST['gender'] ?? '') ?: null,
                'date_of_birth' => $_POST['date_of_birth'] ?: null,
                'address' => sanitize($_POST['address'] ?? '') ?: null,
                'emergency_contact' => sanitize($_POST['emergency_contact'] ?? '') ?: null,
            ];

            if (empty($data['first_name']) || empty($data['last_name'])) {
                $error = 'First name and last name are required.';
            } else {
                $db->update('users', $data, 'id = ?', [$user['id']]);
                $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
                logAudit('profile_updated', 'user', $user['id']);
                $success = 'Profile updated successfully.';
                $user = currentUser();
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPassword, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } else {
                $db->update('users', ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
                logAudit('password_changed', 'user', $user['id']);
                $success = 'Password changed successfully.';
            }
        }
    }
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
            <div class="sidebar-brand"><i class="fas fa-cross"></i><span>Holy Trinity</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Main</div>
                <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/holy-trinity/portal/profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>
                <a href="/holy-trinity/portal/appointments.php"><i class="fas fa-calendar-check"></i> My Appointments</a>
                <a href="/holy-trinity/portal/donations.php"><i class="fas fa-hand-holding-heart"></i> My Donations</a>
                <div class="sidebar-section">Faith Life</div>
                <a href="/holy-trinity/portal/sacraments.php"><i class="fas fa-dove"></i> Sacramental Records</a>
                <a href="/holy-trinity/portal/ministries.php"><i class="fas fa-people-group"></i> My Ministries</a>
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
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            </div>

            <?php if ($success): ?>
                <div class="flash-message flash-success" style="margin-bottom:1.5rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="flash-message flash-error" style="margin-bottom:1.5rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="grid-2">
                <!-- Profile Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-edit"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" data-validate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="form_action" value="update_profile">

                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name']) ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                                <div class="form-text">Email cannot be changed. Contact admin for assistance.</div>
                            </div>

                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select</option>
                                        <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?= $user['date_of_birth'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= sanitize($user['address'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" placeholder="Name & phone number" value="<?= sanitize($user['emergency_contact'] ?? '') ?>">
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password & Account Info -->
                <div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" data-validate>
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="form_action" value="change_password">

                                <div class="form-group">
                                    <label>Current Password <span class="required">*</span></label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password <span class="required">*</span></label>
                                    <input type="password" name="new_password" class="form-control" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-accent"><i class="fas fa-key"></i> Change Password</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Account Info</h3>
                        </div>
                        <div class="card-body" style="font-size:0.9rem;">
                            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="text-muted">Account Type</span>
                                    <span class="badge badge-primary"><?= ucfirst($user['role']) ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="text-muted">Status</span>
                                    <span class="badge badge-<?= $user['is_active'] ? 'success' : 'error' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="text-muted">Email Verified</span>
                                    <span class="badge badge-<?= $user['email_verified'] ? 'success' : 'warning' ?>"><?= $user['email_verified'] ? 'Yes' : 'No' ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="text-muted">Member Since</span>
                                    <span><?= formatDate($user['created_at']) ?></span>
                                </div>
                                <?php if ($user['last_login']): ?>
                                <div style="display:flex; justify-content:space-between;">
                                    <span class="text-muted">Last Login</span>
                                    <span><?= formatDate($user['last_login'], 'M d, Y g:i A') ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
