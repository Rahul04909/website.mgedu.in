<?php
/**
 * MG Education & Social Development Organization
 * Edit & Update Authorized Study Center
 * 
 * Features:
 * - Pre-filled data across all sections
 * - View / Download links for current PDF documents (Aadhar, PAN, GST)
 * - Image thumbnails for current Owner Photo & Signature with live replacement preview
 * - India Post PIN Code API Integration for location updates
 * - Full validation & secure updates via PDO
 */

// Include Admin Header (Enforces JWT Auth & Navigation)
require_once dirname(__DIR__) . '/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';

$pdo = getDB();
$centerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$center = null;
$errors = [];
$successMessage = '';

if (!$pdo) {
    die("Database connection failed.");
}

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

    // Validation
    if (empty($centerName))   $errors[] = "Center Name is required.";
    if (empty($centerEmail))  $errors[] = "Center Email is required.";
    if (empty($centerMobile)) $errors[] = "Center Mobile Number is required.";
    if (empty($pincode))      $errors[] = "Postal PIN Code is required.";
    if (empty($city))         $errors[] = "City / District is required.";
    if (empty($state))        $errors[] = "State is required.";
    if (empty($address))      $errors[] = "Complete Center Address is required.";
    if (empty($ownerName))    $errors[] = "Owner Name is required.";
    if (empty($ownerEmail))   $errors[] = "Owner Email is required.";
    if (empty($ownerMobile))  $errors[] = "Owner Mobile Number is required.";

    // File handling
    $baseUploadDir = dirname(dirname(__DIR__)) . '/uploads/centers';
    $docsDir       = $baseUploadDir . '/documents';
    $photosDir     = $baseUploadDir . '/photos';
    $sigsDir       = $baseUploadDir . '/signatures';

    // Helper for uploading PDF documents (keeps existing if not re-uploaded)
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
            $errors[] = "Document '{$fileKey}' MUST be in PDF format only. (Uploaded: .{$ext})";
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

    // Helper for uploading images
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
            $updateSql = "UPDATE `centers` SET 
                `center_code` = :center_code,
                `center_name` = :center_name,
                `center_email` = :center_email,
                `center_mobile` = :center_mobile,
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
            $updateStmt->execute([
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
            ]);

            $successMessage = "Study Center '{$centerName}' [{$centerCode}] updated successfully!";

            // Refresh center record
            $stmt = $pdo->prepare("SELECT * FROM `centers` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $centerId]);
            $center = $stmt->fetch(PDO::FETCH_ASSOC);
            $existingAmenities = $amenitiesArray;
        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Page Header & Action Bar -->
    <div class="mg-page-header">
        <div class="mg-page-header-title">
            <div class="mg-page-header-icon" style="background: linear-gradient(135deg, var(--mg-amber) 0%, var(--mg-amber-dark) 100%);">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div>
                <h4>Edit Study Center: <?= htmlspecialchars($center['center_name']) ?></h4>
                <p>Center Code: <span class="mg-code-pill"><?= htmlspecialchars($center['center_code']) ?></span> &bull; Status: <span class="badge bg-<?= $center['status'] === 'active' ? 'success' : ($center['status'] === 'pending' ? 'warning' : 'secondary') ?> text-capitalize"><?= htmlspecialchars($center['status']) ?></span></p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="mg-btn-action mg-btn-navy">
                <i class="fas fa-arrow-left"></i>
                <span>Centers List</span>
            </a>
            <a href="add-center.php" class="mg-btn-action mg-btn-vermilion">
                <i class="fas fa-plus"></i>
                <span>Add Another</span>
            </a>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-circle-exclamation fa-lg me-2 text-danger"></i>
                <h6 class="mb-0 fw-bold">Please correct the following errors:</h6>
            </div>
            <ul class="mb-0 ps-4">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Center Update Form Started -->
    <form method="POST" action="" enctype="multipart/form-data" id="mgEditCenterForm" autocomplete="off">
        <input type="hidden" name="action_update_center" value="1">

        <!-- SECTION 1: CENTER IDENTITY -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-vermilion-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 1: Center Identity &amp; Contact</h5>
                        <p class="mg-chevron-sub">Official center name, franchise code and communications</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 1</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-barcode"></i></span>
                                Center Code / ID <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="center_code" class="form-control mg-input-control text-uppercase fw-bold" 
                                value="<?= htmlspecialchars($center['center_code']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-school"></i></span>
                                Center / Institute Name <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="center_name" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['center_name']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-envelope"></i></span>
                                Center Official Email <span class="mg-required">*</span>
                            </label>
                            <input type="email" name="center_email" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['center_email']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-phone"></i></span>
                                Center Mobile Number <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-flag me-1 text-danger"></i> +91</span>
                                <input type="tel" name="center_mobile" class="form-control mg-input-control" 
                                    maxlength="15"
                                    value="<?= htmlspecialchars(str_replace('+91 ', '', $center['center_mobile'])) ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: LOCATION & INDIA POST API -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-cyan-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 2: Address &amp; Location (India Post API)</h5>
                        <p class="mg-chevron-sub">Postal PIN Code validation and geolocation details</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 2</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-map-pin"></i></span>
                                Postal PIN Code <span class="mg-required">*</span>
                            </label>
                            <div class="mg-pincode-wrap">
                                <input type="text" name="pincode" id="mg_pincode_input" 
                                    class="form-control mg-input-control fw-bold" 
                                    maxlength="6" pattern="\d{6}"
                                    value="<?= htmlspecialchars($center['pincode']) ?>" required>
                                <div class="mg-pincode-status loading" id="pincode_loader">
                                    <i class="fas fa-spinner fa-spin"></i> Fetching...
                                </div>
                                <div class="mg-pincode-status success" id="pincode_success">
                                    <i class="fas fa-circle-check"></i> Verified
                                </div>
                                <div class="mg-pincode-status error" id="pincode_error">
                                    <i class="fas fa-circle-xmark"></i> Invalid
                                </div>
                            </div>
                            <div id="pincode_help_text" class="form-text text-muted">
                                PIN code connects to <strong>api.postalpincode.in</strong> for verified City &amp; State.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-city"></i></span>
                                City / District <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="city" id="mg_city_input" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['city']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-landmark-dome"></i></span>
                                State <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="state" id="mg_state_input" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['state']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-globe-asia"></i></span>
                                Country <span class="text-muted">(Locked)</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-lock text-muted"></i></span>
                                <input type="text" name="country" class="form-control mg-input-control mg-readonly-badge" 
                                    value="India" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8" id="postoffice_select_wrap" style="display: none;">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-envelope-open-text"></i></span>
                                Post Office Locality Suggestion
                            </label>
                            <select id="mg_postoffice_select" class="form-select mg-input-control">
                                <option value="">Select post office branch...</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-location-crosshairs"></i></span>
                                Complete Address Of Center <span class="mg-required">*</span>
                            </label>
                            <textarea name="address" id="mg_address_textarea" class="form-control mg-input-control" rows="3" required><?= htmlspecialchars($center['address']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: INFRASTRUCTURE & AMENITIES -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-purple-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 3: Infrastructure, Lab &amp; Facilities</h5>
                        <p class="mg-chevron-sub">Classroom setup, computers, lab type and operational hours</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 3</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-chalkboard-user"></i></span>
                                Classrooms <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="classrooms_count" class="form-control mg-input-control" min="1" max="100" 
                                value="<?= (int)$center['classrooms_count'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-desktop"></i></span>
                                Computers <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="computers_count" class="form-control mg-input-control" min="0" max="500" 
                                value="<?= (int)$center['computers_count'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-users-gear"></i></span>
                                Total Staff <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="total_staff" class="form-control mg-input-control" min="0" max="100" 
                                value="<?= (int)$center['total_staff'] ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-microchip"></i></span>
                                Lab Type <span class="mg-required">*</span>
                            </label>
                            <select name="lab_type" class="form-select mg-input-control">
                                <option value="Basic" <?= ($center['lab_type'] === 'Basic') ? 'selected' : '' ?>>Basic (Standard PC Lab)</option>
                                <option value="Advanced" <?= ($center['lab_type'] === 'Advanced') ? 'selected' : '' ?>>Advanced (High-End / Programming)</option>
                            </select>
                        </div>
                    </div>

                    <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; ?>
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="far fa-calendar-check"></i></span>
                                Working Day: From
                            </label>
                            <select name="working_days_from" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= ($center['working_days_from'] === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="far fa-calendar-xmark"></i></span>
                                Working Day: To
                            </label>
                            <select name="working_days_to" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= ($center['working_days_to'] === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="far fa-clock"></i></span>
                                Hours: From
                            </label>
                            <input type="text" name="working_hours_from" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['working_hours_from']) ?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-hourglass-end"></i></span>
                                Hours: To
                            </label>
                            <input type="text" name="working_hours_to" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['working_hours_to']) ?>">
                        </div>
                    </div>

                    <!-- Amenities Checklist -->
                    <div class="col-12 mt-2">
                        <label class="mg-form-label mb-1">
                            <span class="mg-label-icon icon-purple"><i class="fas fa-sparkles"></i></span>
                            Amenities &amp; Facilities Available
                        </label>
                        <div class="mg-amenities-grid">
                            <?php
                            $availableAmenities = [
                                ["name" => "High-Speed Internet",             "icon" => "fas fa-wifi"],
                                ["name" => "Power Backup (Inverter/Gen)",      "icon" => "fas fa-bolt"],
                                ["name" => "Dedicated Parking Area",          "icon" => "fas fa-square-parking"],
                                ["name" => "Student Reference Library",        "icon" => "fas fa-book-open-reader"],
                                ["name" => "24x7 CCTV Surveillance",          "icon" => "fas fa-video"],
                                ["name" => "Air Conditioned Classrooms",      "icon" => "fas fa-snowflake"],
                                ["name" => "RO Pure Drinking Water",          "icon" => "fas fa-glass-water"],
                                ["name" => "Clean Restrooms (M/F)",           "icon" => "fas fa-restroom"],
                                ["name" => "Smart TV / Interactive Projector", "icon" => "fas fa-tv"],
                                ["name" => "Fire Safety & Extinguisher",      "icon" => "fas fa-fire-extinguisher"]
                            ];
                            ?>
                            <?php foreach ($availableAmenities as $amenity): 
                                $isChecked = in_array($amenity['name'], $existingAmenities);
                            ?>
                                <label class="mg-amenity-chip">
                                    <input type="checkbox" name="amenities[]" value="<?= htmlspecialchars($amenity['name']) ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <div class="mg-amenity-box">
                                        <div class="mg-amenity-icon">
                                            <i class="<?= $amenity['icon'] ?>"></i>
                                        </div>
                                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4: OWNER CONTACT & FINANCIALS -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-amber-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 4: Owner Details &amp; Commercial Terms</h5>
                        <p class="mg-chevron-sub">Center head, public enquiry number and franchise royalty percentage</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 4</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-id-card"></i></span>
                                Center Head / Owner Name <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="owner_name" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['owner_name']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-at"></i></span>
                                Owner Email Address <span class="mg-required">*</span>
                            </label>
                            <input type="email" name="owner_email" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['owner_email']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-mobile-screen"></i></span>
                                Owner Direct Mobile <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-flag me-1 text-danger"></i> +91</span>
                                <input type="tel" name="owner_mobile" class="form-control mg-input-control" 
                                    maxlength="15"
                                    value="<?= htmlspecialchars(str_replace('+91 ', '', $center['owner_mobile'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-headset"></i></span>
                                Student Helpline / Enquiry No.
                            </label>
                            <input type="text" name="helpline_number" class="form-control mg-input-control" 
                                value="<?= htmlspecialchars($center['helpline_number'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-percent"></i></span>
                                Center Royalty Fee (%) <span class="mg-required">*</span>
                            </label>
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

        <!-- SECTION 5: LEGAL & VERIFICATION DOCUMENTS (PDF ONLY) -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-emerald-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-file-shield"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 5: Verification Documents (PDF Format Only)</h5>
                        <p class="mg-chevron-sub">Statutory files in PDF format (Current files can be inspected or replaced)</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 5</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-4">
                    <!-- Owner Aadhar Card (PDF Only) -->
                    <div class="col-md-4">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-emerald"><i class="fas fa-file-pdf"></i></span>
                            Owner Aadhar Card (PDF)
                        </label>
                        <?php if (!empty($center['aadhar_card_doc'])): ?>
                            <div class="mb-2">
                                <a href="../../<?= htmlspecialchars($center['aadhar_card_doc']) ?>" target="_blank" class="mg-current-file-badge">
                                    <i class="fas fa-file-pdf text-danger"></i> View Current Aadhar PDF
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_aadhar').click()">
                            <input type="file" name="aadhar_card_doc" id="file_aadhar" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_aadhar')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h6 class="mg-upload-title"><?= !empty($center['aadhar_card_doc']) ? 'Upload New Aadhar' : 'Select Aadhar PDF' ?></h6>
                            <p class="mg-upload-sub">Leave empty to keep existing file</p>
                            <span class="mg-upload-badge pdf">PDF ONLY</span>
                            <div class="mg-file-chosen-preview" id="preview_aadhar">
                                <i class="fas fa-check-circle"></i> <span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Owner PAN Card (PDF Only) -->
                    <div class="col-md-4">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-emerald"><i class="fas fa-file-lines"></i></span>
                            Owner PAN Card (PDF)
                        </label>
                        <?php if (!empty($center['pan_card_doc'])): ?>
                            <div class="mb-2">
                                <a href="../../<?= htmlspecialchars($center['pan_card_doc']) ?>" target="_blank" class="mg-current-file-badge">
                                    <i class="fas fa-file-pdf text-danger"></i> View Current PAN PDF
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_pan').click()">
                            <input type="file" name="pan_card_doc" id="file_pan" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_pan')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h6 class="mg-upload-title"><?= !empty($center['pan_card_doc']) ? 'Upload New PAN' : 'Select PAN PDF' ?></h6>
                            <p class="mg-upload-sub">Leave empty to keep existing file</p>
                            <span class="mg-upload-badge pdf">PDF ONLY</span>
                            <div class="mg-file-chosen-preview" id="preview_pan">
                                <i class="fas fa-check-circle"></i> <span></span>
                            </div>
                        </div>
                    </div>

                    <!-- GST Number & Certificate PDF -->
                    <div class="col-md-4">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-emerald"><i class="fas fa-receipt"></i></span>
                            GST Number &amp; Certificate <span class="text-muted">(Optional)</span>
                        </label>
                        <input type="text" name="gst_number" class="form-control mg-input-control mb-2 text-uppercase" 
                            placeholder="GSTIN (e.g. 06AAAAA0000A1Z5)"
                            value="<?= htmlspecialchars($center['gst_number'] ?? '') ?>">

                        <?php if (!empty($center['gst_certificate_doc'])): ?>
                            <div class="mb-2">
                                <a href="../../<?= htmlspecialchars($center['gst_certificate_doc']) ?>" target="_blank" class="mg-current-file-badge">
                                    <i class="fas fa-file-pdf text-danger"></i> View Current GST Certificate
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_gst').click()">
                            <input type="file" name="gst_certificate_doc" id="file_gst" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_gst')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h6 class="mg-upload-title"><?= !empty($center['gst_certificate_doc']) ? 'Upload New GST PDF' : 'Select GST PDF' ?></h6>
                            <p class="mg-upload-sub">Leave empty to keep existing file</p>
                            <span class="mg-upload-badge pdf">PDF ONLY</span>
                            <div class="mg-file-chosen-preview" id="preview_gst">
                                <i class="fas fa-check-circle"></i> <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 6: OWNER PHOTO & SIGNATURE -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-navy-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 6: Owner Photo &amp; Authorized Signature</h5>
                        <p class="mg-chevron-sub">Executive portrait and official signature image assets</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Step 6</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-4">
                    <!-- Owner Image -->
                    <div class="col-md-6">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-navy"><i class="fas fa-user-circle"></i></span>
                            Center Head / Owner Image
                        </label>
                        <div class="mg-upload-dropzone" onclick="document.getElementById('file_owner_photo').click()">
                            <input type="file" name="owner_photo" id="file_owner_photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewImageFile(this, 'img_preview_photo')">
                            <div class="mg-upload-icon-circle image">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h6 class="mg-upload-title">Change Owner Passport Photo</h6>
                            <p class="mg-upload-sub">Leave untouched to keep current photo</p>
                            <span class="mg-upload-badge img">IMAGE FILE</span>
                            
                            <!-- Display current or newly chosen photo -->
                            <?php if (!empty($center['owner_photo'])): ?>
                                <img src="../../<?= htmlspecialchars($center['owner_photo']) ?>" id="img_preview_photo" class="mg-img-preview-box" style="display:block;" alt="Current Owner Photo">
                            <?php else: ?>
                                <img src="" id="img_preview_photo" class="mg-img-preview-box" alt="Owner Photo Preview">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Owner Signature Image -->
                    <div class="col-md-6">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-navy"><i class="fas fa-signature"></i></span>
                            Owner Authorized Signature Image
                        </label>
                        <div class="mg-upload-dropzone" onclick="document.getElementById('file_owner_sig').click()">
                            <input type="file" name="owner_signature" id="file_owner_sig" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewImageFile(this, 'img_preview_sig')">
                            <div class="mg-upload-icon-circle image">
                                <i class="fas fa-pen-nib"></i>
                            </div>
                            <h6 class="mg-upload-title">Change Specimen Signature</h6>
                            <p class="mg-upload-sub">Leave untouched to keep current signature</p>
                            <span class="mg-upload-badge img">IMAGE FILE</span>

                            <!-- Display current or newly chosen signature -->
                            <?php if (!empty($center['owner_signature'])): ?>
                                <img src="../../<?= htmlspecialchars($center['owner_signature']) ?>" id="img_preview_sig" class="mg-img-preview-box" style="display:block; background:#fff; padding:6px;" alt="Current Signature">
                            <?php else: ?>
                                <img src="" id="img_preview_sig" class="mg-img-preview-box" alt="Owner Signature Preview">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Status Row -->
                <div class="row mt-4 pt-3 border-top">
                    <div class="col-md-4">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-navy"><i class="fas fa-toggle-on"></i></span>
                                Current Center Status
                            </label>
                            <select name="status" class="form-select mg-input-control">
                                <option value="active" <?= ($center['status'] === 'active') ? 'selected' : '' ?>>Active &bull; Fully Authorized</option>
                                <option value="pending" <?= ($center['status'] === 'pending') ? 'selected' : '' ?>>Pending &bull; Verification in Progress</option>
                                <option value="inactive" <?= ($center['status'] === 'inactive') ? 'selected' : '' ?>>Inactive &bull; Temporarily Paused</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM ACTION FOOTER -->
        <div class="mg-form-footer">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-clock-rotate-left text-primary fa-lg"></i>
                <span class="text-muted fw-semibold" style="font-size: 0.88rem;">Last updated: <?= date('d M Y, h:i A', strtotime($center['updated_at'])) ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="btn mg-btn-reset">
                    <i class="fas fa-xmark me-1"></i> Cancel
                </a>
                <button type="submit" class="btn mg-btn-submit">
                    <i class="fas fa-check-double me-1"></i> Update &amp; Save Changes
                </button>
            </div>
        </div>

    </form>
</div>

<!-- India Post API Script & Interactive Handlers -->
<script>
$(document).ready(function() {
    let pincodeTimer = null;

    $('#mg_pincode_input').on('input keyup paste', function() {
        const pin = $(this).val().trim();
        $('#pincode_loader, #pincode_success, #pincode_error').hide();

        if (pin.length === 6 && /^\d{6}$/.test(pin)) {
            clearTimeout(pincodeTimer);
            $('#pincode_loader').show();
            $('#pincode_help_text').html('<span class="text-info"><i class="fas fa-circle-notch fa-spin"></i> Contacting India Post postal API...</span>');

            pincodeTimer = setTimeout(function() {
                fetchIndiaPostData(pin);
            }, 300);
        } else if (pin.length > 0 && pin.length < 6) {
            $('#pincode_help_text').html('<span class="text-muted">Enter all 6 digits to autofill City &amp; State.</span>');
            $('#postoffice_select_wrap').hide();
        }
    });

    function fetchIndiaPostData(pincode) {
        const apiUrl = 'https://api.postalpincode.in/pincode/' + pincode;

        $.ajax({
            url: apiUrl,
            type: 'GET',
            dataType: 'json',
            timeout: 6000,
            success: function(response) {
                $('#pincode_loader').hide();

                if (response && response[0] && response[0].Status === 'Success' && response[0].PostOffice && response[0].PostOffice.length > 0) {
                    const postOffices = response[0].PostOffice;
                    const primary = postOffices[0];

                    const city = primary.District || primary.Region || primary.Circle;
                    const state = primary.State;

                    $('#mg_city_input').val(city).addClass('is-valid');
                    $('#mg_state_input').val(state).addClass('is-valid');

                    $('#pincode_success').show();
                    $('#pincode_help_text').html(
                        '<span class="text-success"><i class="fas fa-check-circle"></i> India Post Verified: <strong>' + city + ', ' + state + '</strong> (' + postOffices.length + ' post offices found)</span>'
                    );

                    const select = $('#mg_postoffice_select');
                    select.empty();
                    select.append('<option value="">-- Select Post Office to append to address --</option>');
                    postOffices.forEach(function(po) {
                        select.append('<option value="' + po.Name + '">' + po.Name + ' (' + po.BranchType + ')</option>');
                    });
                    $('#postoffice_select_wrap').slideDown();

                    select.off('change').on('change', function() {
                        const poName = $(this).val();
                        if (poName) {
                            const curAddr = $('#mg_address_textarea').val().trim();
                            if (curAddr.indexOf(poName) === -1) {
                                $('#mg_address_textarea').val(curAddr ? (curAddr + ', Near ' + poName + ' Post Office') : ('Near ' + poName + ' Post Office'));
                            }
                        }
                    });

                } else {
                    $('#pincode_error').show();
                    $('#pincode_help_text').html('<span class="text-danger"><i class="fas fa-triangle-exclamation"></i> No postal records found for this PIN.</span>');
                }
            },
            error: function() {
                $('#pincode_loader').hide();
            }
        });
    }
});

function updateFileLabel(input, previewId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name;
        const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2);
        
        if (!fileName.toLowerCase().endsWith('.pdf')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Only PDF format is permitted for this document.',
                confirmButtonColor: '#e34e26'
            });
            input.value = '';
            $('#' + previewId).hide();
            return;
        }

        $('#' + previewId).html('<i class="fas fa-check-circle"></i> New File: ' + fileName + ' (' + fileSizeMb + ' MB)').css('display', 'flex');
    }
}

function previewImageFile(input, imgElementId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            $('#' + imgElementId).attr('src', e.target.result).show();
        };

        reader.readAsDataURL(file);
    }
}
</script>

<?php if (!empty($successMessage)): ?>
<script>
Swal.fire({
    title: 'Center Updated!',
    text: <?= json_encode($successMessage) ?>,
    icon: 'success',
    confirmButtonColor: '#10b981',
    confirmButtonText: 'Great!'
});
</script>
<?php endif; ?>

<?php 
// Include Admin Footer
require_once dirname(__DIR__) . '/footer.php'; 
?>

