<?php
$pageTitle = 'Book Appointment';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Fetch clergy (priests) for the "Which priest" dropdown
$clergyList = $db->fetchAll("
    SELECT c.id as clergy_id, c.title, c.full_name, c.position, c.user_id
    FROM clergy c WHERE c.is_active = 1 ORDER BY c.display_order, c.full_name
");

// Fetch available providers (priests and department heads)
$providers = $db->fetchAll("
    SELECT u.id, u.first_name, u.last_name, u.role, d.name as department_name, d.id as department_id
    FROM users u
    LEFT JOIN departments d ON d.head_user_id = u.id
    WHERE u.role IN ('priest', 'department_head', 'admin') AND u.is_active = 1
    ORDER BY u.role, u.first_name
");

// Fetch departments for booking
$departments = $db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $priestClergyId = intval($_POST['priest_clergy_id'] ?? 0);
        $providerId = intval($_POST['provider_id'] ?? 0);
        $departmentId = intval($_POST['department_id'] ?? 0) ?: null;
        $appointmentDate = sanitize($_POST['appointment_date'] ?? '');
        $startTime = sanitize($_POST['start_time'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        // If priest selected, resolve their user_id as provider
        if ($priestClergyId) {
            $clergy = $db->fetch("SELECT user_id, full_name FROM clergy WHERE id = ?", [$priestClergyId]);
            if ($clergy && $clergy['user_id']) {
                $providerId = $clergy['user_id'];
            } else {
                // If clergy has no linked user, use first priest user as fallback
                $priestUser = $db->fetch("SELECT id FROM users WHERE role = 'priest' AND is_active = 1 LIMIT 1");
                $providerId = $priestUser ? $priestUser['id'] : 0;
            }
        }

        if (empty($providerId) || empty($appointmentDate) || empty($startTime) || empty($reason)) {
            $error = 'Please select a priest or staff member, and fill in all required fields.';
        } elseif (strtotime($appointmentDate) < strtotime('today')) {
            $error = 'Cannot book appointments in the past.';
        } else {
            // Check for existing appointment at same time
            $existing = $db->fetch(
                "SELECT id FROM appointments WHERE provider_id = ? AND appointment_date = ? AND start_time = ? AND status NOT IN ('cancelled', 'declined')",
                [$providerId, $appointmentDate, $startTime]
            );

            if ($existing) {
                $error = 'This time slot is already booked. Please choose another time.';
            } else {
                $endTime = date('H:i:s', strtotime($startTime) + 1800); // 30 min slots
                $refNumber = generateReference('APT');

                // Handle file upload
                $documentPath = null;
                if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = APP_ROOT . '/uploads/appointments/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                    $filename = $refNumber . '.' . $ext;
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $filename)) {
                        $documentPath = $filename;
                    }
                }

                $db->insert('appointments', [
                    'reference_number' => $refNumber,
                    'user_id' => $_SESSION['user_id'],
                    'provider_id' => $providerId,
                    'department_id' => $departmentId,
                    'appointment_date' => $appointmentDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'reason' => $reason,
                    'description' => $description,
                    'document_path' => $documentPath,
                    'status' => 'pending',
                ]);

                logAudit('appointment_booked', 'appointment', null, null, json_encode(['ref' => $refNumber]));

                // Send notification to the provider
                $bookerName = sanitize($_SESSION['user_first'] ?? 'A parishioner');
                sendNotification(
                    'New Appointment Request',
                    "{$bookerName} has booked an appointment for {$appointmentDate} ({$reason}). Ref: {$refNumber}",
                    'info',
                    '/holy-trinity/admin/appointments.php',
                    $providerId
                );
                // Notify all priests
                sendNotification(
                    'New Appointment Request',
                    "{$bookerName} has requested an appointment ({$reason}). Ref: {$refNumber}",
                    'info',
                    '/holy-trinity/admin/appointments.php',
                    null, null, 'priest'
                );

                $success = "Appointment booked successfully! Your reference number is <strong>{$refNumber}</strong>. You will receive a confirmation once approved.";
            }
        }
    }
}
?>

