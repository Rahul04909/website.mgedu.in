<?php
/**
 * SMTP Diagnostic Tester
 * Open in browser: http://localhost/mgedu.in/test_mail.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

header('Content-Type: text/plain; charset=utf-8');

echo "=== MG EDUCATION & SOCIAL DEVELOPMENT ORGANIZATION ===\n";
echo "=== SMTP DIAGNOSTIC & DISPATCH HEALTH CHECK ===\n\n";

$host = env('MAIL_HOST', 'mail.mgedu.in');
$port = (int)env('MAIL_PORT', 587);
$user = env('MAIL_USERNAME', 'team@mgedu.in');
$pass = env('MAIL_PASSWORD', '');
$enc  = env('MAIL_ENCRYPTION', 'tls');

echo "Configuration from .env:\n";
echo " - Host:        $host\n";
echo " - Port:        $port\n";
echo " - Username:    $user\n";
echo " - Password:    " . (empty($pass) ? "[EMPTY!]" : "[CONFIGURED: " . strlen($pass) . " chars]") . "\n";
echo " - Encryption:  $enc\n\n";

// Test 1: Sockets connect to Port 587
echo "Test 1: Socket probe to $host:587 (STARTTLS)...\n";
$errno = 0; $errstr = '';
$socket587 = @fsockopen($host, 587, $errno, $errstr, 4);
if ($socket587) {
    echo "  [OK] Port 587 is reachable!\n";
    $banner = fgets($socket587, 512);
    echo "  Banner: " . trim($banner) . "\n";
    fclose($socket587);
} else {
    echo "  [BLOCKED/OFFLINE] Port 587 unreachable ($errno: $errstr)\n";
}

// Test 2: Sockets connect to Port 465
echo "\nTest 2: Socket probe to $host:465 (Direct SSL)...\n";
$socket465 = @fsockopen("ssl://$host", 465, $errno, $errstr, 4);
if ($socket465) {
    echo "  [OK] Port 465 is reachable via SSL!\n";
    $banner = fgets($socket465, 512);
    echo "  Banner: " . trim($banner) . "\n";
    fclose($socket465);
} else {
    echo "  [BLOCKED/OFFLINE] Port 465 unreachable ($errno: $errstr)\n";
}

// Test 3: Registration Email Pipeline with Auto-Fallback
echo "\nTest 3: Dispatching Test Registration Email via sendCenterRegistrationEmail()...\n";
$sampleCenter = [
    'center_code'        => 'MGEDU2026TEST',
    'center_name'        => 'MG Test Academy',
    'center_email'       => $user,
    'owner_name'         => 'Rahul Sharma',
    'owner_email'        => $user,
    'center_mobile'      => '+91 8059982049',
    'city'               => 'Hisar',
    'state'              => 'Haryana',
    'pincode'            => '125001',
    'address'            => 'Urban Estate II',
    'classrooms_count'   => 2,
    'computers_count'    => 20,
    'lab_type'           => 'Advanced',
    'working_days_from'  => 'Mon',
    'working_days_to'    => 'Sat',
    'working_hours_from' => '09:00 AM',
    'working_hours_to'   => '06:00 PM',
    'royalty_percentage' => 20.00
];

$result = sendCenterRegistrationEmail($sampleCenter, 'TestPass@2026');
echo "\nResult:\n";
print_r($result);

echo "\nCheck logs/mail.log for detailed audit entries.\n";
