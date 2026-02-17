<?php
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/holy-trinity/index.php');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect('/holy-trinity/index.php');
}

$email = sanitize($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
} else {
    $db = Database::getInstance();
    $existing = $db->fetch("SELECT id, is_subscribed FROM newsletters WHERE email = ?", [$email]);

    if ($existing) {
        if ($existing['is_subscribed']) {
            setFlash('info', 'You are already subscribed to our newsletter.');
        } else {
            $db->update('newsletters', ['is_subscribed' => 1, 'unsubscribed_at' => null], 'id = ?', [$existing['id']]);
            setFlash('success', 'Welcome back! You have been re-subscribed to our newsletter.');
        }
    } else {
        $db->insert('newsletters', [
            'email' => $email,
            'first_name' => sanitize($_POST['first_name'] ?? '') ?: null,
            'is_subscribed' => 1,
        ]);
        setFlash('success', 'Thank you for subscribing to our newsletter!');
    }

    logAudit('newsletter_subscribe', 'newsletter', null, null, json_encode(['email' => $email]));
}

$referer = $_SERVER['HTTP_REFERER'] ?? '/holy-trinity/index.php';
redirect($referer);
