<?php
/**
 * MG Education & Social Development Organization
 * Admin Table Creator & Seed Importer (sql-tables/import-admin.php)
 * 
 * Sets up the `admins` database table with production-grade security schema
 * and inserts/updates the primary Super Admin account.
 */

// 1. Load Environment & Global Config
require_once dirname(__DIR__) . '/includes/config.php';

// 2. Database Connection with Database Auto-Creation Fallback
$host     = env('DB_HOST', '127.0.0.1');
$port     = env('DB_PORT', '3306');
$database = env('DB_DATABASE', 'jghfrodu_mgone');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$charset  = env('DB_CHARSET', 'utf8mb4');

$statusMessage = "";
$statusType = "info";
$adminCredentials = [
    'name'        => 'Rahul Sharma',
    'username'    => 'admin',
    'email'       => 'admin@mgedu.in',
    'mobile'      => '+91 8059982049',
    'profile_pic' => './src/images/user-avtar.png',
    'password'    => 'Admin@MGedu2026!', // Plain text to share with user
    'role'        => 'Super Admin',
    'status'      => 'active'
];

try {
    // Attempt connecting to MySQL Server first
    $rootDsn = "mysql:host={$host};port={$port};charset={$charset}";
    $pdoRoot = new PDO($rootDsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Ensure database exists
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");

    // Connect to specific database
    $dbDsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    $pdo = new PDO($dbDsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);

    // 3. Create `admins` Table Schema
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `email` VARCHAR(191) NOT NULL UNIQUE,
        `mobile` VARCHAR(25) NOT NULL,
        `profile_pic` VARCHAR(255) DEFAULT './src/images/user-avtar.png',
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(50) DEFAULT 'Super Admin',
        `status` ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
        `failed_login_attempts` TINYINT UNSIGNED DEFAULT 0,
        `lockout_until` DATETIME NULL,
        `last_login_at` DATETIME NULL,
        `last_login_ip` VARCHAR(45) NULL,
        `remember_token` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_username` (`username`),
        INDEX `idx_email` (`email`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci;
    ";

    $pdo->exec($createTableSql);

    // 4. Seed / Import Super Admin Account
    $checkStmt = $pdo->prepare("SELECT id FROM `admins` WHERE username = :username OR email = :email LIMIT 1");
    $checkStmt->execute([
        ':username' => $adminCredentials['username'],
        ':email'    => $adminCredentials['email']
    ]);
    $existingAdmin = $checkStmt->fetch();

    $hashedPassword = password_hash($adminCredentials['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    if ($existingAdmin) {
        // Update existing record
        $updateStmt = $pdo->prepare("
            UPDATE `admins` 
            SET name = :name,
                mobile = :mobile,
                profile_pic = :profile_pic,
                password = :password,
                role = :role,
                status = :status,
                failed_login_attempts = 0,
                lockout_until = NULL,
                updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':name'        => $adminCredentials['name'],
            ':mobile'      => $adminCredentials['mobile'],
            ':profile_pic' => $adminCredentials['profile_pic'],
            ':password'    => $hashedPassword,
            ':role'        => $adminCredentials['role'],
            ':status'      => $adminCredentials['status'],
            ':id'          => $existingAdmin['id']
        ]);
        $statusMessage = "Admin account existed and was successfully refreshed with updated credentials!";
        $statusType = "success";
    } else {
        // Insert fresh admin record
        $insertStmt = $pdo->prepare("
            INSERT INTO `admins` (name, username, email, mobile, profile_pic, password, role, status)
            VALUES (:name, :username, :email, :mobile, :profile_pic, :password, :role, :status)
        ");
        $insertStmt->execute([
            ':name'        => $adminCredentials['name'],
            ':username'    => $adminCredentials['username'],
            ':email'       => $adminCredentials['email'],
            ':mobile'      => $adminCredentials['mobile'],
            ':profile_pic' => $adminCredentials['profile_pic'],
            ':password'    => $hashedPassword,
            ':role'        => $adminCredentials['role'],
            ':status'      => $adminCredentials['status']
        ]);
        $statusMessage = "Table `admins` created and Super Admin successfully imported into database `{$database}`!";
        $statusType = "success";
    }

} catch (PDOException $e) {
    $statusType = "danger";
    $statusMessage = "Database Error: " . $e->getMessage();
}

// Check if running in CLI
if (php_sapi_name() === 'cli') {
    echo "====================================================\n";
    echo "MG Education Admin Importer\n";
    echo "====================================================\n";
    echo "Status: {$statusMessage}\n";
    echo "----------------------------------------------------\n";
    echo "Admin Name:     {$adminCredentials['name']}\n";
    echo "Username:       {$adminCredentials['username']}\n";
    echo "Email:          {$adminCredentials['email']}\n";
    echo "Mobile:         {$adminCredentials['mobile']}\n";
    echo "Password:       {$adminCredentials['password']}\n";
    echo "Role:           {$adminCredentials['role']}\n";
    echo "====================================================\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Admin Table &bull; MG Education</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #071d33 0%, #0b2542 50%, #0f3d68 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 24px 16px;
        }
        .import-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            max-width: 620px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .import-header {
            background: linear-gradient(135deg, #071d33 0%, #0b63b6 100%);
            padding: 26px 28px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3.5px solid #ffb703;
        }
        .header-logo-badge {
            background: #ffffff;
            padding: 6px 12px;
            border-radius: 10px;
            border: 2px solid #ffb703;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .header-logo-badge img {
            max-height: 38px;
            width: auto;
            display: block;
        }
        .import-body {
            padding: 28px;
        }
        .credential-box {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-top: 18px;
        }
        .cred-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 13.5px;
        }
        .cred-row:last-child {
            border-bottom: none;
        }
        .cred-label {
            font-weight: 700;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cred-val {
            font-weight: 800;
            color: #0f172a;
            font-family: 'JetBrains Mono', monospace;
            background: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }
        .cred-password {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
            font-size: 15px;
            letter-spacing: 0.5px;
        }
        .btn-go-login {
            background: linear-gradient(135deg, #0b63b6 0%, #084e96 100%);
            border: none;
            color: #ffffff;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(11, 99, 182, 0.35);
            transition: all 0.22s ease;
            margin-top: 22px;
        }
        .btn-go-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 99, 182, 0.45);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="import-card">
    <div class="import-header">
        <div class="header-logo-badge">
            <img src="../assets/images/logo.jpg" alt="MG Logo">
        </div>
        <div>
            <h5 class="mb-0 fw-bold">Admin Database Setup</h5>
            <small class="text-white-50">MG Education &bull; Production Migration</small>
        </div>
    </div>

    <div class="import-body">
        <div class="alert alert-<?= $statusType ?> d-flex align-items-center gap-3">
            <i class="fas fa-<?= $statusType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> fs-4"></i>
            <div>
                <strong>Database Status:</strong> <?= htmlspecialchars($statusMessage) ?>
            </div>
        </div>

        <h6 class="fw-bold mt-4 mb-2 text-dark">
            <i class="fas fa-shield-alt text-primary me-2"></i> Super Admin Credentials Created:
        </h6>

        <div class="credential-box">
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-user text-primary"></i> Full Name</span>
                <span class="cred-val"><?= htmlspecialchars($adminCredentials['name']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-at text-info"></i> Username</span>
                <span class="cred-val"><?= htmlspecialchars($adminCredentials['username']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-envelope text-warning"></i> Email Address</span>
                <span class="cred-val"><?= htmlspecialchars($adminCredentials['email']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-phone text-success"></i> Mobile Number</span>
                <span class="cred-val"><?= htmlspecialchars($adminCredentials['mobile']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-image text-secondary"></i> Profile Picture</span>
                <span class="cred-val"><?= htmlspecialchars($adminCredentials['profile_pic']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-key text-danger"></i> Password (Raw)</span>
                <span class="cred-val cred-password" id="adminPass"><?= htmlspecialchars($adminCredentials['password']) ?></span>
            </div>
            <div class="cred-row">
                <span class="cred-label"><i class="fas fa-user-shield text-primary"></i> Admin Role</span>
                <span class="badge bg-primary px-3 py-2 rounded-pill"><?= htmlspecialchars($adminCredentials['role']) ?></span>
            </div>
        </div>

        <a href="../admin/login.php" class="btn-go-login">
            <span>Proceed to Admin Login Panel</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

</body>
</html>

