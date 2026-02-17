<?php
require_once __DIR__ . '/../config/app.php';

// If already logged in as department head, redirect to department dashboard
if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['department_head', 'priest', 'super_admin', 'admin'])) {
    redirect('/holy-trinity/department/dashboard.php');
}

$db = Database::getInstance();

// Fetch active departments for the selector
$departments = $db->fetchAll("SELECT id, name, slug FROM departments WHERE is_active = 1 ORDER BY name");

$error = '';
$selectedDept = sanitize($_GET['dept'] ?? $_POST['department_slug'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $deptSlug = sanitize($_POST['department_slug'] ?? '');

        if (empty($email) || empty($password) || empty($deptSlug)) {
            $error = 'Please select a department and fill in all fields.';
        } else {
            // Find the department
            $dept = $db->fetch("SELECT * FROM departments WHERE slug = ? AND is_active = 1", [$deptSlug]);
            if (!$dept) {
                $error = 'Invalid department selected.';
            } else {
                // Authenticate user
                $user = $db->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Verify user belongs to this department (as head or member with admin/head role)
                    $isHead = ($dept['head_user_id'] == $user['id']);
                    $membership = $db->fetch(
                        "SELECT * FROM department_members WHERE department_id = ? AND user_id = ? AND role IN ('admin', 'head', 'leader')",
                        [$dept['id'], $user['id']]
                    );
                    $isPrivileged = in_array($user['role'], ['priest', 'super_admin', 'admin']);

                    if (!$isHead && !$membership && !$isPrivileged) {
                        $error = 'You are not an administrator of the ' . $dept['name'] . '. Contact the parish office for access.';
                    } else {
                        // Update last login
                        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_first'] = $user['first_name'];
                        $_SESSION['dept_id'] = $dept['id'];
                        $_SESSION['dept_slug'] = $dept['slug'];
                        $_SESSION['dept_name'] = $dept['name'];

                        session_regenerate_id(true);
                        logAudit('department_login', 'user', $user['id'], null, json_encode(['department' => $dept['name']]));
                        setFlash('success', 'Welcome to ' . $dept['name'] . ' Dashboard, ' . $user['first_name'] . '!');

                        redirect('/holy-trinity/department/dashboards/' . $dept['slug'] . '.php');
                    }
                } else {
                    $error = 'Invalid email or password.';
                    logAudit('dept_login_failed', 'user', null, null, json_encode(['email' => $email, 'dept' => $deptSlug]));
                }
            }
        }
    }
}

