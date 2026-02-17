<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    redirect('/holy-trinity/portal/dashboard.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $old = $_POST;
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $gender = sanitize($_POST['gender'] ?? '');
        $dob = sanitize($_POST['date_of_birth'] ?? '');

        if (empty($firstName)) $errors[] = 'First name is required.';
        if (empty($lastName)) $errors[] = 'Last name is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $verificationToken = bin2hex(random_bytes(32));
                $userId = $db->insert('users', [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'parishioner',
                    'gender' => $gender ?: null,
                    'date_of_birth' => $dob ?: null,
                    'verification_token' => $verificationToken,
                    'is_active' => 1,
                    'email_verified' => 0,
                ]);

                logAudit('register', 'user', $userId);
                setFlash('success', 'Registration successful! You can now log in.');
                redirect('/holy-trinity/auth/login.php');
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
    <title>Register | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card" style="max-width:560px;">
            <div class="auth-header">
                <i class="fas fa-cross"></i>
                <h2>Create Account</h2>
                <p>Join the Holy Trinity Parish community</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="flash-message flash-error" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:var(--radius);">
                    <div>
                        <?php foreach ($errors as $err): ?>
                            <div><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" placeholder="First name" value="<?= sanitize($old['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" placeholder="Last name" value="<?= sanitize($old['last_name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" value="<?= sanitize($old['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+256-XXX-XXXXXXX" value="<?= sanitize($old['phone'] ?? '') ?>" data-validate-phone>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select gender</option>
                            <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= sanitize($old['date_of_birth'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                    <div class="form-text">Must be at least 8 characters long</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:flex; align-items:flex-start; gap:0.5rem; cursor:pointer; font-size:0.9rem;">
                        <input type="checkbox" required style="margin-top:0.3rem;">
                        I agree to the <a href="#" style="color:var(--primary); font-weight:500;">Terms of Service</a> and <a href="#" style="color:var(--primary); font-weight:500;">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-links">
                Already have an account? <a href="/holy-trinity/auth/login.php">Sign in here</a>
            </div>

            <div style="text-align:center; margin-top:2rem;">
                <a href="/holy-trinity/index.php" style="color:var(--text-light); font-size:0.85rem;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
