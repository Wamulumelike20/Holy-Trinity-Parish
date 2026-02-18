<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$payslipId = intval($_GET['id'] ?? 0);

$payslip = $db->fetch("SELECT p.*, u.first_name, u.last_name, u.email, u.phone, sp.employee_id, sp.position_title, sp.bank_name, sp.bank_account
    FROM payslips p JOIN users u ON p.staff_user_id = u.id LEFT JOIN staff_profiles sp ON sp.user_id = p.staff_user_id
    WHERE p.id = ? AND p.staff_user_id = ?", [$payslipId, $userId]);

if (!$payslip) {
    // Allow leadership to view any payslip
    if (isLeadership()) {
        $payslip = $db->fetch("SELECT p.*, u.first_name, u.last_name, u.email, u.phone, sp.employee_id, sp.position_title, sp.bank_name, sp.bank_account
            FROM payslips p JOIN users u ON p.staff_user_id = u.id LEFT JOIN staff_profiles sp ON sp.user_id = p.staff_user_id
            WHERE p.id = ?", [$payslipId]);
    }
    if (!$payslip) {
        setFlash('error', 'Payslip not found.');
        redirect('/holy-trinity/staff/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= sanitize($payslip['pay_period']) ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f8fafc; color:#1e293b; }
        .payslip-actions { display:flex; gap:0.75rem; justify-content:center; padding:1.5rem; background:#fff; border-bottom:1px solid #e2e8f0; }
        .payslip-actions button, .payslip-actions a { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.5rem; border-radius:8px; font-size:0.9rem; font-weight:600; cursor:pointer; text-decoration:none; border:none; transition:all 0.2s; }
        .btn-print { background:#1a365d; color:#fff; }
        .btn-download { background:#22c55e; color:#fff; }
        .btn-back { background:#e2e8f0; color:#475569; }
        .payslip-container { max-width:800px; margin:2rem auto; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.08); overflow:hidden; }
        .payslip-header { background:linear-gradient(135deg,#1a365d,#2c5282); color:#fff; padding:2rem; display:flex; justify-content:space-between; align-items:center; }
        .payslip-header h1 { font-size:1.3rem; }
        .payslip-header .ref { opacity:0.8; font-size:0.85rem; }
        .payslip-body { padding:2rem; }
        .payslip-row { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
        .payslip-field label { font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:0.25rem; }
        .payslip-field span { font-size:0.95rem; font-weight:500; }
        .payslip-table { width:100%; border-collapse:collapse; margin:1.5rem 0; }
        .payslip-table th { background:#f1f5f9; padding:0.75rem 1rem; text-align:left; font-size:0.8rem; text-transform:uppercase; color:#64748b; border-bottom:2px solid #e2e8f0; }
        .payslip-table td { padding:0.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:0.9rem; }
        .payslip-table .amount { text-align:right; font-weight:600; }
        .payslip-total { background:#1a365d; color:#fff; }
        .payslip-total td { font-weight:700; font-size:1rem; }
        .payslip-footer { text-align:center; padding:1.5rem; border-top:1px solid #e2e8f0; color:#94a3b8; font-size:0.8rem; }
        @media print {
            .payslip-actions { display:none !important; }
            body { background:#fff; }
            .payslip-container { box-shadow:none; margin:0; border-radius:0; }
        }
    </style>
</head>
<body>
    <div class="payslip-actions">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        <button class="btn-download" onclick="window.print()"><i class="fas fa-download"></i> Download PDF</button>
        <a href="/holy-trinity/staff/dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="payslip-container" id="payslipContent">
        <div class="payslip-header">
            <div>
                <h1><i class="fas fa-cross"></i> Holy Trinity Parish</h1>
                <div class="ref">Kabwe, Zambia &bull; Kabwe Diocese</div>
            </div>
            <div style="text-align:right;">
                <strong>PAYSLIP</strong><br>
                <span class="ref"><?= sanitize($payslip['pay_period']) ?></span>
            </div>
        </div>

        <div class="payslip-body">
            <div class="payslip-row">
                <div class="payslip-field"><label>Employee Name</label><span><?= sanitize($payslip['first_name'] . ' ' . $payslip['last_name']) ?></span></div>
                <div class="payslip-field"><label>Employee ID</label><span><?= sanitize($payslip['employee_id'] ?? 'N/A') ?></span></div>
            </div>
            <div class="payslip-row">
                <div class="payslip-field"><label>Position</label><span><?= sanitize($payslip['position_title'] ?? 'N/A') ?></span></div>
                <div class="payslip-field"><label>Pay Date</label><span><?= formatDate($payslip['pay_date']) ?></span></div>
            </div>
            <div class="payslip-row">
                <div class="payslip-field"><label>Bank</label><span><?= sanitize($payslip['bank_name'] ?? 'N/A') ?></span></div>
                <div class="payslip-field"><label>Account</label><span><?= sanitize($payslip['bank_account'] ?? 'N/A') ?></span></div>
            </div>

            <table class="payslip-table">
                <thead><tr><th>Description</th><th class="amount">Amount (ZMW)</th></tr></thead>
                <tbody>
                    <tr><td>Basic Salary</td><td class="amount"><?= number_format($payslip['basic_salary'], 2) ?></td></tr>
                    <?php if ($payslip['allowances'] > 0): ?>
                    <tr><td>Allowances</td><td class="amount"><?= number_format($payslip['allowances'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($payslip['overtime_pay'] > 0): ?>
                    <tr><td>Overtime Pay</td><td class="amount"><?= number_format($payslip['overtime_pay'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr style="background:#f0fdf4;"><td><strong>Gross Pay</strong></td><td class="amount"><strong><?= number_format($payslip['basic_salary'] + $payslip['allowances'] + $payslip['overtime_pay'], 2) ?></strong></td></tr>
                    <?php if ($payslip['tax'] > 0): ?>
                    <tr><td>PAYE Tax</td><td class="amount" style="color:#ef4444;">-<?= number_format($payslip['tax'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($payslip['napsa'] > 0): ?>
                    <tr><td>NAPSA Contribution</td><td class="amount" style="color:#ef4444;">-<?= number_format($payslip['napsa'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($payslip['nhima'] > 0): ?>
                    <tr><td>NHIMA Contribution</td><td class="amount" style="color:#ef4444;">-<?= number_format($payslip['nhima'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($payslip['deductions'] > 0): ?>
                    <tr><td>Other Deductions</td><td class="amount" style="color:#ef4444;">-<?= number_format($payslip['deductions'], 2) ?></td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="payslip-total"><td>NET PAY</td><td class="amount">ZMW <?= number_format($payslip['net_pay'], 2) ?></td></tr>
                </tfoot>
            </table>

            <?php if ($payslip['notes']): ?>
            <div style="background:#f8fafc; padding:1rem; border-radius:8px; font-size:0.85rem; color:#64748b;">
                <strong>Notes:</strong> <?= sanitize($payslip['notes']) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="payslip-footer">
            <p>This is a computer-generated payslip. No signature required.</p>
            <p>Holy Trinity Parish &bull; Kabwe, Zambia &bull; info@holytrinityparish.org</p>
        </div>
    </div>
</body>
</html>
