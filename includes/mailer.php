<?php
/**
 * MG Education & Social Development Organization
 * Centralized Email Dispatcher (PHPMailer / SMTP)
 * 
 * Configured using environment variables (.env):
 * - MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION
 * - MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 * - Organization contact details & embedded logo
 */

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Returns a configured PHPMailer instance using .env credentials.
 *
 * @return PHPMailer
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = env('MAIL_HOST', 'mail.mgedu.in');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('MAIL_USERNAME', 'team@mgedu.in');
    $mail->Password   = env('MAIL_PASSWORD', '');
    
    $encryption       = strtolower(env('MAIL_ENCRYPTION', 'tls'));
    if ($encryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }

    $mail->Port       = (int)env('MAIL_PORT', 587);
    $mail->Timeout    = 15;
    $mail->CharSet    = 'UTF-8';

    // Allow self-signed certificates in local development if needed
    if (env('APP_ENV', 'local') === 'local') {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
    }

    // Default sender
    $fromAddress = env('MAIL_FROM_ADDRESS', 'team@mgedu.in');
    $fromName    = env('MAIL_FROM_NAME', 'MG EDUCATION & SOCIAL DEVELOPMENT ORGANIZATION');
    $mail->setFrom($fromAddress, $fromName);

    return $mail;
}

/**
 * Sends a formal registration confirmation email with login credentials,
 * embedded logo, and organization contacts to the newly authorized center.
 *
 * @param array $center Center data row
 * @param string $plainPassword Plain-text password entered or generated
 * @return array ['success' => bool, 'message' => string]
 */
