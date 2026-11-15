<?php
/**
 * MG Education & Social Development Organization
 * Centralized Email Dispatcher (PHPMailer / SMTP)
 * 
 * Configured using environment variables (.env):
 * - MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION
 * - MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 * - Automatic dual-port fallback (Port 587 TLS <-> Port 465 SSL)
 * - Complete audit logging to logs/mail.log
 */

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Returns a configured PHPMailer instance using .env credentials.
 *
 * @param int|null $overridePort
 * @param string|null $overrideEncryption 'ssl' or 'tls'
 * @return PHPMailer
 */
function createMailer(?int $overridePort = null, ?string $overrideEncryption = null): PHPMailer
{
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = env('MAIL_HOST', 'mail.mgedu.in');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('MAIL_USERNAME', 'team@mgedu.in');
    $mail->Password   = env('MAIL_PASSWORD', '');
    
    $port       = $overridePort ?? (int)env('MAIL_PORT', 587);
    $encryption = $overrideEncryption ?? strtolower((string)env('MAIL_ENCRYPTION', 'tls'));

    if ($port === 465 || $encryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
    } elseif ($port === 587 || $encryption === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
    } else {
        $mail->SMTPSecure  = false;
        $mail->SMTPAutoTLS = false;
        $mail->Port        = $port;
    }

    $mail->Timeout = 10;
    $mail->CharSet = 'UTF-8';

    // Unconditionally relax SSL cert verification for cPanel / local WAMP / Windows OpenSSL
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];

    // Default sender & Return-Path / Envelope Sender
    $fromAddress  = env('MAIL_FROM_ADDRESS', 'team@mgedu.in');
    $fromName     = env('MAIL_FROM_NAME', 'MG EDUCATION & SOCIAL DEVELOPMENT ORGANIZATION');
    $mail->setFrom($fromAddress, $fromName);
    $mail->Sender = $fromAddress; // Critical for Exim / cPanel SPF / Return-Path verification

    return $mail;
}

/**
 * Sends a formal registration confirmation email with login credentials,
 * embedded logo, and organization contacts to the newly authorized center.
 * Features automatic dual-port fallback (Port 587 TLS <-> Port 465 SSL).
 *
 * @param array $center Center data row
 * @param string $plainPassword Plain-text password entered or generated
 * @return array ['success' => bool, 'message' => string, 'error' => ?string]
 */
