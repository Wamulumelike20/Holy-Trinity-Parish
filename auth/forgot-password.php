<?php
require_once __DIR__ . '/../config/app.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = Database::getInstance();
            $user = $db->fetch("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $db->update('users', [
                    'reset_token' => $token,
                    'reset_token_expiry' => $expiry
                ], 'id = ?', [$user['id']]);
                logAudit('password_reset_request', 'user', $user['id']);
            }
            $message = 'If an account with that email exists, a password reset link has been sent.';
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
    <title>Forgot Password | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-key"></i>
                <h2>Forgot Password</h2>
                <p>Enter your email to receive a reset link</p>
            </div>

            <?php if ($message): ?>
                <div class="flash-message flash-success" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <i class="fas fa-check-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash-message flash-error" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>

            <div class="auth-links">
                Remember your password? <a href="/holy-trinity/auth/login.php">Sign in</a>
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
