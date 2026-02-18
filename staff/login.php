<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn() && isStaff()) {
    redirect('/holy-trinity/app/dashboard.php');
}

$error = '';
$staffTypes = [
    'guard' => ['label' => 'Security Guard', 'icon' => 'fa-shield-halved', 'color' => '#1a365d'],
    'house_helper' => ['label' => 'House Helper', 'icon' => 'fa-house-chimney', 'color' => '#7b2cbf'],
    'general_worker' => ['label' => 'General Worker', 'icon' => 'fa-hard-hat', 'color' => '#e85d04'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $staffType = sanitize($_POST['staff_type'] ?? '');

        if (empty($email) || empty($password) || empty($staffType)) {
            $error = 'Please fill in all fields.';
        } else {
            $db = Database::getInstance();
            $user = $db->fetch("SELECT * FROM users WHERE email = ? AND role = ? AND is_active = 1", [$email, $staffType]);

            if ($user && password_verify($password, $user['password_hash'])) {
                $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                session_regenerate_id(true);

                logAudit('staff_login', 'user', $user['id']);
                setFlash('success', 'Welcome, ' . $user['first_name'] . '!');
                redirect('/holy-trinity/app/dashboard.php');
            } else {
                $error = 'Invalid credentials or you are not registered as this staff type.';
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
    <title>Staff Login | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        .staff-login-page { min-height:100vh; background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#334155 100%); display:flex; align-items:center; justify-content:center; padding:2rem; }
        .staff-login-container { max-width:500px; width:100%; }
        .staff-login-header { text-align:center; margin-bottom:2rem; color:#fff; }
        .staff-login-header h1 { font-family:'Cinzel',serif; font-size:1.6rem; margin:0.5rem 0; }
        .staff-login-header p { opacity:0.7; font-size:0.9rem; }
        .staff-type-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem; margin-bottom:1.5rem; }
        .staff-type-card { background:rgba(255,255,255,0.08); border:2px solid rgba(255,255,255,0.1); border-radius:12px; padding:1.25rem 0.75rem; text-align:center; cursor:pointer; transition:all 0.3s; color:rgba(255,255,255,0.7); }
        .staff-type-card:hover { background:rgba(255,255,255,0.12); transform:translateY(-2px); }
        .staff-type-card.selected { border-color:#d4a843; background:rgba(212,168,67,0.15); color:#fff; }
        .staff-type-card i { font-size:1.8rem; display:block; margin-bottom:0.5rem; }
        .staff-type-card span { font-size:0.8rem; font-weight:600; }
        .staff-login-form { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:2rem; backdrop-filter:blur(10px); }
        .staff-login-form .form-group label { color:rgba(255,255,255,0.8); }
        .staff-login-form .form-control { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; }
        .staff-login-form .form-control::placeholder { color:rgba(255,255,255,0.4); }
        .staff-login-form .form-control:focus { border-color:#d4a843; background:rgba(255,255,255,0.12); }
        .btn-staff { background:linear-gradient(135deg,#d4a843,#b8922e); color:#0f172a; font-weight:700; border:none; width:100%; padding:0.85rem; border-radius:8px; font-size:1rem; cursor:pointer; transition:all 0.3s; }
        .btn-staff:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(212,168,67,0.3); }
        .staff-links { text-align:center; margin-top:1.5rem; }
        .staff-links a { color:rgba(255,255,255,0.6); font-size:0.85rem; text-decoration:none; }
        .staff-links a:hover { color:#d4a843; }
    </style>
</head>
<body>
    <div class="staff-login-page">
        <div class="staff-login-container">
            <div class="staff-login-header">
                <i class="fas fa-cross" style="font-size:2rem; color:#d4a843;"></i>
                <h1>Staff Portal</h1>
                <p>Holy Trinity Parish - Kabwe</p>
            </div>

            <?php if ($error): ?>
                <div style="background:rgba(220,38,38,0.2); border:1px solid rgba(220,38,38,0.4); color:#fca5a5; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.9rem;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="staff-type-grid">
                <?php foreach ($staffTypes as $type => $info): ?>
                <div class="staff-type-card" data-type="<?= $type ?>" onclick="selectStaffType('<?= $type ?>')">
                    <i class="fas <?= $info['icon'] ?>" style="color:<?= $info['color'] ?>;"></i>
                    <span><?= $info['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="staff-login-form">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="staff_type" id="staffType" value="">

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-staff" id="loginBtn" disabled>
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>

            <div class="staff-links">
                <a href="/holy-trinity/auth/login.php"><i class="fas fa-arrow-left"></i> Main Login</a>
                &nbsp;&bull;&nbsp;
                <a href="/holy-trinity/index.php"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </div>

    <script>
    function selectStaffType(type) {
        document.querySelectorAll('.staff-type-card').forEach(c => c.classList.remove('selected'));
        document.querySelector(`[data-type="${type}"]`).classList.add('selected');
        document.getElementById('staffType').value = type;
        document.getElementById('loginBtn').disabled = false;
    }
    <?php if (!empty($_POST['staff_type'])): ?>
    selectStaffType('<?= sanitize($_POST['staff_type']) ?>');
    <?php endif; ?>
    </script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
