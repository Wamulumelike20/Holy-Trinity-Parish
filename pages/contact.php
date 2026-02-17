<?php
$pageTitle = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // In production, send email here
            logAudit('contact_form_submitted', null, null, null, json_encode(['name' => $name, 'email' => $email, 'subject' => $subject]));
            $success = 'Thank you for your message! We will get back to you as soon as possible.';
        }
    }
}
?>

<section class="page-banner">
    <h1><i class="fas fa-envelope"></i> Contact Us</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a><span>/</span><span>Contact</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 400px; gap:2rem;" class="grid-2">
            <!-- Contact Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Send Us a Message</h3>
                </div>
                <div class="card-body">
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

                    <form method="POST" data-validate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Your Name <span class="required">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Full name" required value="<?= isLoggedIn() ? sanitize($_SESSION['user_name'] ?? '') : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="Your email" required value="<?= isLoggedIn() ? sanitize($_SESSION['user_email'] ?? '') : '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject" class="form-control" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Sacramental Request">Sacramental Request</option>
                                <option value="Appointment Request">Appointment Request</option>
                                <option value="Donation Inquiry">Donation Inquiry</option>
                                <option value="Ministry Information">Ministry Information</option>
                                <option value="Event Information">Event Information</option>
                                <option value="Prayer Request">Prayer Request</option>
                                <option value="Feedback">Feedback</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Message <span class="required">*</span></label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Write your message here..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Contact Info Sidebar -->
            <div>
                <div class="card mb-3">
                    <div class="card-body" style="padding:2rem;">
                        <h3 style="font-size:1.15rem; margin-bottom:1.5rem;"><i class="fas fa-map-marker-alt" style="color:var(--gold);"></i> Visit Us</h3>
                        <div style="display:flex; flex-direction:column; gap:1.25rem; font-size:0.95rem;">
                            <div style="display:flex; gap:1rem;">
                                <i class="fas fa-location-dot" style="color:var(--gold); margin-top:0.2rem; width:20px; text-align:center;"></i>
                                <div>
                                    <strong>Address</strong>
                                    <p class="text-muted" style="margin:0;">Holy Trinity Parish<br>Kampala, Uganda</p>
                                </div>
                            </div>
                            <div style="display:flex; gap:1rem;">
                                <i class="fas fa-phone" style="color:var(--gold); margin-top:0.2rem; width:20px; text-align:center;"></i>
                                <div>
                                    <strong>Phone</strong>
                                    <p class="text-muted" style="margin:0;">+256-XXX-XXXXXX</p>
                                </div>
                            </div>
                            <div style="display:flex; gap:1rem;">
                                <i class="fas fa-envelope" style="color:var(--gold); margin-top:0.2rem; width:20px; text-align:center;"></i>
                                <div>
                                    <strong>Email</strong>
                                    <p class="text-muted" style="margin:0;">info@holytrinityparish.org</p>
                                </div>
                            </div>
                            <div style="display:flex; gap:1rem;">
                                <i class="fas fa-clock" style="color:var(--gold); margin-top:0.2rem; width:20px; text-align:center;"></i>
                                <div>
                                    <strong>Office Hours</strong>
                                    <p class="text-muted" style="margin:0;">Mon - Fri: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 1:00 PM<br>Sunday: Closed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body" style="padding:2rem; text-align:center;">
                        <i class="fas fa-hands-praying" style="font-size:2rem; color:var(--gold); margin-bottom:0.75rem; display:block;"></i>
                        <h4>Prayer Requests</h4>
                        <p style="font-size:0.9rem; color:var(--gray);">We believe in the power of prayer. Submit your prayer intentions and our community will pray for you.</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding:2rem;">
                        <h4 style="margin-bottom:1rem;"><i class="fas fa-share-alt" style="color:var(--gold);"></i> Follow Us</h4>
                        <div style="display:flex; gap:0.75rem;">
                            <a href="#" style="width:44px; height:44px; border-radius:50%; background:var(--primary); color:var(--white); display:flex; align-items:center; justify-content:center; font-size:1.1rem;" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" style="width:44px; height:44px; border-radius:50%; background:#1DA1F2; color:var(--white); display:flex; align-items:center; justify-content:center; font-size:1.1rem;" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" style="width:44px; height:44px; border-radius:50%; background:#FF0000; color:var(--white); display:flex; align-items:center; justify-content:center; font-size:1.1rem;" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                            <a href="#" style="width:44px; height:44px; border-radius:50%; background:#E4405F; color:var(--white); display:flex; align-items:center; justify-content:center; font-size:1.1rem;" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
