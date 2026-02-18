<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['liturgical_coordinator', 'priest', 'super_admin', 'admin'])) {
    setFlash('error', 'Liturgical coordinator access required.');
    redirect('/holy-trinity/index.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$pageTitle = 'Liturgical Dashboard';

// Upcoming schedules
$upcomingSchedules = $db->fetchAll(
    "SELECT ls.*, c.full_name as celebrant_name FROM liturgical_schedules ls
     LEFT JOIN clergy c ON ls.celebrant_id = c.id
     WHERE ls.liturgical_date >= CURDATE() ORDER BY ls.liturgical_date ASC LIMIT 15"
);

// Open issues
$openIssues = $db->fetchAll(
    "SELECT li.*, u.first_name, u.last_name FROM liturgical_issues li
     JOIN users u ON li.reported_by = u.id WHERE li.status IN ('open','in_progress') ORDER BY
     FIELD(li.priority,'urgent','high','medium','low'), li.created_at DESC"
);

// Pending purchase requests from liturgical
$purchaseRequests = $db->fetchAll(
    "SELECT pr.*, u.first_name, u.last_name FROM purchase_requests pr
     JOIN users u ON pr.requested_by = u.id WHERE pr.source_type = 'liturgical' ORDER BY pr.created_at DESC LIMIT 10"
);

// Budgets from liturgical
$budgets = $db->fetchAll(
    "SELECT b.*, u.first_name, u.last_name FROM budgets b
     JOIN users u ON b.submitted_by = u.id WHERE b.source_type = 'liturgical' ORDER BY b.created_at DESC LIMIT 10"
);

// Handle new issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report_issue') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $db->insert('liturgical_issues', [
            'reported_by' => $userId,
            'issue_type' => sanitize($_POST['issue_type'] ?? 'other'),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'priority' => sanitize($_POST['priority'] ?? 'medium'),
        ]);
        setFlash('success', 'Issue reported successfully.');
    }
    redirect('/holy-trinity/liturgical/dashboard.php');
}

// Handle new purchase request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purchase_request') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $items = [];
        $itemNames = $_POST['item_name'] ?? [];
        $itemQtys = $_POST['item_qty'] ?? [];
        $itemPrices = $_POST['item_price'] ?? [];
        $total = 0;
        for ($i = 0; $i < count($itemNames); $i++) {
            if (!empty($itemNames[$i])) {
                $subtotal = floatval($itemQtys[$i] ?? 1) * floatval($itemPrices[$i] ?? 0);
                $items[] = ['name' => $itemNames[$i], 'qty' => $itemQtys[$i] ?? 1, 'price' => $itemPrices[$i] ?? 0, 'subtotal' => $subtotal];
                $total += $subtotal;
            }
        }
        $db->insert('purchase_requests', [
            'requested_by' => $userId,
            'source_type' => 'liturgical',
            'title' => sanitize($_POST['pr_title'] ?? ''),
            'description' => sanitize($_POST['pr_description'] ?? ''),
            'items_json' => json_encode($items),
            'total_amount' => $total,
            'urgency' => sanitize($_POST['urgency'] ?? 'normal'),
        ]);
        sendNotification('New Purchase Request', $_SESSION['user_name'] . ' submitted a liturgical purchase request for ZMW ' . number_format($total, 2), 'info', '/holy-trinity/executive/dashboard.php', null, null, 'parish_executive');
        setFlash('success', 'Purchase request submitted to Parish Executive.');
    }
    redirect('/holy-trinity/liturgical/dashboard.php');
}

// Handle new budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_budget') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $items = [];
        $bItemNames = $_POST['b_item_name'] ?? [];
        $bItemAmounts = $_POST['b_item_amount'] ?? [];
        $total = 0;
        for ($i = 0; $i < count($bItemNames); $i++) {
            if (!empty($bItemNames[$i])) {
                $amt = floatval($bItemAmounts[$i] ?? 0);
                $items[] = ['item' => $bItemNames[$i], 'amount' => $amt];
                $total += $amt;
            }
        }

        $attachPath = null;
        if (!empty($_FILES['budget_attachment']['name'])) {
            $uploadDir = UPLOAD_DIR . 'budgets/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['budget_attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'budget_liturgical_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['budget_attachment']['tmp_name'], $uploadDir . $filename)) {
                $attachPath = 'uploads/budgets/' . $filename;
            }
        }

        $db->insert('budgets', [
            'submitted_by' => $userId,
            'source_type' => 'liturgical',
            'title' => sanitize($_POST['budget_title'] ?? ''),
            'budget_period' => sanitize($_POST['budget_period'] ?? ''),
            'description' => sanitize($_POST['budget_description'] ?? ''),
            'items_json' => json_encode($items),
            'total_amount' => $total,
            'attachment_path' => $attachPath,
        ]);
        sendNotification('New Liturgical Budget', $_SESSION['user_name'] . ' submitted a liturgical budget for ZMW ' . number_format($total, 2), 'info', '/holy-trinity/executive/dashboard.php', null, null, 'parish_executive');
        setFlash('success', 'Budget submitted to Parish Executive.');
    }
    redirect('/holy-trinity/liturgical/dashboard.php');
}

