<?php
/**
 * MG Education & Social Development Organization
 * Live Email Dispatcher & SMTP Diagnostic Tool
 * 
 * Usage: Open http://localhost/mgedu.in/test_mail.php in your browser
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$targetEmail = trim($_POST['target_email'] ?? ($_GET['to'] ?? ''));
$action      = $_POST['action'] ?? ($_GET['action'] ?? '');
$testResult  = null;
$debugOutput = '';

if ($action === 'send' && !empty($targetEmail)) {
    $sampleCenter = [
        'center_code'        => 'MGEDU' . date('Y') . '999',
        'center_name'        => 'MG Education Test Academy',
        'center_email'       => $targetEmail,
        'owner_name'         => 'Franchise Director',
        'owner_email'        => $targetEmail,
        'center_mobile'      => '+91 8059982049',
        'city'               => 'New Delhi',
        'state'              => 'Delhi',
        'pincode'            => '110001',
        'address'            => 'Connaught Place, Central Delhi',
        'classrooms_count'   => 2,
        'computers_count'    => 20,
        'lab_type'           => 'Advanced',
        'working_days_from'  => 'Monday',
        'working_days_to'    => 'Saturday',
        'working_hours_from' => '09:00 AM',
        'working_hours_to'   => '06:00 PM',
        'royalty_percentage' => 20.00
    ];

    $testResult = sendCenterRegistrationEmail($sampleCenter, 'MG@2026!DemoPass', $debugOutput);
}

// Read last 20 log entries
$logFile = __DIR__ . '/logs/mail.log';
$logLines = [];
if (file_exists($logFile)) {
    $all = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logLines = array_slice($all, -20);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MG Education - Live Mail Dispatch Tester</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,23,42,0.06); }
        .btn-navy { background-color: #071d33; color: #fff; font-weight: 600; }
        .btn-navy:hover { background-color: #0b2542; color: #fff; }
        pre.debug-box { background: #071d33; color: #38bdf8; padding: 14px 18px; border-radius: 8px; font-size: 12px; max-height: 380px; overflow-y: auto; }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 860px;">

        <!-- Header -->
        <div class="card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-paper-plane text-primary me-2"></i>Live Email Dispatch &amp; Notification Tester</h4>
                    <p class="text-muted small mb-0">Test delivery directly to any center or personal registered email ID.</p>
                </div>
                <a href="admin/centers/index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Admin
                </a>
            </div>
        </div>

        <!-- Dispatch Form -->
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-envelope me-2 text-primary"></i>Send Test Center Notification</h5>
            <form method="POST" action="">
                <input type="hidden" name="action" value="send">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Target Center Registered Email Address:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-at text-muted"></i></span>
                        <input type="email" name="target_email" class="form-control" placeholder="Enter your email (e.g. rahul@gmail.com, center@...)" value="<?= htmlspecialchars($targetEmail) ?>" required autofocus>
                        <button type="submit" class="btn btn-navy px-4">
                            <i class="fas fa-paper-plane me-1"></i> Send Test Notification
                        </button>
                    </div>
                    <div class="form-text">
                        An official registration confirmation email with credentials and logo will be sent to this email.
                    </div>
                </div>
            </form>

            <!-- Test Result -->
            <?php if ($testResult !== null): ?>
                <div class="mt-3">
                    <?php if ($testResult['success']): ?>
                        <div class="alert alert-success d-flex align-items-center rounded-3">
                            <i class="fas fa-circle-check fa-lg me-3"></i>
                            <div>
                                <strong class="d-block">Mail Dispatched Successfully!</strong>
                                <span><?= htmlspecialchars($testResult['message']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger d-flex align-items-center rounded-3">
                            <i class="fas fa-circle-xmark fa-lg me-3"></i>
                            <div>
                                <strong class="d-block">Mail Dispatch Failed</strong>
                                <span><?= htmlspecialchars($testResult['error'] ?? 'Unknown Error') ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($debugOutput)): ?>
                        <h6 class="fw-bold mt-3 mb-2"><i class="fas fa-terminal me-2"></i>Live SMTP Handshake Log:</h6>
                        <pre class="debug-box"><?= htmlspecialchars($debugOutput) ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Configuration Status -->
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-server me-2 text-primary"></i>Current SMTP Environment</h5>
            <div class="row g-2 small">
                <div class="col-sm-6"><strong>SMTP Host:</strong> <code><?= htmlspecialchars(env('MAIL_HOST', 'mail.mgedu.in')) ?></code></div>
                <div class="col-sm-6"><strong>Primary Port:</strong> <code><?= htmlspecialchars(env('MAIL_PORT', 587)) ?> (<?= htmlspecialchars(env('MAIL_ENCRYPTION', 'tls')) ?>)</code></div>
                <div class="col-sm-6"><strong>From Sender:</strong> <code><?= htmlspecialchars(env('MAIL_FROM_ADDRESS', 'team@mgedu.in')) ?></code></div>
                <div class="col-sm-6"><strong>Auto-Fallback:</strong> <span class="badge bg-success">Port 465 (SSL)</span></div>
                <div class="col-sm-6"><strong>EHLO / Hostname:</strong> <code>mgedu.in (RFC 5322 Compliant)</code></div>
                <div class="col-sm-6"><strong>Anti-Spam Optimization:</strong> <span class="badge bg-info text-dark">Active</span></div>
            </div>
        </div>

        <!-- Audit Log View -->
        <div class="card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-list-check me-2 text-primary"></i>Recent Dispatch Audit Log (logs/mail.log)</h5>
                <span class="badge bg-light text-dark border"><?= count($logLines) ?> entries</span>
            </div>
            <?php if (!empty($logLines)): ?>
                <pre class="debug-box mb-0"><?= htmlspecialchars(implode("\n", array_reverse($logLines))) ?></pre>
            <?php else: ?>
                <p class="text-muted small mb-0">No mail log entries yet.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