// Department icons and colors
$deptMeta = [
    'parish-office'    => ['icon' => 'fa-church',           'color' => '#1a365d', 'gradient' => 'linear-gradient(135deg, #1a365d, #2c5282)'],
    'finance'          => ['icon' => 'fa-coins',            'color' => '#2d6a4f', 'gradient' => 'linear-gradient(135deg, #2d6a4f, #40916c)'],
    'catechism'        => ['icon' => 'fa-book-bible',       'color' => '#7b2cbf', 'gradient' => 'linear-gradient(135deg, #7b2cbf, #9d4edd)'],
    'youth-ministry'   => ['icon' => 'fa-people-group',     'color' => '#e85d04', 'gradient' => 'linear-gradient(135deg, #e85d04, #f48c06)'],
    'choir'            => ['icon' => 'fa-music',            'color' => '#d62828', 'gradient' => 'linear-gradient(135deg, #d62828, #e63946)'],
    'marriage-family'  => ['icon' => 'fa-heart',            'color' => '#c2185b', 'gradient' => 'linear-gradient(135deg, #c2185b, #e91e63)'],
    'social-outreach'  => ['icon' => 'fa-hands-holding-heart', 'color' => '#0077b6', 'gradient' => 'linear-gradient(135deg, #0077b6, #00b4d8)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Login | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        .dept-login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 2rem;
        }
        .dept-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .dept-login-header i.fa-cross {
            font-size: 2.5rem;
            color: #d4a843;
            display: block;
            margin-bottom: 0.75rem;
        }
        .dept-login-header h1 {
            color: #fff;
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            margin-bottom: 0.25rem;
        }
        .dept-login-header p {
            color: rgba(255,255,255,0.6);
            font-size: 0.95rem;
        }
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            max-width: 800px;
            width: 100%;
            margin-bottom: 2rem;
        }
        .dept-card {
            background: rgba(255,255,255,0.08);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #fff;
        }
        .dept-card:hover, .dept-card.selected {
            background: rgba(255,255,255,0.15);
            border-color: #d4a843;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212,168,67,0.2);
        }
        .dept-card.selected {
            background: rgba(212,168,67,0.15);
        }
        .dept-card i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        .dept-card .dept-name {
            font-weight: 600;
            font-size: 0.85rem;
            line-height: 1.3;
        }
        .dept-login-form {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: none;
        }
        .dept-login-form.visible {
            display: block;
            animation: slideUp 0.4s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dept-login-form .dept-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        .dept-login-form .dept-badge i {
            font-size: 1.5rem;
        }
        .dept-login-form .dept-badge .dept-badge-name {
            font-weight: 700;
            font-size: 1rem;
        }
        .dept-login-form .dept-badge .dept-badge-sub {
            font-size: 0.8rem;
            opacity: 0.85;
        }
        .dept-login-form h3 {
            font-size: 1.1rem;
            color: #1a365d;
            margin-bottom: 1.25rem;
        }
        .dept-login-form .form-group {
            margin-bottom: 1.25rem;
        }
        .dept-login-form label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.4rem;
        }
        .dept-login-form .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .dept-login-form .form-control:focus {
            border-color: #d4a843;
            outline: none;
            box-shadow: 0 0 0 3px rgba(212,168,67,0.15);
        }
        .dept-login-form .btn-dept-login {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .dept-login-form .btn-dept-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .dept-login-form .back-link {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            color: #64748b;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .dept-login-form .back-link:hover {
            color: #1a365d;
        }
        .dept-login-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        .dept-login-links a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.85rem;
            margin: 0 0.75rem;
            transition: color 0.2s;
        }
        .dept-login-links a:hover {
            color: #d4a843;
        }
        .flash-error-dept {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dept-login-page">
        <div class="dept-login-header">
            <i class="fas fa-cross"></i>
            <h1>Holy Trinity Parish</h1>
            <p>Department Administrator Login</p>
        </div>

        <!-- Department Selection Grid -->
        <div class="dept-grid" id="deptGrid">
            <?php foreach ($departments as $d):
                $meta = $deptMeta[$d['slug']] ?? ['icon' => 'fa-building', 'color' => '#475569', 'gradient' => 'linear-gradient(135deg, #475569, #64748b)'];
            ?>
                <div class="dept-card <?= $selectedDept === $d['slug'] ? 'selected' : '' ?>"
                     data-slug="<?= $d['slug'] ?>"
                     data-name="<?= sanitize($d['name']) ?>"
                     data-icon="<?= $meta['icon'] ?>"
                     data-color="<?= $meta['color'] ?>"
                     data-gradient="<?= $meta['gradient'] ?>"
                     onclick="selectDepartment(this)">
                    <i class="fas <?= $meta['icon'] ?>" style="color: <?= $meta['color'] ?>;"></i>
                    <div class="dept-name"><?= sanitize($d['name']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Login Form (hidden until department selected) -->
        <div class="dept-login-form <?= $error || $selectedDept ? 'visible' : '' ?>" id="loginForm">
            <?php if ($error): ?>
                <div class="flash-error-dept">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="dept-badge" id="deptBadge" style="background: <?= $deptMeta[$selectedDept]['gradient'] ?? 'linear-gradient(135deg, #475569, #64748b)' ?>;">
                <i class="fas <?= $deptMeta[$selectedDept]['icon'] ?? 'fa-building' ?>"></i>
                <div>
                    <div class="dept-badge-name" id="deptBadgeName">
                        <?php
                        $selDeptName = '';
                        foreach ($departments as $d) {
                            if ($d['slug'] === $selectedDept) { $selDeptName = $d['name']; break; }
                        }
                        echo $selDeptName ?: 'Select Department';
                        ?>
                    </div>
                    <div class="dept-badge-sub">Department Admin Portal</div>
                </div>
            </div>

            <h3><i class="fas fa-lock"></i> Sign In to Your Department</h3>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="department_slug" id="deptSlugInput" value="<?= sanitize($selectedDept) ?>">

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your.email@example.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-dept-login" id="btnLogin" style="background: <?= $deptMeta[$selectedDept]['gradient'] ?? 'linear-gradient(135deg, #475569, #64748b)' ?>;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <a href="#" class="back-link" onclick="hideDeptForm(); return false;">
                <i class="fas fa-arrow-left"></i> Choose a different department
            </a>
        </div>

        <div class="dept-login-links">
            <a href="/holy-trinity/auth/login.php"><i class="fas fa-user"></i> General Login</a>
            <a href="/holy-trinity/index.php"><i class="fas fa-home"></i> Back to Home</a>
            <a href="/holy-trinity/auth/register.php"><i class="fas fa-user-plus"></i> Register</a>
        </div>
    </div>

    <script>
        function selectDepartment(card) {
            // Remove selected from all
            document.querySelectorAll('.dept-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const slug = card.dataset.slug;
            const name = card.dataset.name;
            const icon = card.dataset.icon;
            const color = card.dataset.color;
            const gradient = card.dataset.gradient;

            // Update form
            document.getElementById('deptSlugInput').value = slug;
            document.getElementById('deptBadgeName').textContent = name;
            document.getElementById('deptBadge').style.background = gradient;
            document.getElementById('deptBadge').querySelector('i').className = 'fas ' + icon;
            document.getElementById('btnLogin').style.background = gradient;

            // Show form
            const form = document.getElementById('loginForm');
            form.classList.add('visible');
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function hideDeptForm() {
            document.getElementById('loginForm').classList.remove('visible');
            document.querySelectorAll('.dept-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('deptSlugInput').value = '';
        }
    </script>
</body>
</html>
