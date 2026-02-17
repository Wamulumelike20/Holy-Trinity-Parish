<?php
$pageTitle = 'Sacramental Certificate';
require_once __DIR__ . '/../config/app.php';

$db = Database::getInstance();
$recordId = intval($_GET['id'] ?? 0);

if (!$recordId) {
    setFlash('error', 'Record not found.');
    redirect('/holy-trinity/admin/sacraments.php');
}

$record = $db->fetch("SELECT * FROM sacramental_records WHERE id = ?", [$recordId]);

if (!$record) {
    setFlash('error', 'Record not found.');
    redirect('/holy-trinity/admin/sacraments.php');
}

$typeLabels = [
    'baptism' => 'Certificate of Baptism',
    'confirmation' => 'Certificate of Confirmation',
    'marriage' => 'Certificate of Marriage',
    'funeral' => 'Certificate of Christian Burial',
    'first_communion' => 'Certificate of First Holy Communion',
    'anointing' => 'Certificate of Anointing of the Sick',
];

$certTitle = $typeLabels[$record['record_type']] ?? 'Sacramental Certificate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $certTitle ?> | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f0f0; padding: 2rem; }

        .print-controls {
            text-align: center;
            margin-bottom: 2rem;
        }
        .print-controls button {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 0 0.5rem;
            font-family: 'Inter', sans-serif;
        }
        .btn-print { background: #1a3a5c; color: white; }
        .btn-back { background: #e2e8f0; color: #374151; }
        .btn-print:hover { background: #0f2440; }
        .btn-back:hover { background: #cbd5e1; }

        .certificate {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border: 3px solid #d4a843;
            border-radius: 4px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .certificate::before {
            content: '';
            position: absolute;
            inset: 8px;
            border: 1px solid #d4a843;
            border-radius: 2px;
            pointer-events: none;
        }

        .cert-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #d4a843;
        }

        .cert-cross {
            font-size: 2.5rem;
            color: #d4a843;
            margin-bottom: 0.5rem;
        }

        .cert-parish {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            color: #1a3a5c;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .cert-diocese {
            font-size: 0.9rem;
            color: #6b7280;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            color: #8b1a1a;
            text-align: center;
            margin: 2rem 0;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .cert-body {
            text-align: center;
            line-height: 2.2;
            font-size: 1rem;
            color: #374151;
            margin-bottom: 2rem;
        }

        .cert-name {
            font-family: 'Great Vibes', cursive;
            font-size: 2.5rem;
            color: #1a3a5c;
            display: block;
            margin: 0.5rem 0;
        }

        .cert-details {
            margin: 2rem auto;
            max-width: 600px;
        }

        .cert-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dotted #d4a843;
            font-size: 0.95rem;
        }

        .cert-detail-row .label {
            color: #6b7280;
            font-weight: 500;
        }

        .cert-detail-row .value {
            color: #1a3a5c;
            font-weight: 600;
        }

        .cert-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding-top: 2rem;
        }

        .cert-signature {
            text-align: center;
            min-width: 200px;
        }

        .cert-signature .line {
            border-top: 1px solid #374151;
            margin-bottom: 0.3rem;
            width: 200px;
        }

        .cert-signature .sig-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cert-ref {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .cert-seal {
            position: absolute;
            bottom: 80px;
            right: 60px;
            width: 100px;
            height: 100px;
            border: 3px solid #d4a843;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-size: 0.6rem;
            color: #d4a843;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
        }

        @media print {
            body { background: white; padding: 0; }
            .print-controls { display: none; }
            .certificate { box-shadow: none; border-width: 2px; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Certificate</button>
        <button class="btn-back" onclick="history.back()"><i class="fas fa-arrow-left"></i> Go Back</button>
    </div>

    <div class="certificate">
        <div class="cert-header">
            <div class="cert-cross">&#10013;</div>
            <div class="cert-parish">Holy Trinity Parish</div>
            <div class="cert-diocese">Archdiocese of Kampala &bull; Uganda</div>
        </div>

        <div class="cert-title"><?= $certTitle ?></div>

        <div class="cert-body">
            This is to certify that
            <span class="cert-name"><?= sanitize($record['person_first_name'] . ' ' . $record['person_last_name']) ?></span>

            <?php if ($record['record_type'] === 'baptism'): ?>
                born on <?= $record['person_dob'] ? formatDate($record['person_dob'], 'F j, Y') : '___________' ?>
                <br>child of <strong><?= sanitize($record['father_name'] ?? '___________') ?></strong>
                and <strong><?= sanitize($record['mother_name'] ?? '___________') ?></strong>
                <br>was solemnly baptized on <strong><?= formatDate($record['sacrament_date'], 'F j, Y') ?></strong>

            <?php elseif ($record['record_type'] === 'confirmation'): ?>
                was confirmed in the Holy Spirit on
                <br><strong><?= formatDate($record['sacrament_date'], 'F j, Y') ?></strong>

            <?php elseif ($record['record_type'] === 'marriage'): ?>
                and <span class="cert-name" style="font-size:2rem;"><?= sanitize($record['spouse_name'] ?? '___________') ?></span>
                were united in Holy Matrimony on
                <br><strong><?= formatDate($record['sacrament_date'], 'F j, Y') ?></strong>

            <?php elseif ($record['record_type'] === 'funeral'): ?>
                was commended to the mercy of God on
                <br><strong><?= formatDate($record['sacrament_date'], 'F j, Y') ?></strong>

            <?php else: ?>
                received the Sacrament of <?= ucfirst(str_replace('_', ' ', $record['record_type'])) ?> on
                <br><strong><?= formatDate($record['sacrament_date'], 'F j, Y') ?></strong>
            <?php endif; ?>

            <br>at <strong><?= sanitize($record['place'] ?? 'Holy Trinity Parish') ?></strong>
        </div>

        <div class="cert-details">
            <?php if ($record['minister_name']): ?>
            <div class="cert-detail-row">
                <span class="label">Minister</span>
                <span class="value"><?= sanitize($record['minister_name']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($record['sponsor1_name']): ?>
            <div class="cert-detail-row">
                <span class="label"><?= $record['record_type'] === 'marriage' ? 'Witness 1' : 'Godparent 1' ?></span>
                <span class="value"><?= sanitize($record['sponsor1_name']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($record['sponsor2_name']): ?>
            <div class="cert-detail-row">
                <span class="label"><?= $record['record_type'] === 'marriage' ? 'Witness 2' : 'Godparent 2' ?></span>
                <span class="value"><?= sanitize($record['sponsor2_name']) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($record['register_number']): ?>
            <div class="cert-detail-row">
                <span class="label">Register No.</span>
                <span class="value"><?= sanitize($record['register_number']) ?><?php if ($record['page_number']): ?>, Page <?= sanitize($record['page_number']) ?><?php endif; ?><?php if ($record['entry_number']): ?>, Entry <?= sanitize($record['entry_number']) ?><?php endif; ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="cert-footer">
            <div class="cert-signature">
                <div class="line"></div>
                <div class="sig-label">Parish Priest</div>
            </div>
            <div class="cert-signature">
                <div class="line"></div>
                <div class="sig-label">Date Issued</div>
            </div>
        </div>

        <div class="cert-seal">
            Parish<br>Seal
        </div>

        <div class="cert-ref">
            Reference: <?= $record['reference_number'] ?> &bull; Generated: <?= date('F j, Y') ?>
        </div>
    </div>
</body>
</html>
