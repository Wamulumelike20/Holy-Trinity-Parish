<?php
$pageTitle = 'Online Giving';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$categories = $db->fetchAll("SELECT * FROM donation_categories WHERE is_active = 1 ORDER BY name");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $amount = floatval($_POST['amount'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0) ?: null;
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'online');
        $donorName = sanitize($_POST['donor_name'] ?? '');
        $donorEmail = sanitize($_POST['donor_email'] ?? '');
        $donorPhone = sanitize($_POST['donor_phone'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;

        if ($amount <= 0) {
            $error = 'Please enter a valid donation amount.';
        } elseif (!$isAnonymous && empty($donorName) && !isLoggedIn()) {
            $error = 'Please provide your name or check anonymous donation.';
        } else {
            $transRef = generateReference('DON');

            $donationData = [
                'transaction_ref' => $transRef,
                'user_id' => isLoggedIn() ? $_SESSION['user_id'] : null,
                'donor_name' => $isAnonymous ? 'Anonymous' : ($donorName ?: ($_SESSION['user_name'] ?? 'Anonymous')),
                'donor_email' => $donorEmail ?: ($_SESSION['user_email'] ?? null),
                'donor_phone' => $donorPhone,
                'category_id' => $categoryId,
                'amount' => $amount,
                'currency' => 'ZMW',
                'payment_method' => $paymentMethod,
                'payment_status' => 'completed',
                'donation_date' => date('Y-m-d'),
                'notes' => $notes,
                'is_anonymous' => $isAnonymous,
            ];

            $db->insert('donations', $donationData);
            logAudit('donation_made', 'donation', null, null, json_encode(['ref' => $transRef, 'amount' => $amount]));
            $success = "Thank you for your generous donation! Your transaction reference is <strong>{$transRef}</strong>. God bless you abundantly!";
        }
    }
}
?>

<!-- Page Banner -->
<section class="page-banner">
    <h1><i class="fas fa-hand-holding-heart"></i> Online Giving</h1>
    <div class="breadcrumb">
        <a href="/holy-trinity/index.php">Home</a>
        <span>/</span>
        <span>Donate</span>
    </div>
</section>

<!-- Donation Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Support Our Parish</h2>
            <p>"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver." — 2 Corinthians 9:7</p>
        </div>

        <?php if ($success): ?>
            <div class="flash-message flash-success" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                <div class="container"><i class="fas fa-check-circle"></i> <span><?= $success ?></span></div>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash-message flash-error" style="margin-bottom:2rem; padding:1rem; border-radius:var(--radius);">
                <div class="container"><i class="fas fa-exclamation-circle"></i> <span><?= $error ?></span></div>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 380px; gap:2rem;" class="grid-2">
            <!-- Donation Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-donate"></i> Make a Donation</h3>
                </div>
                <div class="card-body">
                    <form method="POST" data-validate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <!-- Quick Amount Selection -->
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Select Amount (ZMW)</label>
                            <div class="donation-amounts">
                                <button type="button" class="amount-btn" data-amount="10000">10,000</button>
                                <button type="button" class="amount-btn" data-amount="20000">20,000</button>
                                <button type="button" class="amount-btn" data-amount="50000">50,000</button>
                                <button type="button" class="amount-btn" data-amount="100000">100,000</button>
                                <button type="button" class="amount-btn" data-amount="200000">200,000</button>
                                <button type="button" class="amount-btn" data-amount="500000">500,000</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Or Enter Custom Amount <span class="required">*</span></label>
                            <input type="number" name="amount" id="donationAmount" class="form-control" placeholder="Enter amount in ZMW" min="1000" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Donation Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">General Offering</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="online">Online Payment</option>
                            </select>
                        </div>

                        <?php if (!isLoggedIn()): ?>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Your Name</label>
                            <input type="text" name="donor_name" class="form-control" placeholder="Full name">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="donor_email" class="form-control" placeholder="For receipt">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone</label>
                                <input type="tel" name="donor_phone" class="form-control" placeholder="Phone number">
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Notes (optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions or dedication..."></textarea>
                        </div>

                        <div class="form-group">
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                <input type="checkbox" name="is_anonymous"> Make this donation anonymous
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-heart"></i> Complete Donation
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Donation Categories -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Giving Options</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php foreach ($categories as $cat): ?>
                        <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--light-gray);">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong style="font-size:0.95rem;"><?= sanitize($cat['name']) ?></strong>
                                    <?php if ($cat['description']): ?>
                                        <p style="font-size:0.8rem; color:var(--text-light); margin:0.2rem 0 0;"><?= sanitize($cat['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <i class="fas fa-chevron-right" style="color:var(--gold);"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3><i class="fas fa-university"></i> Bank Details</h3>
                    </div>
                    <div class="card-body" style="font-size:0.9rem;">
                        <p><strong>Bank:</strong> Centenary Bank</p>
                        <p><strong>Account Name:</strong> Holy Trinity Parish</p>
                        <p><strong>Account Number:</strong> XXXX-XXXX-XXXX</p>
                        <p><strong>Branch:</strong> Kabwe Main Branch</p>
                        <hr style="border-color:var(--light-gray);">
                        <p><strong>Mobile Money:</strong></p>
                        <p>MTN: 0770-XXX-XXX</p>
                        <p>Airtel: 0750-XXX-XXX</p>
                    </div>
                </div>

                <!-- Why Give -->
                <div class="card">
                    <div class="card-body" style="text-align:center; padding:2rem;">
                        <i class="fas fa-hands-praying" style="font-size:2.5rem; color:var(--gold); margin-bottom:1rem; display:block;"></i>
                        <h4>Why Give?</h4>
                        <p style="font-size:0.9rem; color:var(--gray);">
                            Your generous contributions support our parish's mission, maintain our facilities, fund charitable works, and help us serve the community. Every gift, no matter the size, makes a difference.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
