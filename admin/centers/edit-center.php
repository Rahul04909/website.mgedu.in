<?php
/**
 * MG Education & Social Development Organization
 * Edit & Update Authorized Study Center
 * 
 * Features:
 * - Enterprise Clean & Professional Architecture
 * - Pre-filled fields, document links & image previews
 * - India Post PIN Code Verification with Single Status Feedback
 */

// Include Admin Top Header (Enforces JWT Auth & Navigation)
require_once dirname(__DIR__) . '/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';
require_once dirname(dirname(__DIR__)) . '/includes/mailer.php';

$pdo = getDB();
$centerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$center = null;
$errors = [];
$successMessage = '';

if (!$pdo) {
    die("Database connection failed.");
}

// Auto-upgrade database schema: ensure password column exists
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM `centers` LIKE 'password'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE `centers` ADD COLUMN `password` VARCHAR(255) NULL AFTER `center_mobile`");
    }
} catch (Throwable $e) {}

// Fetch Center Data
try {
    $stmt = $pdo->prepare("SELECT * FROM `centers` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $centerId]);
    $center = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$center) {
        echo "<script>window.location.href = 'index.php?error=notfound';</script>";
        exit;
    }
} catch (Throwable $e) {
    die("Error fetching center: " . $e->getMessage());
}

// Parse existing amenities
$existingAmenities = [];
if (!empty($center['amenities'])) {
    $decoded = json_decode($center['amenities'], true);
    if (is_array($decoded)) {
        $existingAmenities = $decoded;
    }
}

