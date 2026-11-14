<?php
/**
 * MG Education & Social Development Organization
 * Study Centers Database Migration & Seeder Script
 * 
 * Sets up the `centers` table with comprehensive schema:
 * - Basic Center Profile & Code
 * - India Post verified Address & Geo Data
 * - Infrastructure (Classrooms, PCs, Staff, Lab Type, Days & Hours, Amenities)
 * - Owner & Contact Information
 * - Royalty Percentage
 * - PDF Verification Documents (Aadhar, PAN, GST)
 * - Photo & Signature Assets
 */

// Load Configuration and Database PDO
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = getDB();
$statusMessage = '';
$statusType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    if (!$pdo) {
        $statusMessage = "Database connection failed! Please check your .env database settings.";
        $statusType = 'danger';
    } else {
        try {
            // 1. Create centers table
            $createTableSql = "CREATE TABLE IF NOT EXISTS `centers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `center_code` VARCHAR(50) NOT NULL UNIQUE,
                `center_name` VARCHAR(255) NOT NULL,
                `center_email` VARCHAR(191) NOT NULL,
                `center_mobile` VARCHAR(20) NOT NULL,
                `pincode` VARCHAR(10) NOT NULL,
                `city` VARCHAR(100) NOT NULL,
                `state` VARCHAR(100) NOT NULL,
                `country` VARCHAR(50) NOT NULL DEFAULT 'India',
                `address` TEXT NOT NULL,
                `classrooms_count` INT NOT NULL DEFAULT 1,
                `computers_count` INT NOT NULL DEFAULT 0,
                `total_staff` INT NOT NULL DEFAULT 0,
                `lab_type` ENUM('Basic', 'Advanced') NOT NULL DEFAULT 'Basic',
                `working_days_from` VARCHAR(30) NOT NULL DEFAULT 'Monday',
                `working_days_to` VARCHAR(30) NOT NULL DEFAULT 'Saturday',
                `working_hours_from` VARCHAR(20) NOT NULL DEFAULT '09:00 AM',
                `working_hours_to` VARCHAR(20) NOT NULL DEFAULT '06:00 PM',
                `amenities` TEXT NULL,
                `owner_name` VARCHAR(150) NOT NULL,
                `owner_email` VARCHAR(191) NOT NULL,
                `owner_mobile` VARCHAR(20) NOT NULL,
                `helpline_number` VARCHAR(30) NULL,
                `royalty_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `aadhar_card_doc` VARCHAR(255) NULL,
                `pan_card_doc` VARCHAR(255) NULL,
                `gst_number` VARCHAR(50) NULL,
                `gst_certificate_doc` VARCHAR(255) NULL,
                `owner_photo` VARCHAR(255) NULL,
                `owner_signature` VARCHAR(255) NULL,
                `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_center_code` (`center_code`),
                INDEX `idx_pincode` (`pincode`),
                INDEX `idx_state` (`state`),
                INDEX `idx_city` (`city`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $pdo->exec($createTableSql);

            // 2. Check if default sample centers exist, insert if empty
            $checkStmt = $pdo->query("SELECT COUNT(*) AS total FROM `centers`");
            $totalCount = (int) $checkStmt->fetchColumn();

            if ($totalCount === 0) {
                $sampleAmenities1 = json_encode([
                    "High-Speed Internet",
                    "Power Backup (Inverter/Generator)",
                    "Dedicated Parking Area",
                    "Air Conditioned Classrooms",
                    "24x7 CCTV Surveillance",
                    "RO Drinking Water",
                    "Student Reference Library"
                ]);

                $sampleAmenities2 = json_encode([
                    "High-Speed Internet",
                    "Power Backup (Inverter/Generator)",
                    "RO Drinking Water",
                    "Clean Restrooms"
                ]);

                $seedSql = "INSERT INTO `centers` (
                    `center_code`, `center_name`, `center_email`, `center_mobile`,
                    `pincode`, `city`, `state`, `country`, `address`,
                    `classrooms_count`, `computers_count`, `total_staff`, `lab_type`,
                    `working_days_from`, `working_days_to`, `working_hours_from`, `working_hours_to`,
                    `amenities`, `owner_name`, `owner_email`, `owner_mobile`, `helpline_number`,
                    `royalty_percentage`, `status`
                ) VALUES 
                (
                    'MG-CENT-1001',
                    'Prayag Institute of Computer & Skill Development',
                    'prayag.cent1001@mgedu.in',
                    '+91 8059982049',
                    '134003',
                    'Ambala',
                    'Haryana',
                    'India',
                    'SCO 45-46, Near City Plaza, Jagadhri Road, Ambala Cantt',
                    4,
                    35,
                    8,
                    'Advanced',
                    'Monday',
                    'Saturday',
                    '08:30 AM',
                    '06:30 PM',
                    :amenities1,
                    'Rahul Dhiman',
                    'rahul@prayagcomputer.in',
                    '+91 8059982049',
                    '1800-889-1001',
                    15.00,
                    'active'
                ),
                (
                    'MG-CENT-1002',
                    'Delhi Central Academy of Technical Studies',
                    'delhi.cent1002@mgedu.in',
                    '+91 9812345678',
                    '110001',
                    'Central Delhi',
                    'Delhi',
                    'India',
                    'Plot 12, 2nd Floor, Baroda House Lane, Connaught Place, New Delhi',
                    3,
                    25,
                    6,
                    'Advanced',
                    'Monday',
                    'Saturday',
                    '09:00 AM',
                    '06:00 PM',
                    :amenities2,
                    'Amitabh Verma',
                    'amitabh.verma@mgedu.in',
                    '+91 9812345678',
                    '011-23344556',
                    12.50,
                    'active'
                )";

                $insertStmt = $pdo->prepare($seedSql);
                $insertStmt->execute([
                    ':amenities1' => $sampleAmenities1,
                    ':amenities2' => $sampleAmenities2
                ]);

                $statusMessage = "Migration Successful! `centers` table created with 2 sample authorized centers populated.";
                $statusType = 'success';
            } else {
                $statusMessage = "Migration verified! The `centers` table already exists with {$totalCount} center records.";
                $statusType = 'info';
            }
        } catch (Throwable $e) {
            $statusMessage = "Migration error: " . $e->getMessage();
            $statusType = 'danger';
        }
    }
}

// Fetch current statistics
$totalCenters = 0;
$activeCenters = 0;
if ($pdo) {
    try {
        $statsStmt = $pdo->query("SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) AS active_count
            FROM `centers`");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        $totalCenters = (int)($stats['total'] ?? 0);
        $activeCenters = (int)($stats['active_count'] ?? 0);
    } catch (Throwable $e) {
        // Table may not exist yet
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Centers Table Migration & Setup | MG Education</title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Mukta:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --mg-navy: #071d33;
            --mg-vermilion: #e34e26;
            --mg-cyan: #00a2db;
            --mg-purple: #724c97;
            --mg-amber: #d67d26;
            --mg-emerald: #10b981;
        }
        body {
            background: linear-gradient(135deg, #f0f4f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            padding: 40px 15px;
        }
        .setup-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(7, 29, 51, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            max-width: 880px;
            margin: 0 auto;
        }
        .setup-header {
            background: linear-gradient(135deg, var(--mg-vermilion) 0%, #c53a15 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        .setup-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .setup-header h2 {
            font-weight: 800;
            margin: 0 0 8px;
            font-size: 1.85rem;
        }
        .setup-body {
            padding: 35px 30px;
        }
        .stat-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
        }
        .stat-badge .stat-num {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--mg-navy);
        }
        .stat-badge .stat-lbl {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .schema-box {
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Courier New', monospace;
            padding: 20px;
            border-radius: 12px;
            font-size: 0.82rem;
            line-height: 1.6;
            max-height: 250px;
            overflow-y: auto;
        }
        .btn-migrate {
            background: linear-gradient(135deg, var(--mg-vermilion) 0%, #c53a15 100%);
            color: #ffffff;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 8px 20px rgba(227, 78, 38, 0.3);
            transition: all 0.25s ease;
        }
        .btn-migrate:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(227, 78, 38, 0.4);
        }
        .btn-panel {
            background: linear-gradient(135deg, var(--mg-navy) 0%, #0b2542 100%);
            color: #ffffff;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(7, 29, 51, 0.25);
            transition: all 0.25s ease;
        }
        .btn-panel:hover {
            color: #ffffff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <div class="setup-header-badge">
            <i class="fas fa-building-columns"></i> Study Centers Network Architecture
        </div>
        <h2>Study Centers Table Migration</h2>
        <p class="mb-0 text-white-50">Sets up comprehensive schema for Authorized Centers, Infrastructure, India Post API integration & Legal PDFs.</p>
    </div>

    <div class="setup-body">
        <?php if (!empty($statusMessage)): ?>
            <div class="alert alert-<?= $statusType ?> d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-<?= $statusType === 'success' ? 'circle-check' : 'triangle-exclamation' ?> fa-lg me-2"></i>
                <div><?= htmlspecialchars($statusMessage) ?></div>
            </div>
        <?php endif; ?>

        <!-- Database Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-badge">
                    <div class="stat-num text-primary"><?= $totalCenters ?></div>
                    <div class="stat-lbl">Total Centers in Database</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-badge">
                    <div class="stat-num text-success"><?= $activeCenters ?></div>
                    <div class="stat-lbl">Active & Verified Centers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-badge">
                    <div class="stat-num text-warning"><i class="fas fa-satellite-dish me-1"></i> Ready</div>
                    <div class="stat-lbl">India Post PIN API Hook</div>
                </div>
            </div>
        </div>

        <!-- Schema Overview -->
        <h6 class="fw-bold mb-2 text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 0.8rem;">
            <i class="fas fa-database me-1 text-danger"></i> Schema Specification: `centers` Table
        </h6>
        <div class="schema-box mb-4">
CREATE TABLE `centers` (<br>
&nbsp;&nbsp;`id` INT AUTO_INCREMENT PRIMARY KEY,<br>
&nbsp;&nbsp;`center_code` VARCHAR(50) UNIQUE, -- Auto or Custom ID (e.g. MG-CENT-1001)<br>
&nbsp;&nbsp;`center_name` VARCHAR(255),<br>
&nbsp;&nbsp;`center_email` VARCHAR(191),<br>
&nbsp;&nbsp;`center_mobile` VARCHAR(20),<br>
&nbsp;&nbsp;`pincode` VARCHAR(10),<br>
&nbsp;&nbsp;`city` VARCHAR(100),<br>
&nbsp;&nbsp;`state` VARCHAR(100),<br>
&nbsp;&nbsp;`country` VARCHAR(50) DEFAULT 'India',<br>
&nbsp;&nbsp;`address` TEXT,<br>
&nbsp;&nbsp;`classrooms_count` INT DEFAULT 1,<br>
&nbsp;&nbsp;`computers_count` INT DEFAULT 0,<br>
&nbsp;&nbsp;`total_staff` INT DEFAULT 0,<br>
&nbsp;&nbsp;`lab_type` ENUM('Basic', 'Advanced'),<br>
&nbsp;&nbsp;`working_days_from` VARCHAR(30),<br>
&nbsp;&nbsp;`working_days_to` VARCHAR(30),<br>
&nbsp;&nbsp;`working_hours_from` VARCHAR(20),<br>
&nbsp;&nbsp;`working_hours_to` VARCHAR(20),<br>
&nbsp;&nbsp;`amenities` TEXT, -- JSON array of amenities<br>
&nbsp;&nbsp;`owner_name` VARCHAR(150),<br>
&nbsp;&nbsp;`owner_email` VARCHAR(191),<br>
&nbsp;&nbsp;`owner_mobile` VARCHAR(20),<br>
&nbsp;&nbsp;`helpline_number` VARCHAR(30),<br>
&nbsp;&nbsp;`royalty_percentage` DECIMAL(5,2),<br>
&nbsp;&nbsp;`aadhar_card_doc` VARCHAR(255), -- PDF only<br>
&nbsp;&nbsp;`pan_card_doc` VARCHAR(255), -- PDF only<br>
&nbsp;&nbsp;`gst_number` VARCHAR(50),<br>
&nbsp;&nbsp;`gst_certificate_doc` VARCHAR(255), -- PDF only<br>
&nbsp;&nbsp;`owner_photo` VARCHAR(255), -- JPG/PNG/WEBP<br>
&nbsp;&nbsp;`owner_signature` VARCHAR(255), -- JPG/PNG/WEBP<br>
&nbsp;&nbsp;`status` ENUM('active', 'inactive', 'pending'),<br>
&nbsp;&nbsp;`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP<br>
);
        </div>

        <!-- Action Form -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2">
            <form method="POST" action="">
                <button type="submit" name="run_migration" class="btn btn-migrate">
                    <i class="fas fa-play-circle me-2"></i> Run / Re-run Migration
                </button>
            </form>
            <div class="d-flex gap-2">
                <a href="../admin/centers/index.php" class="btn btn-panel">
                    <i class="fas fa-list-check"></i> Centers Panel
                </a>
                <a href="../admin/centers/add-center.php" class="btn btn-primary" style="border-radius: 12px; font-weight:700; padding:14px 24px;">
                    <i class="fas fa-plus-circle me-1"></i> Add Center
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