// Handle resolve issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_issue') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $issueId = intval($_POST['issue_id'] ?? 0);
        $db->update('liturgical_issues', [
            'status' => 'resolved',
            'resolution_notes' => sanitize($_POST['resolution_notes'] ?? ''),
            'resolved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$issueId]);
        setFlash('success', 'Issue marked as resolved.');
    }
    redirect('/holy-trinity/liturgical/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . "/../includes/pwa-head.php"; ?>
    <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/holy-trinity/assets/css/style.css">
    <style>
        .lit-banner { background:linear-gradient(135deg,#7b2cbf,#9d4edd); color:#fff; padding:1.5rem 2rem; border-radius:12px; margin-bottom:1.5rem; }
        .lit-banner h1 { font-family:'Cinzel',serif; font-size:1.4rem; margin:0; }
        .lit-banner p { margin:0.25rem 0 0; opacity:0.8; font-size:0.9rem; }
        .priority-urgent { color:#ef4444; font-weight:700; }
        .priority-high { color:#e85d04; font-weight:600; }
        .priority-medium { color:#0077b6; }
        .priority-low { color:#64748b; }
        .modal-overlay { display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:1rem; }
        .modal-content { background:#fff; border-radius:16px; max-width:600px; width:100%; padding:2rem; position:relative; max-height:90vh; overflow-y:auto; }
        .modal-close { position:absolute; top:1rem; right:1rem; background:none; border:none; font-size:1.3rem; cursor:pointer; color:#94a3b8; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar" style="background:linear-gradient(135deg,#7b2cbf,#9d4edd);">
            <div class="sidebar-brand" style="background:rgba(0,0,0,0.2);"><i class="fas fa-book-bible"></i><span>Liturgical</span></div>
            <nav class="sidebar-menu">
                <div class="sidebar-section">Liturgy</div>
                <a href="/holy-trinity/liturgical/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#" onclick="document.getElementById('issueModal').style.display='flex'; return false;"><i class="fas fa-exclamation-triangle"></i> Report Issue</a>
                <a href="#" onclick="document.getElementById('purchaseModal').style.display='flex'; return false;"><i class="fas fa-shopping-cart"></i> Purchase Request</a>
                <a href="#" onclick="document.getElementById('budgetModal').style.display='flex'; return false;"><i class="fas fa-file-invoice"></i> Submit Budget</a>
                <div class="sidebar-section">Navigation</div>
                <?php if (in_array($userRole, ['priest','super_admin','admin'])): ?>
                <a href="/holy-trinity/admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                <?php endif; ?>
                <a href="/holy-trinity/index.php"><i class="fas fa-globe"></i> Website</a>
                <a href="/holy-trinity/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <div class="dashboard-main">
            <?php foreach (['success','error','warning'] as $type): $msg = getFlash($type); if ($msg): ?>
            <div class="flash-message flash-<?= $type ?>" style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:8px;"><?= $msg ?><button onclick="this.parentElement.remove()" style="float:right;background:none;border:none;cursor:pointer;color:inherit;">&times;</button></div>
            <?php endif; endforeach; ?>

            <div class="lit-banner">
                <h1><i class="fas fa-book-bible"></i> Liturgical Management</h1>
                <p>Manage liturgical schedules, issues, purchase requests, and budgets</p>
            </div>

            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); margin-bottom:1.5rem;">
                <div class="stat-card" style="border-left:4px solid #7b2cbf;"><div class="stat-info"><h3><?= count($upcomingSchedules) ?></h3><p>Upcoming Liturgies</p></div></div>
                <div class="stat-card" style="border-left:4px solid #ef4444;"><div class="stat-info"><h3><?= count($openIssues) ?></h3><p>Open Issues</p></div></div>
                <div class="stat-card" style="border-left:4px solid #e85d04;"><div class="stat-info"><h3><?= count($purchaseRequests) ?></h3><p>Purchase Requests</p></div></div>
                <div class="stat-card" style="border-left:4px solid #0077b6;"><div class="stat-info"><h3><?= count($budgets) ?></h3><p>Budgets</p></div></div>
            </div>

            <div class="grid-2">
                <!-- Open Issues -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Open Issues</h3>
                        <button onclick="document.getElementById('issueModal').style.display='flex'" class="btn btn-sm btn-outline"><i class="fas fa-plus"></i> Report</button>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($openIssues)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No open issues</p>
                        <?php else: ?>
                            <?php foreach ($openIssues as $issue): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <div>
                                        <strong><?= sanitize($issue['title']) ?></strong>
                                        <span class="priority-<?= $issue['priority'] ?>" style="font-size:0.75rem; margin-left:0.5rem;">[<?= strtoupper($issue['priority']) ?>]</span>
                                        <br><small class="text-muted"><?= ucfirst(str_replace('_',' ',$issue['issue_type'])) ?> &bull; <?= sanitize($issue['first_name'] . ' ' . $issue['last_name']) ?> &bull; <?= formatDate($issue['created_at']) ?></small>
                                    </div>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="resolve_issue">
                                        <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                                        <input type="hidden" name="resolution_notes" value="Resolved">
                                        <button type="submit" class="btn btn-sm" style="background:#22c55e; color:#fff; border:none; font-size:0.75rem; padding:0.25rem 0.6rem; border-radius:4px; cursor:pointer;"><i class="fas fa-check"></i></button>
                                    </form>
                                </div>
                                <p style="font-size:0.85rem; color:#64748b; margin-top:0.25rem;"><?= sanitize(substr($issue['description'], 0, 100)) ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Liturgies -->
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-calendar-alt" style="color:#7b2cbf;"></i> Upcoming Liturgies</h3></div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($upcomingSchedules)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No upcoming liturgies scheduled</p>
                        <?php else: ?>
                            <?php foreach ($upcomingSchedules as $s): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem;">
                                <strong><?= sanitize($s['celebration'] ?? 'Mass') ?></strong>
                                <br><small class="text-muted">
                                    <?= formatDate($s['liturgical_date']) ?> at <?= $s['mass_time'] ? formatTime($s['mass_time']) : 'TBD' ?>
                                    <?= $s['celebrant_name'] ? ' &bull; ' . sanitize($s['celebrant_name']) : '' ?>
                                    <?= $s['liturgical_season'] ? ' &bull; ' . sanitize($s['liturgical_season']) : '' ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Purchase Requests -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart" style="color:#e85d04;"></i> Purchase Requests</h3>
                        <button onclick="document.getElementById('purchaseModal').style.display='flex'" class="btn btn-sm btn-outline"><i class="fas fa-plus"></i> New</button>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($purchaseRequests)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No purchase requests</p>
                        <?php else: ?>
                            <?php foreach ($purchaseRequests as $pr): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= sanitize($pr['title']) ?></strong>
                                    <br><small class="text-muted"><?= formatDate($pr['created_at']) ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <strong>ZMW <?= number_format($pr['total_amount'], 2) ?></strong>
                                    <br><span class="badge badge-<?= $pr['status'] === 'approved' ? 'success' : ($pr['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($pr['status']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Budgets -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice" style="color:#0077b6;"></i> Budgets</h3>
                        <button onclick="document.getElementById('budgetModal').style.display='flex'" class="btn btn-sm btn-outline"><i class="fas fa-plus"></i> New</button>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($budgets)): ?>
                            <p class="text-muted text-center" style="padding:1.5rem;">No budgets submitted</p>
                        <?php else: ?>
                            <?php foreach ($budgets as $b): ?>
                            <div style="padding:0.75rem 1.25rem; border-bottom:1px solid var(--light-gray); font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= sanitize($b['title']) ?></strong>
                                    <br><small class="text-muted"><?= sanitize($b['budget_period']) ?> &bull; <?= formatDate($b['created_at']) ?></small>
                                </div>
                                <div style="text-align:right;">
                                    <strong>ZMW <?= number_format($b['total_amount'], 2) ?></strong>
                                    <br><span class="badge badge-<?= $b['status'] === 'approved' ? 'success' : ($b['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($b['status']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Issue Modal -->
    <div id="issueModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="this.parentElement.parentElement.style.display='none'">&times;</button>
            <h3 style="color:#7b2cbf; margin-bottom:1.5rem;"><i class="fas fa-exclamation-triangle"></i> Report Liturgical Issue</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="report_issue">
                <div class="form-group"><label>Issue Type</label>
                    <select name="issue_type" class="form-control" required>
                        <option value="vestment">Vestment</option><option value="vessel">Sacred Vessel</option>
                        <option value="book">Liturgical Book</option><option value="furniture">Furniture</option>
                        <option value="sound_system">Sound System</option><option value="lighting">Lighting</option>
                        <option value="decoration">Decoration</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required placeholder="Brief title..."></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3" required placeholder="Describe the issue..."></textarea></div>
                <div class="form-group"><label>Priority</label>
                    <select name="priority" class="form-control"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Submit Issue</button>
            </form>
        </div>
    </div>

    <!-- Purchase Request Modal -->
    <div id="purchaseModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="this.parentElement.parentElement.style.display='none'">&times;</button>
            <h3 style="color:#e85d04; margin-bottom:1.5rem;"><i class="fas fa-shopping-cart"></i> Purchase Request</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="purchase_request">
                <div class="form-group"><label>Title</label><input type="text" name="pr_title" class="form-control" required placeholder="e.g. Candles for Easter"></div>
                <div class="form-group"><label>Description</label><textarea name="pr_description" class="form-control" rows="2" placeholder="Additional details..."></textarea></div>
                <div class="form-group"><label>Urgency</label>
                    <select name="urgency" class="form-control"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                </div>
                <div id="purchaseItems">
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">Items</label>
                    <div class="purchase-item" style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="item_name[]" class="form-control" placeholder="Item name" required>
                        <input type="number" name="item_qty[]" class="form-control" placeholder="Qty" value="1" min="1">
                        <input type="number" name="item_price[]" class="form-control" placeholder="Price (ZMW)" step="0.01" min="0">
                    </div>
                </div>
                <button type="button" onclick="addPurchaseItem()" style="background:none; border:1px dashed #94a3b8; color:#64748b; padding:0.4rem 1rem; border-radius:6px; cursor:pointer; font-size:0.85rem; margin-bottom:1rem;"><i class="fas fa-plus"></i> Add Item</button>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Submit to Parish Executive</button>
            </form>
        </div>
    </div>

    <!-- Budget Modal -->
    <div id="budgetModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="this.parentElement.parentElement.style.display='none'">&times;</button>
            <h3 style="color:#0077b6; margin-bottom:1.5rem;"><i class="fas fa-file-invoice"></i> Submit Budget</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="submit_budget">
                <div class="form-group"><label>Budget Title</label><input type="text" name="budget_title" class="form-control" required placeholder="e.g. Lenten Season Budget"></div>
                <div class="form-group"><label>Budget Period</label><input type="text" name="budget_period" class="form-control" required placeholder="e.g. March 2026 or Q1 2026"></div>
                <div class="form-group"><label>Description</label><textarea name="budget_description" class="form-control" rows="2" placeholder="Budget overview..."></textarea></div>
                <div id="budgetItems">
                    <label style="font-weight:600; margin-bottom:0.5rem; display:block;">Budget Items</label>
                    <div style="display:grid; grid-template-columns:2fr 1fr; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="b_item_name[]" class="form-control" placeholder="Item description" required>
                        <input type="number" name="b_item_amount[]" class="form-control" placeholder="Amount (ZMW)" step="0.01" min="0">
                    </div>
                </div>
                <button type="button" onclick="addBudgetItem()" style="background:none; border:1px dashed #94a3b8; color:#64748b; padding:0.4rem 1rem; border-radius:6px; cursor:pointer; font-size:0.85rem; margin-bottom:1rem;"><i class="fas fa-plus"></i> Add Item</button>
                <div class="form-group"><label>Attachment (optional)</label><input type="file" name="budget_attachment" class="form-control" accept=".pdf,.xlsx,.xls,.doc,.docx"></div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> Submit to Parish Executive</button>
            </form>
        </div>
    </div>

    <script>
    function addPurchaseItem() {
        const div = document.createElement('div');
        div.className = 'purchase-item';
        div.style.cssText = 'display:grid; grid-template-columns:2fr 1fr 1fr; gap:0.5rem; margin-bottom:0.5rem;';
        div.innerHTML = '<input type="text" name="item_name[]" class="form-control" placeholder="Item name" required><input type="number" name="item_qty[]" class="form-control" placeholder="Qty" value="1" min="1"><input type="number" name="item_price[]" class="form-control" placeholder="Price (ZMW)" step="0.01" min="0">';
        document.getElementById('purchaseItems').appendChild(div);
    }
    function addBudgetItem() {
        const div = document.createElement('div');
        div.style.cssText = 'display:grid; grid-template-columns:2fr 1fr; gap:0.5rem; margin-bottom:0.5rem;';
        div.innerHTML = '<input type="text" name="b_item_name[]" class="form-control" placeholder="Item description" required><input type="number" name="b_item_amount[]" class="form-control" placeholder="Amount (ZMW)" step="0.01" min="0">';
        document.getElementById('budgetItems').appendChild(div);
    }
    </script>
    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php include_once __DIR__ . "/../includes/pwa-sw.php"; ?>
</body>
</html>
