<?php require_once __DIR__ . '/../config/app.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <?php if (isset($extraCSS)): ?>
        <link rel="stylesheet" href="<?= $extraCSS ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div class="top-bar-left">
                <span><i class="fas fa-phone"></i> +260-XXX-XXXXXX</span>
                <span><i class="fas fa-envelope"></i> info@holytrinityparish.org</span>
            </div>
            <div class="top-bar-right">
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?= sanitize($_SESSION['user_name'] ?? 'User') ?></span>
                    <a href="/holy-trinity/portal/dashboard.php"><i class="fas fa-user-circle"></i> My Portal</a>
                    <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="/holy-trinity/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="/holy-trinity/auth/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav" id="mainNav">
        <div class="container nav-inner">
            <a href="/holy-trinity/index.php" class="nav-brand">
                <i class="fas fa-cross"></i>
                <div>
                    <span class="brand-name">Holy Trinity Parish</span>
                    <span class="brand-tagline">A Community of Faith, Hope & Love</span>
                </div>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="/holy-trinity/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' && !strpos($_SERVER['PHP_SELF'], 'portal') ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="/holy-trinity/pages/about.php" class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>"><i class="fas fa-church"></i> About Us</a></li>
                <li><a href="/holy-trinity/pages/events.php" class="<?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="/holy-trinity/pages/sermons.php" class="<?= basename($_SERVER['PHP_SELF']) == 'sermons.php' ? 'active' : '' ?>"><i class="fas fa-bible"></i> Sermons</a></li>
                <li class="nav-dropdown">
                    <a href="#" class="dropdown-toggle"><i class="fas fa-hands-praying"></i> Ministries <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="/holy-trinity/pages/ministries.php">All Ministries</a></li>
                        <li><a href="/holy-trinity/pages/departments.php">Departments</a></li>
                    </ul>
                </li>
                <li><a href="/holy-trinity/appointments/book.php" class="<?= strpos($_SERVER['PHP_SELF'], 'appointments') ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="/holy-trinity/donations/donate.php" class="<?= strpos($_SERVER['PHP_SELF'], 'donations') ? 'active' : '' ?>"><i class="fas fa-hand-holding-heart"></i> Donate</a></li>
                <li><a href="/holy-trinity/pages/contact.php" class="<?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php
    $flashTypes = ['success', 'error', 'warning', 'info'];
    foreach ($flashTypes as $type):
        $msg = getFlash($type);
        if ($msg):
    ?>
    <div class="flash-message flash-<?= $type ?>" id="flashMessage">
        <div class="container">
            <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
            <span><?= $msg ?></span>
            <button class="flash-close" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <?php endif; endforeach; ?>

    <main>