function sendCenterRegistrationEmail(array $center, string $plainPassword): array
{
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/mail.log';

    $centerEmail = trim($center['center_email'] ?? '');
    if (empty($centerEmail) || !filter_var($centerEmail, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid or empty center email address: '{$centerEmail}'";
        @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: {$err}\n", FILE_APPEND);
        return ['success' => false, 'error' => $err];
    }

    $centerName   = htmlspecialchars($center['center_name'] ?? 'Authorized Study Center');
    $centerCode   = htmlspecialchars($center['center_code'] ?? 'MGEDU');
    $ownerName    = htmlspecialchars($center['owner_name'] ?? 'Center Director');
    $ownerEmail   = trim($center['owner_email'] ?? '');
    $centerMobile = htmlspecialchars($center['center_mobile'] ?? '');
    $city         = htmlspecialchars($center['city'] ?? '');
    $state        = htmlspecialchars($center['state'] ?? '');
    $pincode      = htmlspecialchars($center['pincode'] ?? '');
    $address      = htmlspecialchars($center['address'] ?? '');
    $classrooms   = (int)($center['classrooms_count'] ?? 1);
    $computers    = (int)($center['computers_count'] ?? 0);
    $labType      = htmlspecialchars($center['lab_type'] ?? 'Standard');
    $workingDays  = htmlspecialchars(($center['working_days_from'] ?? 'Mon') . ' to ' . ($center['working_days_to'] ?? 'Sat'));
    $workingHours = htmlspecialchars(($center['working_hours_from'] ?? '09:00 AM') . ' - ' . ($center['working_hours_to'] ?? '06:00 PM'));
    $royalty      = htmlspecialchars($center['royalty_percentage'] ?? '0.00');

    $appUrl       = rtrim(env('APP_URL', 'http://localhost/mgedu.in'), '/');
    $loginUrl     = $appUrl . '/admin/login.php';
    $supportPhone = env('ORG_CONTACT_PHONE', '+91 8059982049');
    $supportEmail = env('ORG_CONTACT_EMAIL', 'team@mgedu.in');
    $orgName      = env('APP_NAME', 'MG Education & Social Development Organization');
    $isoCert      = env('ORG_ISO_CERT', 'ISO 9001:2015 Certified');
    $nitiDarpan   = env('ORG_NITI_DARPAN', 'NITI Aayog Darpan Registered');

    $configuredPort = (int)env('MAIL_PORT', 587);
    $configuredEnc  = strtolower((string)env('MAIL_ENCRYPTION', 'tls'));

    // Try primary configuration first; fallback to secondary port if blocked
    $attempts = [];
    if ($configuredPort === 465 || $configuredEnc === 'ssl') {
        $attempts[] = ['port' => 465, 'enc' => 'ssl', 'name' => 'Port 465 (SSL)'];
        $attempts[] = ['port' => 587, 'enc' => 'tls', 'name' => 'Port 587 (STARTTLS)'];
    } else {
        $attempts[] = ['port' => 587, 'enc' => 'tls', 'name' => 'Port 587 (STARTTLS)'];
        $attempts[] = ['port' => 465, 'enc' => 'ssl', 'name' => 'Port 465 (SSL)'];
    }

    $lastError = '';

    foreach ($attempts as $idx => $attempt) {
        try {
            $mail = createMailer($attempt['port'], $attempt['enc']);

            // Recipients
            $mail->addAddress($centerEmail, $centerName);
            if (!empty($ownerEmail) && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) && strtolower($ownerEmail) !== strtolower($centerEmail)) {
                $mail->addCC($ownerEmail, $ownerName);
            }

            // Embed MG Education official logo
            $logoPath = dirname(__DIR__) . '/assets/images/logo.jpg';
            $hasEmbeddedLogo = false;
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'mg_official_logo', 'logo.jpg', 'base64', 'image/jpeg');
                $hasEmbeddedLogo = true;
            }

            $mail->isHTML(true);
            $mail->Subject = "Study Center Authorization & Portal Credentials: {$centerName} [{$centerCode}]";

            // HTML Header Graphic
            $logoHtml = $hasEmbeddedLogo 
                ? '<img src="cid:mg_official_logo" alt="MG Education Logo" style="max-height: 58px; max-width: 260px; display: block; margin: 0 auto;">'
                : '<h2 style="color: #071d33; margin: 0; font-size: 22px; font-weight: 800; text-align: center;">' . $orgName . '</h2>';

            $bodyHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Center Registration & Portal Credentials</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">

    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table width="640" border="0" cellpadding="0" cellspacing="0" style="max-width: 640px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">

                    <!-- Top Accent Bar -->
                    <tr>
                        <td height="6" style="background: linear-gradient(90deg, #0284c7 0%, #071d33 50%, #f59e0b 100%); line-height: 6px; font-size: 6px;">&nbsp;</td>
                    </tr>

                    <!-- Organization Header -->
                    <tr>
                        <td style="padding: 28px 36px 20px; text-align: center; border-bottom: 1px solid #e2e8f0; background-color: #ffffff;">
                            {$logoHtml}
                            <div style="margin-top: 10px; font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">
                                {$isoCert} &bull; {$nitiDarpan}
                            </div>
                        </td>
                    </tr>

                    <!-- Hero Confirmation Banner -->
                    <tr>
                        <td style="padding: 26px 36px 16px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="display: inline-block; padding: 4px 12px; background-color: #ecfdf5; color: #047857; font-size: 11px; font-weight: 700; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #a7f3d0; margin-bottom: 10px;">
                                            &bull; Registration Confirmed &amp; Authorized
                                        </div>
                                        <h1 style="margin: 0; font-size: 21px; font-weight: 700; color: #0f172a; line-height: 1.3;">
                                            Welcome to the MG Education Network
                                        </h1>
                                        <p style="margin: 8px 0 0; font-size: 13px; color: #475569; line-height: 1.5;">
                                            Dear <strong>{$ownerName}</strong>, your institution <strong>{$centerName}</strong> has been successfully registered and authorized as an official study center.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Credentials Box -->
                    <tr>
                        <td style="padding: 24px 36px;">
                            <div style="background-color: #071d33; border-radius: 10px; padding: 22px 26px; color: #ffffff;">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #38bdf8; font-weight: 700; margin-bottom: 12px;">
                                    Center Administrator Login Credentials
                                </div>
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="color: #ffffff;">
                                    <tr>
                                        <td width="38%" style="padding: 6px 0; font-size: 13px; color: #94a3b8;">Center Code / ID:</td>
                                        <td width="62%" style="padding: 6px 0; font-size: 15px; font-weight: 700; color: #ffffff; font-family: monospace;">{$centerCode}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; font-size: 13px; color: #94a3b8;">Official Username / Email:</td>
                                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #38bdf8;">{$centerEmail}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; font-size: 13px; color: #94a3b8;">Temporary Password:</td>
                                        <td style="padding: 6px 0; font-size: 15px; font-weight: 700; color: #facc15; font-family: monospace;">{$plainPassword}</td>
                                    </tr>
                                </table>
                                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.12); text-align: center;">
                                    <a href="{$loginUrl}" target="_blank" style="display: inline-block; background-color: #0284c7; color: #ffffff; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; letter-spacing: 0.3px;">
                                        Access Center Management Portal &rarr;
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Center Summary Details -->
                    <tr>
                        <td style="padding: 0 36px 24px;">
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                                Authorized Center Profile Record
                            </div>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 13px; color: #334155; line-height: 1.6;">
                                <tr>
                                    <td width="35%" style="padding: 5px 0; color: #64748b;">Institute Name:</td>
                                    <td width="65%" style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$centerName}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #64748b;">Center Director / Owner:</td>
                                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$ownerName} ({$centerMobile})</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #64748b;">Center Location:</td>
                                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$address}, {$city}, {$state} - {$pincode}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #64748b;">Infrastructure:</td>
                                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$classrooms} Classrooms &bull; {$computers} Computer Terminals &bull; {$labType} Lab</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #64748b;">Working Hours:</td>
                                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$workingDays} ({$workingHours})</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #64748b;">Royalty Sharing:</td>
                                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{$royalty}% Organization Share</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Security Advisory -->
                    <tr>
                        <td style="padding: 0 36px 24px;">
                            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #92400e; line-height: 1.5;">
                                <strong>Security Reminder:</strong> For your center's protection, please log in immediately at <a href="{$loginUrl}" style="color: #b45309; font-weight: 700;">{$loginUrl}</a> and change your temporary password under Profile Settings.
                            </div>
                        </td>
                    </tr>

                    <!-- Support Helpdesk Contact -->
                    <tr>
                        <td style="padding: 20px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                            Need Assistance or Franchise Support?
                                        </div>
                                        <div style="font-size: 12px; color: #475569; line-height: 1.6;">
                                            Helpline: <strong style="color: #071d33;">{$supportPhone}</strong> &bull; Email: <strong style="color: #0284c7;">{$supportEmail}</strong><br>
                                            Website: <a href="{$appUrl}" target="_blank" style="color: #0284c7; text-decoration: none;">{$appUrl}</a><br>
                                            Head Office: {$orgName}, India
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Footer & Legal Disclaimer -->
                    <tr>
                        <td align="center" style="background-color: #071d33; padding: 18px 24px; color: #94a3b8; font-size: 11px; line-height: 1.5;">
                            &copy; {$orgName}. All rights reserved.<br>
                            This is an automated formal notification. Please do not reply directly to this email address.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
HTML;

            $mail->Body = $bodyHtml;

            // Plain text alternative
            $mail->AltBody = "CONGRATULATIONS - STUDY CENTER REGISTRATION SUCCESSFUL\n"
                . "Institute: {$centerName}\n"
                . "Center Code: {$centerCode}\n"
                . "Center Email: {$centerEmail}\n"
                . "Temporary Password: {$plainPassword}\n"
                . "Portal Login: {$loginUrl}\n\n"
                . "Address: {$address}, {$city}, {$state} - {$pincode}\n"
                . "Helpline: {$supportPhone}\n"
                . "Website: {$appUrl}\n\n"
                . "{$orgName}";

            $mail->send();

            // Log success
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SUCCESS: Registration email sent to {$centerEmail} via {$attempt['name']}\n", FILE_APPEND);

            return [
                'success' => true,
                'message' => "Registration email sent successfully to {$centerEmail} via {$attempt['name']}."
            ];

        } catch (Throwable $e) {
            $lastError = $e->getMessage();
            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ATTEMPT FAILED ({$attempt['name']}) for {$centerEmail}: {$lastError}\n", FILE_APPEND);
        }
    }

    return [
        'success' => false,
        'error'   => "SMTP Delivery Failed: " . $lastError
    ];
}