<!-- Page Banner -->
<section class="page-banner">
    <h1><i class="fas fa-calendar-check"></i> Book an Appointment</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a>
        <span>/</span>
        <span>Appointments</span>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <div class="card" style="max-width:600px; margin:0 auto; text-align:center;">
                <div class="card-body" style="padding:3rem;">
                    <i class="fas fa-lock" style="font-size:3rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                    <h3>Login Required</h3>
                    <p class="text-muted">Please log in or create an account to book an appointment.</p>
                    <div style="display:flex; gap:1rem; justify-content:center; margin-top:1.5rem;">
                        <a href="/holy-trinity/auth/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="/holy-trinity/auth/register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <?php if ($success): ?>
                <div class="flash-message flash-success" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                    <div class="container">
                        <i class="fas fa-check-circle"></i> <span><?= $success ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash-message flash-error" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                    <div class="container">
                        <i class="fas fa-exclamation-circle"></i> <span><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 380px; gap:2rem;" class="grid-2">
                <!-- Booking Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-plus"></i> New Appointment</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" data-validate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="form-group">
                                <label><i class="fas fa-pray"></i> Which Priest Would You Like to Meet? <span class="required">*</span></label>
                                <select name="priest_clergy_id" class="form-control" id="priestSelect">
                                    <option value="">Select a priest</option>
                                    <?php foreach ($clergyList as $cl): ?>
                                        <option value="<?= $cl['clergy_id'] ?>" data-user-id="<?= $cl['user_id'] ?>">
                                            <?= sanitize($cl['title'] . ' ' . $cl['full_name']) ?> &mdash; <?= sanitize($cl['position']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose the priest you wish to have your appointment with</div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Department (optional)</label>
                                <select name="department_id" class="form-control" id="departmentSelect">
                                    <option value="">General / No specific department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= sanitize($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select if your appointment relates to a specific department</div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Or Meet With Staff Member</label>
                                <select name="provider_id" class="form-control" id="providerSelect">
                                    <option value="">Select staff member (optional)</option>
                                    <?php foreach ($providers as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-dept="<?= $p['department_id'] ?>">
                                            <?= sanitize($p['first_name'] . ' ' . $p['last_name']) ?>
                                            (<?= ucfirst(str_replace('_', ' ', $p['role'])) ?>)
                                            <?php if ($p['department_name']): ?> &mdash; <?= sanitize($p['department_name']) ?><?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">If not meeting a priest, select a department head or staff member</div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Preferred Date <span class="required">*</span></label>
                                <input type="date" name="appointment_date" class="form-control" id="appointmentDate" min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Preferred Time <span class="required">*</span></label>
                                <select name="start_time" class="form-control" required>
                                    <option value="">Select a time</option>
                                    <?php
                                    for ($h = 8; $h <= 16; $h++) {
                                        for ($m = 0; $m < 60; $m += 30) {
                                            $time = sprintf('%02d:%02d:00', $h, $m);
                                            $display = date('g:i A', strtotime($time));
                                            echo "<option value=\"{$time}\">{$display}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-clipboard-list"></i> Reason for Appointment <span class="required">*</span></label>
                                <select name="reason" class="form-control" required>
                                    <option value="">Select reason</option>
                                    <option value="Spiritual Counseling">Spiritual Counseling</option>
                                    <option value="Marriage Preparation">Marriage Preparation</option>
                                    <option value="Baptism Preparation">Baptism Preparation</option>
                                    <option value="Confession">Confession</option>
                                    <option value="Anointing of the Sick">Anointing of the Sick</option>
                                    <option value="Financial Matter">Financial Matter</option>
                                    <option value="Youth Guidance">Youth Guidance</option>
                                    <option value="Family Counseling">Family Counseling</option>
                                    <option value="Certificate Request">Certificate Request</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-comment-dots"></i> Additional Details</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Provide any additional information about your appointment..."></textarea>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-paperclip"></i> Supporting Document (optional)</label>
                                <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png">
                                <div class="form-text">Max 5MB. Accepted: PDF, DOC, DOCX, JPG, PNG</div>
                                <div class="file-preview"></div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-calendar-check"></i> Book Appointment
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Booking Info</h3>
                        </div>
                        <div class="card-body">
                            <div style="display:flex; flex-direction:column; gap:1rem; font-size:0.9rem;">
                                <div style="display:flex; gap:0.75rem;">
                                    <i class="fas fa-clock" style="color:var(--gold); margin-top:0.2rem;"></i>
                                    <div>
                                        <strong>Office Hours</strong>
                                        <p class="text-muted" style="margin:0;">Mon - Fri: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 1:00 PM</p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:0.75rem;">
                                    <i class="fas fa-hourglass-half" style="color:var(--gold); margin-top:0.2rem;"></i>
                                    <div>
                                        <strong>Appointment Duration</strong>
                                        <p class="text-muted" style="margin:0;">Each appointment slot is 30 minutes</p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:0.75rem;">
                                    <i class="fas fa-bell" style="color:var(--gold); margin-top:0.2rem;"></i>
                                    <div>
                                        <strong>Confirmation</strong>
                                        <p class="text-muted" style="margin:0;">You'll receive a confirmation email once your appointment is approved</p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:0.75rem;">
                                    <i class="fas fa-redo" style="color:var(--gold); margin-top:0.2rem;"></i>
                                    <div>
                                        <strong>Reschedule/Cancel</strong>
                                        <p class="text-muted" style="margin:0;">You can reschedule or cancel from your portal dashboard</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Appointments -->
                    <?php
                    $myAppointments = $db->fetchAll(
                        "SELECT a.*, u.first_name as provider_first, u.last_name as provider_last, d.name as dept_name
                         FROM appointments a
                         LEFT JOIN users u ON a.provider_id = u.id
                         LEFT JOIN departments d ON a.department_id = d.id
                         WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
                         ORDER BY a.appointment_date ASC, a.start_time ASC LIMIT 5",
                        [$_SESSION['user_id']]
                    );
                    ?>
                    <?php if (!empty($myAppointments)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> My Upcoming</h3>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($myAppointments as $apt): ?>
                            <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong><?= sanitize($apt['reason']) ?></strong>
                                    <span class="badge badge-<?= $apt['status'] === 'approved' ? 'success' : ($apt['status'] === 'pending' ? 'warning' : 'error') ?>">
                                        <?= ucfirst($apt['status']) ?>
                                    </span>
                                </div>
                                <div class="text-muted" style="font-size:0.8rem; margin-top:0.25rem;">
                                    <i class="fas fa-calendar"></i> <?= formatDate($apt['appointment_date']) ?> at <?= formatTime($apt['start_time']) ?>
                                    <br><i class="fas fa-user"></i> <?= sanitize($apt['provider_first'] . ' ' . $apt['provider_last']) ?>
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-light); margin-top:0.25rem;">
                                    Ref: <?= $apt['reference_number'] ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="/holy-trinity/portal/appointments.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