// Handle Form Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_center'])) {
    $centerCode         = trim($_POST['center_code'] ?? $center['center_code']);
    $centerName         = trim($_POST['center_name'] ?? '');
    $centerEmail        = trim($_POST['center_email'] ?? '');
    $centerMobile       = trim($_POST['center_mobile'] ?? '');
    $centerPassword     = trim($_POST['center_password'] ?? '');
    $pincode            = trim($_POST['pincode'] ?? '');
    $city               = trim($_POST['city'] ?? '');
    $state              = trim($_POST['state'] ?? '');
    $country            = 'India';
    $address            = trim($_POST['address'] ?? '');

    $classroomsCount    = (int)($_POST['classrooms_count'] ?? 1);
    $computersCount     = (int)($_POST['computers_count'] ?? 0);
    $totalStaff         = (int)($_POST['total_staff'] ?? 0);
    $labType            = in_array($_POST['lab_type'] ?? '', ['Basic', 'Advanced']) ? $_POST['lab_type'] : 'Basic';
    $workingDaysFrom    = trim($_POST['working_days_from'] ?? 'Monday');
    $workingDaysTo      = trim($_POST['working_days_to'] ?? 'Saturday');
    $workingHoursFrom   = trim($_POST['working_hours_from'] ?? '09:00 AM');
    $workingHoursTo     = trim($_POST['working_hours_to'] ?? '06:00 PM');
    $amenitiesArray     = isset($_POST['amenities']) && is_array($_POST['amenities']) ? $_POST['amenities'] : [];
    $amenitiesJson      = json_encode($amenitiesArray, JSON_UNESCAPED_UNICODE);

    $ownerName          = trim($_POST['owner_name'] ?? '');
    $ownerEmail         = trim($_POST['owner_email'] ?? '');
    $ownerMobile        = trim($_POST['owner_mobile'] ?? '');
    $helplineNumber     = trim($_POST['helpline_number'] ?? '');
    $royaltyPercentage  = (float)($_POST['royalty_percentage'] ?? 0.00);
    $gstNumber          = trim($_POST['gst_number'] ?? '');
    $status             = in_array($_POST['status'] ?? '', ['active', 'inactive', 'pending']) ? $_POST['status'] : 'active';

    if (empty($centerName))   $errors[] = "Center Name is required.";
    if (empty($centerEmail))  $errors[] = "Center Email is required.";
    if (empty($centerMobile)) $errors[] = "Center Mobile Number is required.";
    if (!empty($centerPassword) && strlen($centerPassword) < 6) {
        $errors[] = "New Center Password must be at least 6 characters.";
    }
    if (empty($pincode))      $errors[] = "Postal PIN Code is required.";
    if (empty($city))         $errors[] = "City / District is required.";
    if (empty($state))        $errors[] = "State is required.";
    if (empty($address))      $errors[] = "Complete Center Address is required.";
    if (empty($ownerName))    $errors[] = "Owner Name is required.";
    if (empty($ownerEmail))   $errors[] = "Owner Email is required.";
    if (empty($ownerMobile))  $errors[] = "Owner Mobile Number is required.";

    $baseUploadDir = dirname(dirname(__DIR__)) . '/uploads/centers';
    $docsDir       = $baseUploadDir . '/documents';
    $photosDir     = $baseUploadDir . '/photos';
    $sigsDir       = $baseUploadDir . '/signatures';

    $handlePdfUpload = function ($fileKey, $prefix, $currentPath) use ($docsDir, &$errors) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return $currentPath;
        }
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$fileKey}. Code: " . $file['error'];
            return $currentPath;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'])) {
            $errors[] = "Document '{$fileKey}' MUST be a PDF file.";
            return $currentPath;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "Document '{$fileKey}' exceeds 10MB limit.";
            return $currentPath;
        }

        $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $destination = $docsDir . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/centers/documents/' . $newFileName;
        } else {
            $errors[] = "Failed to save document {$fileKey}.";
            return $currentPath;
        }
    };

    $handleImageUpload = function ($fileKey, $targetDir, $relPathPrefix, $prefix, $currentPath) use (&$errors) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return $currentPath;
        }
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$fileKey}. Code: " . $file['error'];
            return $currentPath;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            $errors[] = "Image '{$fileKey}' must be JPG, PNG, or WEBP.";
            return $currentPath;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image '{$fileKey}' exceeds 5MB limit.";
            return $currentPath;
        }

        $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $targetDir . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $relPathPrefix . '/' . $newFileName;
        } else {
            $errors[] = "Failed to save image {$fileKey}.";
            return $currentPath;
        }
    };

    $aadharDocPath = $handlePdfUpload('aadhar_card_doc', 'aadhar', $center['aadhar_card_doc']);
    $panDocPath    = $handlePdfUpload('pan_card_doc', 'pan', $center['pan_card_doc']);
    $gstDocPath    = $handlePdfUpload('gst_certificate_doc', 'gst', $center['gst_certificate_doc']);

    $ownerPhotoPath     = $handleImageUpload('owner_photo', $photosDir, 'uploads/centers/photos', 'owner_photo', $center['owner_photo']);
    $ownerSignaturePath = $handleImageUpload('owner_signature', $sigsDir, 'uploads/centers/signatures', 'owner_sig', $center['owner_signature']);

    if (empty($errors)) {
        try {
            $passwordClause = "";
            $params = [
                ':center_code'         => $centerCode,
                ':center_name'         => $centerName,
                ':center_email'        => $centerEmail,
                ':center_mobile'       => $centerMobile,
                ':pincode'             => $pincode,
                ':city'                => $city,
                ':state'               => $state,
                ':address'             => $address,
                ':classrooms_count'    => $classroomsCount,
                ':computers_count'     => $computersCount,
                ':total_staff'         => $totalStaff,
                ':lab_type'            => $labType,
                ':working_days_from'   => $workingDaysFrom,
                ':working_days_to'     => $workingDaysTo,
                ':working_hours_from'  => $workingHoursFrom,
                ':working_hours_to'    => $workingHoursTo,
                ':amenities'           => $amenitiesJson,
                ':owner_name'          => $ownerName,
                ':owner_email'         => $ownerEmail,
                ':owner_mobile'        => $ownerMobile,
                ':helpline_number'     => $helplineNumber,
                ':royalty_percentage'  => $royaltyPercentage,
                ':aadhar_card_doc'     => $aadharDocPath,
                ':pan_card_doc'        => $panDocPath,
                ':gst_number'          => $gstNumber,
                ':gst_certificate_doc' => $gstDocPath,
                ':owner_photo'         => $ownerPhotoPath,
                ':owner_signature'     => $ownerSignaturePath,
                ':status'              => $status,
                ':id'                  => $centerId
            ];

            if (!empty($centerPassword)) {
                $passwordClause = "`password` = :password,";
                $params[':password'] = password_hash($centerPassword, PASSWORD_BCRYPT);
            }

            $updateSql = "UPDATE `centers` SET 
                `center_code` = :center_code,
                `center_name` = :center_name,
                `center_email` = :center_email,
                `center_mobile` = :center_mobile,
                {$passwordClause}
                `pincode` = :pincode,
                `city` = :city,
                `state` = :state,
                `address` = :address,
                `classrooms_count` = :classrooms_count,
                `computers_count` = :computers_count,
                `total_staff` = :total_staff,
                `lab_type` = :lab_type,
                `working_days_from` = :working_days_from,
                `working_days_to` = :working_days_to,
                `working_hours_from` = :working_hours_from,
                `working_hours_to` = :working_hours_to,
                `amenities` = :amenities,
                `owner_name` = :owner_name,
                `owner_email` = :owner_email,
                `owner_mobile` = :owner_mobile,
                `helpline_number` = :helpline_number,
                `royalty_percentage` = :royalty_percentage,
                `aadhar_card_doc` = :aadhar_card_doc,
                `pan_card_doc` = :pan_card_doc,
                `gst_number` = :gst_number,
                `gst_certificate_doc` = :gst_certificate_doc,
                `owner_photo` = :owner_photo,
                `owner_signature` = :owner_signature,
                `status` = :status
                WHERE `id` = :id";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($params);

            // Re-fetch updated center record
            $stmt = $pdo->prepare("SELECT * FROM `centers` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $centerId]);
            $center = $stmt->fetch(PDO::FETCH_ASSOC);
            $existingAmenities = $amenitiesArray;

            // Dispatch update notification email
            $mailResult = sendCenterUpdateEmail($center, !empty($centerPassword) ? $centerPassword : null);
            $mailNote = $mailResult['success'] 
                ? " (Notification sent to registered email)" 
                : " (Mail notice: " . htmlspecialchars($mailResult['error'] ?? '') . ")";

            $successMessage = "Study Center '{$centerName}' [{$centerCode}] updated successfully!" . $mailNote;
        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Top Action & Navigation Row -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pt-1">
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 rounded-pill px-3 py-1 fw-semibold">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Centers</span>
            </a>
            <span class="text-muted d-none d-sm-inline">|</span>
            <span class="fw-bold text-dark fs-6 d-none d-sm-inline">Edit Center: <?= htmlspecialchars($center['center_name']) ?></span>
            <span class="mg-code-badge ms-1"><?= htmlspecialchars($center['center_code']) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                Status: <strong class="text-capitalize text-<?= $center['status'] === 'active' ? 'success' : ($center['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= htmlspecialchars($center['status']) ?></strong>
            </span>
            <a href="add-center.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold">
                <i class="fas fa-plus me-1"></i> Add New
            </a>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="fas fa-circle-exclamation me-2"></i>
                <strong>Please correct the following errors:</strong>
            </div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Form Started -->
    <form method="POST" action="" enctype="multipart/form-data" id="mgEditCenterForm" autocomplete="off">
        <input type="hidden" name="action_update_center" value="1">

        <!-- 1. Center Identity & Contact -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon blue">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Center Identity &amp; Contact Details</h6>
                        <span class="mg-section-sub">Official center profile and communications</span>
                    </div>
                </div>
                <span class="mg-section-tag">Identity</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center ID / Code <span class="mg-required">*</span></label>
                            <input type="text" name="center_code" class="form-control mg-input-control font-monospace fw-bold" 
                                value="<?= htmlspecialchars($center['center_code']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center / Institute Name <span class="mg-required">*</span></label>
                            <input type="text" name="center_name" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['center_name']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center Official Email <span class="mg-required">*</span></label>
                            <input type="email" name="center_email" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['center_email']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center Mobile Number <span class="mg-required">*</span></label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix">+91</span>
                                <input type="tel" name="center_mobile" class="form-control mg-input-control" 
                                    maxlength="15"
                                    value="<?= htmlspecialchars(str_replace('+91 ', '', $center['center_mobile'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Portal Password <span class="text-muted">(Leave empty to keep)</span></label>
                            <div class="input-group">
                                <input type="password" name="center_password" id="center_password_input" 
                                    class="form-control mg-input-control" 
                                    placeholder="Enter new password">
                                <button type="button" class="btn btn-outline-secondary" id="btn_toggle_pwd" title="Show / Hide Password" style="border-color:#cbd5e1;">
                                    <i class="far fa-eye" id="eye_icon"></i>
                                </button>
                                <button type="button" class="btn btn-light border" id="btn_gen_pwd" title="Generate Strong Password" style="border-color:#cbd5e1; font-size:0.75rem; font-weight:600;">
                                    <i class="fas fa-key me-1 text-warning"></i> Auto
                                </button>
                            </div>
                            <small class="text-muted" style="font-size:0.73rem;">Only enter if you wish to reset password.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Address & Geolocation (India Post API) -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon green">
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Address &amp; Location (India Post Verified)</h6>
                        <span class="mg-section-sub">Postal PIN Code validation and geolocation</span>
                    </div>
                </div>
                <span class="mg-section-tag">India Post API</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Postal PIN Code <span class="mg-required">*</span></label>
                            <div class="input-group">
                                <input type="text" name="pincode" id="mg_pincode_input" 
                                    class="form-control mg-input-control fw-bold" 
                                    maxlength="6" pattern="\d{6}"
                                    value="<?= htmlspecialchars($center['pincode']) ?>" required>
                                <button type="button" class="btn btn-outline-secondary" id="btn_verify_pin" style="border-color:#cbd5e1; font-size:0.82rem; font-weight:600;">
                                    <i class="fas fa-magnifying-glass me-1"></i> Verify
                                </button>
                            </div>
                            <div id="pin_status_container" class="mt-1"></div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">City / District <span class="mg-required">*</span></label>
                            <input type="text" name="city" id="mg_city_input" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['city']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-2 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">State <span class="mg-required">*</span></label>
                            <input type="text" name="state" id="mg_state_input" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['state']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-2 col-12">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Country <span class="text-muted">(Default)</span></label>
                            <input type="text" name="country" class="form-control mg-input-control mg-input-readonly" 
                                value="India" readonly tabindex="-1">
                        </div>
                    </div>

                    <div class="col-12" id="postoffice_select_wrap" style="display: none;">
                        <div class="p-2 rounded-2 bg-light border mb-2 d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted small fw-semibold"><i class="fas fa-envelope-open-text me-1 text-primary"></i> Post Office Locality:</span>
                            <select id="mg_postoffice_select" class="form-select form-select-sm d-inline-block w-auto" style="min-width: 250px;">
                                <option value="">Select post office branch to append...</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">Complete Center Address <span class="mg-required">*</span></label>
                            <textarea name="address" id="mg_address_textarea" class="form-control mg-input-control" rows="2" required><?= htmlspecialchars($center['address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Infrastructure & Facilities -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon purple">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Infrastructure &amp; Operational Setup</h6>
                        <span class="mg-section-sub">Classroom setup, IT lab capacity and amenities</span>
                    </div>
                </div>
                <span class="mg-section-tag">Facilities</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Classrooms <span class="mg-required">*</span></label>
                            <input type="number" name="classrooms_count" class="form-control mg-input-control" min="1" max="100" 
                                value="<?= (int)$center['classrooms_count'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Computers <span class="mg-required">*</span></label>
                            <input type="number" name="computers_count" class="form-control mg-input-control" min="0" max="500" 
                                value="<?= (int)$center['computers_count'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Total Staff <span class="mg-required">*</span></label>
                            <input type="number" name="total_staff" class="form-control mg-input-control" min="0" max="100" 
                                value="<?= (int)$center['total_staff'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Lab Type <span class="mg-required">*</span></label>
                            <select name="lab_type" class="form-select mg-input-control">
                                <option value="Basic" <?= ($center['lab_type'] === 'Basic') ? 'selected' : '' ?>>Basic (Standard PC Lab)</option>
                                <option value="Advanced" <?= ($center['lab_type'] === 'Advanced') ? 'selected' : '' ?>>Advanced (High-End Lab)</option>
                            </select>
                        </div>
                    </div>

                    <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; ?>
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Days: From</label>
                            <select name="working_days_from" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= ($center['working_days_from'] === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Days: To</label>
                            <select name="working_days_to" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= ($center['working_days_to'] === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Hours: From</label>
                            <input type="text" name="working_hours_from" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['working_hours_from']) ?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Hours: To</label>
                            <input type="text" name="working_hours_to" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['working_hours_to']) ?>">
                        </div>
                    </div>

                    <!-- Amenities Pills -->
                    <div class="col-12 mt-1">
                        <label class="mg-form-label mb-2">Available Amenities &amp; Services</label>
                        <div class="mg-amenities-wrap">
                            <?php
                            $amenitiesList = [
                                "High-Speed Internet",
                                "Power Backup (Inverter/Generator)",
                                "Dedicated Parking Area",
                                "Student Reference Library",
                                "24x7 CCTV Surveillance",
                                "Air Conditioned Classrooms",
                                "RO Pure Drinking Water",
                                "Clean Restrooms (M/F)",
                                "Smart TV / Interactive Projector",
                                "Fire Safety & Extinguisher"
                            ];
                            ?>
                            <?php foreach ($amenitiesList as $amenity): 
                                $isChecked = in_array($amenity, $existingAmenities);
                            ?>
                                <label class="mg-amenity-pill <?= $isChecked ? 'active' : '' ?>">
                                    <input type="checkbox" name="amenities[]" value="<?= htmlspecialchars($amenity) ?>" <?= $isChecked ? 'checked' : '' ?> onchange="togglePillActive(this)">
                                    <i class="fas fa-check check-icon"></i>
                                    <span><?= htmlspecialchars($amenity) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Owner & Commercials -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon amber">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Owner Details &amp; Commercial Terms</h6>
                        <span class="mg-section-sub">Center head, helpline number and franchise royalty percentage</span>
                    </div>
                </div>
                <span class="mg-section-tag">Commercial</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center Head / Owner Name <span class="mg-required">*</span></label>
                            <input type="text" name="owner_name" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['owner_name']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Owner Email Address <span class="mg-required">*</span></label>
                            <input type="email" name="owner_email" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['owner_email']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Owner Direct Mobile <span class="mg-required">*</span></label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix">+91</span>
                                <input type="tel" name="owner_mobile" class="form-control mg-input-control" 
                                    maxlength="15"
                                    value="<?= htmlspecialchars(str_replace('+91 ', '', $center['owner_mobile'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Student Helpline / Enquiry No.</label>
                            <input type="text" name="helpline_number" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['helpline_number'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center Royalty Fee (%) <span class="mg-required">*</span></label>
                            <div class="mg-input-group has-suffix">
                                <input type="number" step="0.01" min="0" max="100" name="royalty_percentage" 
                                    class="form-control mg-input-control fw-bold" 
                                    value="<?= htmlspecialchars($center['royalty_percentage']) ?>" required>
                                <span class="mg-input-suffix">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Verification Documents (PDF Only) -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon coral">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Verification Documents (PDF Format Only)</h6>
                        <span class="mg-section-sub">Inspect or replace current verification files</span>
                    </div>
                </div>
                <span class="mg-section-tag">PDF Documents</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <!-- Owner Aadhar PDF -->
                    <div class="col-md-4">
                        <div class="mg-doc-upload-card">
                            <div>
                                <div class="mg-doc-upload-top">
                                    <div class="mg-doc-icon-badge pdf"><i class="fas fa-file-pdf"></i></div>
                                    <div class="mg-doc-upload-info">
                                        <h6>Owner Aadhar Card</h6>
                                        <span>PDF format only</span>
                                    </div>
                                </div>
                                <?php if (!empty($center['aadhar_card_doc'])): ?>
                                    <div class="mb-2">
                                        <a href="../../<?= htmlspecialchars($center['aadhar_card_doc']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none p-2 d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-file-pdf text-danger"></i> View Current Aadhar PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="mg-file-status-line" id="status_aadhar">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="aadhar_card_doc" id="file_aadhar" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_aadhar')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_aadhar').click()">
                                    <i class="fas fa-upload"></i> <?= !empty($center['aadhar_card_doc']) ? 'Replace PDF' : 'Upload PDF' ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Owner PAN PDF -->
                    <div class="col-md-4">
                        <div class="mg-doc-upload-card">
                            <div>
                                <div class="mg-doc-upload-top">
                                    <div class="mg-doc-icon-badge pdf"><i class="fas fa-file-lines"></i></div>
                                    <div class="mg-doc-upload-info">
                                        <h6>Owner PAN Card</h6>
                                        <span>PDF format only</span>
                                    </div>
                                </div>
                                <?php if (!empty($center['pan_card_doc'])): ?>
                                    <div class="mb-2">
                                        <a href="../../<?= htmlspecialchars($center['pan_card_doc']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none p-2 d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-file-pdf text-danger"></i> View Current PAN PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="mg-file-status-line" id="status_pan">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="pan_card_doc" id="file_pan" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_pan')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_pan').click()">
                                    <i class="fas fa-upload"></i> <?= !empty($center['pan_card_doc']) ? 'Replace PDF' : 'Upload PDF' ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GST Number & PDF -->
                    <div class="col-md-4">
                        <div class="mg-doc-upload-card">
                            <div>
                                <div class="mg-doc-upload-top">
                                    <div class="mg-doc-icon-badge pdf"><i class="fas fa-receipt"></i></div>
                                    <div class="mg-doc-upload-info">
                                        <h6>GST Registration</h6>
                                        <span>Optional business filing</span>
                                    </div>
                                </div>
                                <input type="text" name="gst_number" class="form-control mg-input-control mb-2 text-uppercase" 
                                    style="height:36px; font-size:0.8rem;"
                                    placeholder="GSTIN (Optional)"
                                    value="<?= htmlspecialchars($center['gst_number'] ?? '') ?>">
                                <?php if (!empty($center['gst_certificate_doc'])): ?>
                                    <div class="mb-2">
                                        <a href="../../<?= htmlspecialchars($center['gst_certificate_doc']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none p-2 d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-file-pdf text-danger"></i> View Current GST PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="mg-file-status-line" id="status_gst">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="gst_certificate_doc" id="file_gst" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_gst')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_gst').click()">
                                    <i class="fas fa-upload"></i> <?= !empty($center['gst_certificate_doc']) ? 'Replace PDF' : 'Upload PDF' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Owner Photo & Signature Images -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon navy">
                        <i class="fas fa-signature"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Owner Photo &amp; Specimen Signature</h6>
                        <span class="mg-section-sub">Current portrait and specimen signature</span>
                    </div>
                </div>
                <span class="mg-section-tag">Media Assets</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <!-- Owner Photo -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-2 border bg-light d-flex align-items-center gap-3">
                            <?php if (!empty($center['owner_photo'])): ?>
                                <img src="../../<?= htmlspecialchars($center['owner_photo']) ?>" id="preview_photo_img" class="mg-img-preview-frame" style="display:block;" alt="Owner Photo">
                            <?php else: ?>
                                <img src="" id="preview_photo_img" class="mg-img-preview-frame" alt="Owner Photo">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark" style="font-size:0.88rem;">Owner Passport Photo</div>
                                <div class="text-muted small mb-2" style="font-size:0.75rem;">JPG, PNG, or WEBP</div>
                                <input type="file" name="owner_photo" id="file_photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleImgSelected(this, 'preview_photo_img')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_photo').click()">
                                    <i class="fas fa-camera me-1"></i> Change Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Signature -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-2 border bg-light d-flex align-items-center gap-3">
                            <?php if (!empty($center['owner_signature'])): ?>
                                <img src="../../<?= htmlspecialchars($center['owner_signature']) ?>" id="preview_sig_img" class="mg-img-preview-frame" style="display:block; background:#fff; padding:4px;" alt="Signature">
                            <?php else: ?>
                                <img src="" id="preview_sig_img" class="mg-img-preview-frame" alt="Signature">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark" style="font-size:0.88rem;">Specimen Signature</div>
                                <div class="text-muted small mb-2" style="font-size:0.75rem;">Clean background</div>
                                <input type="file" name="owner_signature" id="file_sig" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleImgSelected(this, 'preview_sig_img')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_sig').click()">
                                    <i class="fas fa-pen-nib me-1"></i> Change Signature
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 pt-3 border-top align-items-center">
                    <div class="col-md-4">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">Current Franchise Status</label>
                            <select name="status" class="form-select mg-input-control">
                                <option value="active" <?= ($center['status'] === 'active') ? 'selected' : '' ?>>Active &bull; Fully Authorized</option>
                                <option value="pending" <?= ($center['status'] === 'pending') ? 'selected' : '' ?>>Pending Verification</option>
                                <option value="inactive" <?= ($center['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer -->
        <div class="mg-form-footer-bar">
            <div class="text-muted small">
                <i class="fas fa-clock-rotate-left me-1"></i> Last updated: <?= date('d M Y, h:i A', strtotime($center['updated_at'])) ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-light border px-3" style="font-size:0.88rem; font-weight:600;">Cancel</a>
                <button type="submit" class="mg-btn-save-main">
                    <i class="fas fa-check-double me-1"></i> Update &amp; Save Changes
                </button>
            </div>
        </div>

    </form>
</div>

<!-- India Post API & Interactive Handlers Script -->
<script>
$(document).ready(function() {
    let pinTimer = null;

    $('#mg_pincode_input').on('input keyup paste', function() {
        const pin = $(this).val().trim();
        if (pin.length === 6 && /^\d{6}$/.test(pin)) {
            clearTimeout(pinTimer);
            pinTimer = setTimeout(function() {
                lookupIndiaPost(pin);
            }, 300);
        } else if (pin.length === 0) {
            $('#pin_status_container').empty();
            $('#postoffice_select_wrap').hide();
        }
    });

    $('#btn_verify_pin').on('click', function(e) {
        e.preventDefault();
        const pin = $('#mg_pincode_input').val().trim();
        if (pin.length === 6 && /^\d{6}$/.test(pin)) {
            lookupIndiaPost(pin);
        } else {
            $('#pin_status_container').html('<span class="mg-pin-status-pill error"><i class="fas fa-triangle-exclamation"></i> Please enter a 6-digit PIN.</span>');
        }
    });

    function lookupIndiaPost(pin) {
        const statusBox = $('#pin_status_container');
        statusBox.html('<span class="mg-pin-status-pill loading"><i class="fas fa-spinner fa-spin"></i> Checking India Post...</span>');

        $.ajax({
            url: 'https://api.postalpincode.in/pincode/' + pin,
            type: 'GET',
            dataType: 'json',
            timeout: 6000,
            success: function(resp) {
                if (resp && resp[0] && resp[0].Status === 'Success' && resp[0].PostOffice && resp[0].PostOffice.length > 0) {
                    const postOffices = resp[0].PostOffice;
                    const primary = postOffices[0];

                    const city = primary.District || primary.Region || primary.Circle;
                    const state = primary.State;

                    $('#mg_city_input').val(city);
                    $('#mg_state_input').val(state);

                    statusBox.html('<span class="mg-pin-status-pill success"><i class="fas fa-check-circle"></i> India Post Verified: <strong>' + city + ', ' + state + '</strong> (' + postOffices.length + ' post offices)</span>');

                    const sel = $('#mg_postoffice_select');
                    sel.empty();
                    sel.append('<option value="">-- Select Post Office to append --</option>');
                    postOffices.forEach(function(po) {
                        sel.append('<option value="' + po.Name + '">' + po.Name + ' (' + po.BranchType + ')</option>');
                    });
                    $('#postoffice_select_wrap').slideDown();

                    sel.off('change').on('change', function() {
                        const poName = $(this).val();
                        if (poName) {
                            const cur = $('#mg_address_textarea').val().trim();
                            if (cur.indexOf(poName) === -1) {
                                $('#mg_address_textarea').val(cur ? (cur + ', Near ' + poName + ' Post Office') : ('Near ' + poName + ' Post Office'));
                            }
                        }
                    });
                } else {
                    statusBox.html('<span class="mg-pin-status-pill error"><i class="fas fa-circle-xmark"></i> No records found for ' + pin + '.</span>');
                }
            },
            error: function() {
                statusBox.html('<span class="mg-pin-status-pill error"><i class="fas fa-wifi"></i> India Post API unreachable.</span>');
            }
        });
    }
});

function togglePillActive(checkbox) {
    const pill = $(checkbox).closest('.mg-amenity-pill');
    if (checkbox.checked) {
        pill.addClass('active');
    } else {
        pill.removeClass('active');
    }
}

function handleDocSelected(input, statusElementId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.name.toLowerCase().endsWith('.pdf')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Format',
                text: 'Only PDF format is accepted for legal verification documents.',
                confirmButtonColor: '#071d33'
            });
            input.value = '';
            $('#' + statusElementId).hide();
            return;
        }

        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        $('#' + statusElementId).find('.file-name').text(file.name + ' (' + sizeMb + ' MB)');
        $('#' + statusElementId).css('display', 'flex');
    }
}

function handleImgSelected(input, imgElementId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#' + imgElementId).attr('src', e.target.result).show();
        };
        reader.readAsDataURL(file);
    }
}
// Toggle password visibility
$('#btn_toggle_pwd').on('click', function() {
    const input = $('#center_password_input');
    const icon = $('#eye_icon');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Auto-generate secure password
$('#btn_gen_pwd').on('click', function() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*';
    let pwd = 'MG@';
    for (let i = 0; i < 5; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    pwd += '26!';
    $('#center_password_input').val(pwd).attr('type', 'text');
    $('#eye_icon').removeClass('fa-eye').addClass('fa-eye-slash');
});
</script>

<?php if (!empty($successMessage)): ?>
<script>
Swal.fire({
    title: 'Center Updated!',
    text: <?= json_encode($successMessage) ?>,
    icon: 'success',
    confirmButtonColor: '#071d33',
    confirmButtonText: 'Great!'
});
</script>
<?php endif; ?>

<?php 
// Include Admin Footer
require_once dirname(__DIR__) . '/footer.php'; 
?>
