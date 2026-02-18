<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    redirect('/holy-trinity/portal/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = Database::getInstance();
            $user = $db->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Log the action
                logAudit('login', 'user', $user['id']);

                setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');

                // Redirect based on role
                if (in_array($user['role'], ['admin', 'super_admin'])) {
                    redirect('/holy-trinity/admin/dashboard.php');
                } elseif ($user['role'] === 'priest') {
                    redirect('/holy-trinity/priest/dashboard.php');
                } elseif ($user['role'] === 'department_head') {
                    // Find their department and redirect to its dashboard
                    $userDept = $db->fetch("SELECT d.slug FROM departments d WHERE d.head_user_id = ? AND d.is_active = 1 LIMIT 1", [$user['id']]);
                    if (!$userDept) {
                        $userDept = $db->fetch(
                            "SELECT d.slug FROM departments d INNER JOIN department_members dm ON dm.department_id = d.id WHERE dm.user_id = ? AND d.is_active = 1 LIMIT 1",
                            [$user['id']]
                        );
                    }
                    if ($userDept) {
                        redirect('/holy-trinity/department/dashboards/' . $userDept['slug'] . '.php');
                    } else {
                        redirect('/holy-trinity/department/dashboard.php');
                    }
                } else {
                    redirect('/holy-trinity/portal/dashboard.php');
                }
            } else {
                $error = 'Invalid email or password.';
                logAudit('login_failed', 'user', null, null, json_encode(['email' => $email]));
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
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-cross"></i>
                <h2>Welcome Back</h2>
                <p>Sign in to your Holy Trinity Parish account</p>
            </div>

            <?php if ($error): ?>
                <div class="flash-message flash-error" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; font-size:0.9rem;">
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="/holy-trinity/auth/forgot-password.php" style="color:var(--primary); font-weight:500;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="auth-links">
                Don't have an account? <a href="/holy-trinity/auth/register.php">Register here</a>
            </div>

            <div style="text-align:center; margin-top:2rem;">
                <a href="/holy-trinity/index.php" style="color:var(--text-light); font-size:0.85rem;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