function sendCenterRegistrationEmail(array $center, string $plainPassword): array
{
    try {
        $mail = createMailer();

        $centerName   = htmlspecialchars($center['center_name'] ?? 'Authorized Study Center');
        $centerCode   = htmlspecialchars($center['center_code'] ?? 'MGEDU');
        $centerEmail  = trim($center['center_email'] ?? '');
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

        // Recipients
        $mail->addAddress($centerEmail, $centerName);
        if (!empty($ownerEmail) && strtolower($ownerEmail) !== strtolower($centerEmail)) {
            $mail->addCC($ownerEmail, $ownerName);
        }

        // Embed MG Education official logo
        $logoPath = ROOT_PATH . '/assets/images/logo.jpg';
        $hasEmbeddedLogo = false;
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'mg_official_logo', 'logo.jpg');
            $hasEmbeddedLogo = true;
        }

        $mail->isHTML(true);
        $mail->Subject = "Study Center Authorization & Portal Credentials: {$centerName} [{$centerCode}]";

        // HTML Body
        $logoHtml = $hasEmbeddedLogo 
            ? '<img src="cid:mg_official_logo" alt="MG Education Logo" style="max-height: 58px; max-width: 240px; display: block; margin: 0 auto;">'
            : '<h2 style="color: #071d33; margin: 0; font-size: 22px; font-weight: 800;">' . $orgName . '</h2>';

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
                <table width="640" border="0" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">
                    
                    <!-- Top Organization Header -->
                    <tr>
                        <td align="center" style="background-color: #ffffff; padding: 26px 24px; border-bottom: 2px solid #00a2db;">
                            {$logoHtml}
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; font-weight: 700; margin-top: 8px;">
                                {$isoCert} &bull; {$nitiDarpan}
                            </div>
                        </td>
                    </tr>

                    <!-- Authorization Announcement Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #071d33 0%, #0b2542 100%); padding: 24px 30px; color: #ffffff;">
                            <div style="display: inline-block; background-color: rgba(0, 162, 219, 0.25); color: #38bdf8; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 4px 12px; border-radius: 4px; margin-bottom: 8px;">
                                Official Franchise Onboarding
                            </div>
                            <h1 style="margin: 0; font-size: 21px; font-weight: 700; line-height: 1.3; color: #ffffff;">
                                Congratulations! Center Registration Successful
                            </h1>
                            <p style="margin: 6px 0 0; font-size: 13px; color: #cbd5e1; line-height: 1.5;">
                                Your institute has been formally accredited and integrated into the national MG Education network.
                            </p>
                        </td>
                    </tr>

                    <!-- Greeting & Introduction -->
                    <tr>
                        <td style="padding: 26px 30px 14px;">
                            <p style="font-size: 15px; color: #0f172a; margin: 0 0 10px; font-weight: 600;">
                                Dear {$ownerName} / Management Team,
                            </p>
                            <p style="font-size: 13.5px; line-height: 1.6; color: #334155; margin: 0;">
                                We are pleased to confirm that <strong>{$centerName}</strong> has been registered with authorization code 
                                <strong style="color: #0284c7; font-family: monospace; font-size: 14px;">{$centerCode}</strong>. 
                                Below are your confidential portal credentials and official registration summary.
                            </p>
                        </td>
                    </tr>

                    <!-- Confidential Credentials Box -->
                    <tr>
                        <td style="padding: 0 30px 20px;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    <td colspan="2" style="background-color: #0284c7; color: #ffffff; padding: 10px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        &#128274; Center Portal Login Credentials
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #64748b; width: 38%;">
                                        <strong>Center Code / ID:</strong>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #071d33; font-weight: 700; font-family: monospace;">
                                        {$centerCode}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #64748b;">
                                        <strong>Login Email:</strong>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13.5px; color: #0f172a; font-weight: 600;">
                                        {$centerEmail}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #64748b;">
                                        <strong>Initial Password:</strong>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #b91c1c; font-weight: 700; font-family: monospace;">
                                        {$plainPassword}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #64748b;">
                                        <strong>Portal Access:</strong>
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 13px;">
                                        <a href="{$loginUrl}" target="_blank" style="display: inline-block; background-color: #071d33; color: #ffffff; text-decoration: none; font-size: 12px; font-weight: 700; padding: 7px 16px; border-radius: 6px;">
                                            Access Portal &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size: 11px; color: #64748b; margin-top: 6px; font-style: italic;">
                                * For security purposes, please login and update your temporary password immediately upon first access.
                            </div>
                        </td>
                    </tr>

                    <!-- Registered Center Specifications -->
                    <tr>
                        <td style="padding: 0 30px 22px;">
                            <div style="font-size: 12.5px; font-weight: 700; color: #071d33; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">
                                Institute Infrastructure &amp; Location Profile
                            </div>
                            <table width="100%" border="0" cellpadding="8" cellspacing="0" style="font-size: 12.5px; border-collapse: collapse; border: 1px solid #e2e8f0;">
                                <tr style="background-color: #f8fafc;">
                                    <td style="border: 1px solid #e2e8f0; color: #64748b; width: 35%;"><strong>Center Address:</strong></td>
                                    <td style="border: 1px solid #e2e8f0; color: #0f172a;">{$address}, {$city}, {$state} - {$pincode}</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #e2e8f0; color: #64748b;"><strong>Center Contact:</strong></td>
                                    <td style="border: 1px solid #e2e8f0; color: #0f172a;">{$centerMobile} | Director: {$ownerName}</td>
                                </tr>
                                <tr style="background-color: #f8fafc;">
                                    <td style="border: 1px solid #e2e8f0; color: #64748b;"><strong>Infrastructure:</strong></td>
                                    <td style="border: 1px solid #e2e8f0; color: #0f172a;">{$classrooms} Classroom(s) &bull; {$computers} Computer Terminals ({$labType} Lab)</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #e2e8f0; color: #64748b;"><strong>Operational Timing:</strong></td>
                                    <td style="border: 1px solid #e2e8f0; color: #0f172a;">{$workingDays} ({$workingHours})</td>
                                </tr>
                                <tr style="background-color: #f8fafc;">
                                    <td style="border: 1px solid #e2e8f0; color: #64748b;"><strong>Royalty Agreement:</strong></td>
                                    <td style="border: 1px solid #e2e8f0; color: #0f172a; font-weight: 700; color: #0284c7;">{$royalty}% Monthly</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Official Support & Helpline -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 700; color: #071d33; margin-bottom: 4px;">
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

        // Plain Text Alternative Body
        $mail->AltBody = "CONGRATULATIONS - STUDY CENTER REGISTRATION SUCCESSFUL\n"
            . "Institute: {$centerName}\n"
            . "Center Code: {$centerCode}\n"
            . "Center Email: {$centerEmail}\n"
            . "Temporary Password: {$plainPassword}\n"
            . "Portal Login: {$loginUrl}\n\n"
            . "Address: {$address}, {$city}, {$state} - {$pincode}\n"
            . "Helpline: {$supportPhone}\n"
            . "Website: {$appUrl}\n\n"
            . "MG Education & Social Development Organization";

        $mail->send();
        return ['success' => true, 'message' => 'Registration email sent successfully to ' . $centerEmail];

    } catch (Throwable $e) {
        error_log("Failed to send center registration email: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

