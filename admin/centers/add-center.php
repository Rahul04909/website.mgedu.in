<?php
/**
 * MG Education & Social Development Organization
 * Add New Authorized Study Center
 * 
 * Features:
 * - 100% Quick Features Colorful Section UI
 * - India Post Official PIN Code API Integration with City & State Autofill
 * - Strict PDF File Uploads for Aadhar, PAN & GST Documents
 * - Live Preview for Owner Passport Photo & Signature
 * - Complete Infrastructure, Working Days/Hours & Amenities Checklists
 */

// Include Admin Top Header (Enforces JWT Auth & Navigation)
require_once dirname(__DIR__) . '/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';

$pdo = getDB();
$errors = [];
$successMessage = '';

// Auto-generate Center Code suggestion
$suggestedCode = 'MG-CENT-' . rand(1010, 9990);
if ($pdo) {
    try {
        $lastCenter = $pdo->query("SELECT id FROM `centers` ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($lastCenter) {
            $suggestedCode = 'MG-CENT-' . (1000 + (int)$lastCenter['id'] + 1);
        }
    } catch (Throwable $e) {}
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_center'])) {
    // 1. Sanitize text fields
    $centerCode         = trim($_POST['center_code'] ?? $suggestedCode);
    $centerName         = trim($_POST['center_name'] ?? '');
    $centerEmail        = trim($_POST['center_email'] ?? '');
    $centerMobile       = trim($_POST['center_mobile'] ?? '');
    $pincode            = trim($_POST['pincode'] ?? '');
    $city               = trim($_POST['city'] ?? '');
    $state              = trim($_POST['state'] ?? '');
    $country            = 'India'; // Fixed default
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

    // 2. Validate mandatory fields
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

    // 3. Ensure upload directories exist
    $baseUploadDir = dirname(dirname(__DIR__)) . '/uploads/centers';
    $docsDir       = $baseUploadDir . '/documents';
    $photosDir     = $baseUploadDir . '/photos';
    $sigsDir       = $baseUploadDir . '/signatures';

    foreach ([$baseUploadDir, $docsDir, $photosDir, $sigsDir] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    // Helper for uploading PDF documents
    $handlePdfUpload = function ($fileKey, $prefix) use ($docsDir, &$errors) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$fileKey}. Code: " . $file['error'];
            return null;
        }

        // Validate PDF extension & mime
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'])) {
            $errors[] = "Document '{$fileKey}' MUST be in PDF format only. (Uploaded: .{$ext})";
            return null;
        }

        // 10MB limit
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "Document '{$fileKey}' exceeds 10MB limit.";
            return null;
        }

        $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $destination = $docsDir . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/centers/documents/' . $newFileName;
        } else {
            $errors[] = "Failed to save document {$fileKey}.";
            return null;
        }
    };

    // Helper for uploading images (Owner Photo & Signature)
    $handleImageUpload = function ($fileKey, $targetDir, $relPathPrefix, $prefix) use (&$errors) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$fileKey}. Code: " . $file['error'];
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            $errors[] = "Image '{$fileKey}' must be JPG, PNG, or WEBP.";
            return null;
        }

        // 5MB limit
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image '{$fileKey}' exceeds 5MB limit.";
            return null;
        }

        $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $targetDir . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $relPathPrefix . '/' . $newFileName;
        } else {
            $errors[] = "Failed to save image {$fileKey}.";
            return null;
        }
    };

    // Process File Uploads
    $aadharDocPath = $handlePdfUpload('aadhar_card_doc', 'aadhar');
    $panDocPath    = $handlePdfUpload('pan_card_doc', 'pan');
    $gstDocPath    = $handlePdfUpload('gst_certificate_doc', 'gst');

    $ownerPhotoPath     = $handleImageUpload('owner_photo', $photosDir, 'uploads/centers/photos', 'owner_photo');
    $ownerSignaturePath = $handleImageUpload('owner_signature', $sigsDir, 'uploads/centers/signatures', 'owner_sig');

    // 4. Save to Database
    if (empty($errors)) {
        if (!$pdo) {
            $errors[] = "Database connection not available.";
        } else {
            try {
                $insertSql = "INSERT INTO `centers` (
                    `center_code`, `center_name`, `center_email`, `center_mobile`,
                    `pincode`, `city`, `state`, `country`, `address`,
                    `classrooms_count`, `computers_count`, `total_staff`, `lab_type`,
                    `working_days_from`, `working_days_to`, `working_hours_from`, `working_hours_to`,
                    `amenities`, `owner_name`, `owner_email`, `owner_mobile`, `helpline_number`,
                    `royalty_percentage`, `aadhar_card_doc`, `pan_card_doc`, `gst_number`,
                    `gst_certificate_doc`, `owner_photo`, `owner_signature`, `status`
                ) VALUES (
                    :center_code, :center_name, :center_email, :center_mobile,
                    :pincode, :city, :state, :country, :address,
                    :classrooms_count, :computers_count, :total_staff, :lab_type,
                    :working_days_from, :working_days_to, :working_hours_from, :working_hours_to,
                    :amenities, :owner_name, :owner_email, :owner_mobile, :helpline_number,
                    :royalty_percentage, :aadhar_card_doc, :pan_card_doc, :gst_number,
                    :gst_certificate_doc, :owner_photo, :owner_signature, :status
                )";

                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    ':center_code'         => $centerCode,
                    ':center_name'         => $centerName,
                    ':center_email'        => $centerEmail,
                    ':center_mobile'       => $centerMobile,
                    ':pincode'             => $pincode,
                    ':city'                => $city,
                    ':state'               => $state,
                    ':country'             => $country,
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
                    ':status'              => $status
                ]);

                // Success! Set flag for SweetAlert2
                $successMessage = "Authorized Study Center '{$centerName}' [{$centerCode}] created successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = "A center with Code '{$centerCode}' already exists. Please choose a unique code.";
                } else {
                    $errors[] = "Database Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Page Header & Action Bar -->
    <div class="mg-page-header">
        <div class="mg-page-header-title">
            <div class="mg-page-header-icon">
                <i class="fas fa-building-circle-check"></i>
            </div>
            <div>
                <h4>Create Authorized Study Center</h4>
                <p>Register new franchise branch with infrastructure, India Post verified geolocation &amp; legal PDF records.</p>
            </div>
        </div>
        <div>
            <a href="index.php" class="mg-btn-action mg-btn-navy">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Centers List</span>
            </a>
        </div>
    </div>

    <!-- Error Summary Alerts -->
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

    <!-- Center Creation Form Started -->
    <form method="POST" action="" enctype="multipart/form-data" id="mgAddCenterForm" autocomplete="off">
        <input type="hidden" name="action_save_center" value="1">

        <!-- ==========================================================================
             SECTION 1: CENTER BASIC PROFILE & CONTACT (Vermilion / Orange Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-vermilion-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 1: Center Identity &amp; Contact</h5>
                        <p class="mg-chevron-sub">Official institute branding, registration code and primary communication</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Identity &bull; Step 1</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <!-- Center Code -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-barcode"></i></span>
                                Center Code / ID <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="center_code" class="form-control mg-input-control text-uppercase fw-bold" 
                                value="<?= htmlspecialchars($_POST['center_code'] ?? $suggestedCode) ?>" required>
                            <small class="text-muted">Unique franchise center identifier (Auto-suggested).</small>
                        </div>
                    </div>

                    <!-- Center Name -->
                    <div class="col-md-8">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-school"></i></span>
                                Center / Institute Name <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="center_name" class="form-control mg-input-control" 
                                placeholder="e.g. Prayag Institute of Computer & Skill Development"
                                value="<?= htmlspecialchars($_POST['center_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Center Official Email -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-envelope"></i></span>
                                Center Official Email <span class="mg-required">*</span>
                            </label>
                            <input type="email" name="center_email" class="form-control mg-input-control" 
                                placeholder="center.branch@mgedu.in"
                                value="<?= htmlspecialchars($_POST['center_email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Center Mobile Number (+91) -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-vermilion"><i class="fas fa-phone"></i></span>
                                Center Mobile Number <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-flag me-1 text-danger"></i> +91</span>
                                <input type="tel" name="center_mobile" class="form-control mg-input-control" 
                                    placeholder="8059982049" maxlength="15"
                                    value="<?= htmlspecialchars($_POST['center_mobile'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================================
             SECTION 2: GEOGRAPHIC LOCATION & INDIA POST API (Cyan / Blue Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-cyan-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 2: Address &amp; Location (India Post API)</h5>
                        <p class="mg-chevron-sub">Enter 6-digit Postal PIN to auto-fetch City, State &amp; Post Office localities</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Location &bull; Step 2</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <!-- PIN Code with Live India Post API Hook -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-map-pin"></i></span>
                                Postal PIN Code <span class="mg-required">*</span>
                            </label>
                            <div class="mg-pincode-wrap">
                                <input type="text" name="pincode" id="mg_pincode_input" 
                                    class="form-control mg-input-control fw-bold" 
                                    placeholder="e.g. 110001 or 134003" 
                                    maxlength="6" 
                                    pattern="\d{6}"
                                    value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>" required>
                                <!-- Status badge inside input -->
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
                                Type 6 digits — City &amp; State will auto-fill from <strong>api.postalpincode.in</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- City / District (Autofilled) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-city"></i></span>
                                City / District <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="city" id="mg_city_input" class="form-control mg-input-control" 
                                placeholder="Autofilled from PIN" 
                                value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- State (Autofilled) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-landmark-dome"></i></span>
                                State <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="state" id="mg_state_input" class="form-control mg-input-control" 
                                placeholder="Autofilled from PIN" 
                                value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Country (India - Fixed & Non-Editable) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-globe-asia"></i></span>
                                Country <span class="text-muted">(Default: Locked)</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-lock text-muted"></i></span>
                                <input type="text" name="country" class="form-control mg-input-control mg-readonly-badge" 
                                    value="India" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>

                    <!-- Post Office Localities Dropdown (Populated by API) -->
                    <div class="col-md-8" id="postoffice_select_wrap" style="display: none;">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-envelope-open-text"></i></span>
                                Post Office Locality <span class="text-muted">(India Post Suggestion)</span>
                            </label>
                            <select id="mg_postoffice_select" class="form-select mg-input-control">
                                <option value="">Select post office branch...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Complete Address Textarea -->
                    <div class="col-12">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-cyan"><i class="fas fa-location-crosshairs"></i></span>
                                Complete Address Of Center <span class="mg-required">*</span>
                            </label>
                            <textarea name="address" id="mg_address_textarea" class="form-control mg-input-control" rows="3" 
                                placeholder="Building No., SCO/Shop No., Street/Road, Landmark, Area..." required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================================
             SECTION 3: INFRASTRUCTURE & AMENITIES (Purple Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-purple-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 3: Infrastructure, Lab &amp; Facilities</h5>
                        <p class="mg-chevron-sub">Classroom count, computer lab setup, working schedules and available amenities</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Facilities &bull; Step 3</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <!-- Classrooms -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-chalkboard-user"></i></span>
                                Classrooms <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="classrooms_count" class="form-control mg-input-control" min="1" max="100" 
                                value="<?= htmlspecialchars($_POST['classrooms_count'] ?? '2') ?>" required>
                        </div>
                    </div>

                    <!-- Computers -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-desktop"></i></span>
                                Computers <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="computers_count" class="form-control mg-input-control" min="0" max="500" 
                                value="<?= htmlspecialchars($_POST['computers_count'] ?? '20') ?>" required>
                        </div>
                    </div>

                    <!-- Total Staff -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-users-gear"></i></span>
                                Total Staff <span class="mg-required">*</span>
                            </label>
                            <input type="number" name="total_staff" class="form-control mg-input-control" min="0" max="100" 
                                value="<?= htmlspecialchars($_POST['total_staff'] ?? '5') ?>" required>
                        </div>
                    </div>

                    <!-- Lab Type -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-microchip"></i></span>
                                Lab Type <span class="mg-required">*</span>
                            </label>
                            <select name="lab_type" class="form-select mg-input-control">
                                <option value="Basic" <?= (($_POST['lab_type'] ?? '') === 'Basic') ? 'selected' : '' ?>>Basic (Standard PC Lab)</option>
                                <option value="Advanced" <?= (($_POST['lab_type'] ?? 'Advanced') === 'Advanced') ? 'selected' : '' ?>>Advanced (High-End / Programming / Multi-OS)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Working Days From & To -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="far fa-calendar-check"></i></span>
                                Working Day: From
                            </label>
                            <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; ?>
                            <select name="working_days_from" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= (($_POST['working_days_from'] ?? 'Monday') === $day) ? 'selected' : '' ?>><?= $day ?></option>
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
                                    <option value="<?= $day ?>" <?= (($_POST['working_days_to'] ?? 'Saturday') === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Working Hours From & To -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="far fa-clock"></i></span>
                                Hours: From
                            </label>
                            <input type="text" name="working_hours_from" class="form-control mg-input-control" 
                                placeholder="09:00 AM" value="<?= htmlspecialchars($_POST['working_hours_from'] ?? '09:00 AM') ?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-purple"><i class="fas fa-hourglass-end"></i></span>
                                Hours: To
                            </label>
                            <input type="text" name="working_hours_to" class="form-control mg-input-control" 
                                placeholder="06:00 PM" value="<?= htmlspecialchars($_POST['working_hours_to'] ?? '06:00 PM') ?>">
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
                            $selectedAmenities = $_POST['amenities'] ?? [
                                "High-Speed Internet", "Power Backup (Inverter/Gen)", "RO Pure Drinking Water"
                            ];
                            ?>
                            <?php foreach ($availableAmenities as $amenity): 
                                $isChecked = in_array($amenity['name'], $selectedAmenities);
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

        <!-- ==========================================================================
             SECTION 4: OWNER CONTACT & FINANCIAL ROYALTY (Amber Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-amber-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 4: Owner Details &amp; Commercial Terms</h5>
                        <p class="mg-chevron-sub">Center head information, public helpline number and monthly royalty percentage</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Commercials &bull; Step 4</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-3">
                    <!-- Owner Name -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-id-card"></i></span>
                                Center Head / Owner Name <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="owner_name" class="form-control mg-input-control" 
                                placeholder="e.g. Rahul Dhiman"
                                value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Owner Email -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-at"></i></span>
                                Owner Email Address <span class="mg-required">*</span>
                            </label>
                            <input type="email" name="owner_email" class="form-control mg-input-control" 
                                placeholder="owner@gmail.com"
                                value="<?= htmlspecialchars($_POST['owner_email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Owner Mobile (+91) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-mobile-screen"></i></span>
                                Owner Direct Mobile <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix"><i class="fas fa-flag me-1 text-danger"></i> +91</span>
                                <input type="tel" name="owner_mobile" class="form-control mg-input-control" 
                                    placeholder="8059982049" maxlength="15"
                                    value="<?= htmlspecialchars($_POST['owner_mobile'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Public Helpline Number -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-headset"></i></span>
                                Student Helpline / Enquiry No.
                            </label>
                            <input type="text" name="helpline_number" class="form-control mg-input-control" 
                                placeholder="e.g. 1800-889-1001 or Landline"
                                value="<?= htmlspecialchars($_POST['helpline_number'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Center Royalty Fees (% percentage) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-amber"><i class="fas fa-percent"></i></span>
                                Center Royalty Fee (%) <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group has-suffix">
                                <input type="number" step="0.01" min="0" max="100" name="royalty_percentage" 
                                    class="form-control mg-input-control fw-bold" 
                                    placeholder="15.00"
                                    value="<?= htmlspecialchars($_POST['royalty_percentage'] ?? '15.00') ?>" required>
                                <span class="mg-input-suffix">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================================
             SECTION 5: LEGAL & VERIFICATION DOCUMENTS (PDF ONLY) (Emerald Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-emerald-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-file-shield"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 5: Verification Documents (PDF Format Only)</h5>
                        <p class="mg-chevron-sub">Upload statutory verification files strictly in PDF format (Max 10MB per document)</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Documents &bull; Step 5</div>
            </div>

            <div class="mg-form-body">
                <div class="row g-4">
                    <!-- Owner Aadhar Card (PDF Only) -->
                    <div class="col-md-4">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-emerald"><i class="fas fa-file-pdf"></i></span>
                            Owner Aadhar Card (PDF)
                        </label>
                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_aadhar').click()">
                            <input type="file" name="aadhar_card_doc" id="file_aadhar" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_aadhar')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h6 class="mg-upload-title">Select Aadhar PDF</h6>
                            <p class="mg-upload-sub">Click to browse or drag file here</p>
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
                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_pan').click()">
                            <input type="file" name="pan_card_doc" id="file_pan" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_pan')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h6 class="mg-upload-title">Select PAN PDF</h6>
                            <p class="mg-upload-sub">Click to browse or drag file here</p>
                            <span class="mg-upload-badge pdf">PDF ONLY</span>
                            <div class="mg-file-chosen-preview" id="preview_pan">
                                <i class="fas fa-check-circle"></i> <span></span>
                            </div>
                        </div>
                    </div>

                    <!-- GST Number & GST Certificate PDF (Optional) -->
                    <div class="col-md-4">
                        <label class="mg-form-label">
                            <span class="mg-label-icon icon-emerald"><i class="fas fa-receipt"></i></span>
                            GST Number &amp; Certificate <span class="text-muted">(Optional)</span>
                        </label>
                        <input type="text" name="gst_number" class="form-control mg-input-control mb-2 text-uppercase" 
                            placeholder="GSTIN (e.g. 06AAAAA0000A1Z5)"
                            value="<?= htmlspecialchars($_POST['gst_number'] ?? '') ?>">

                        <div class="mg-upload-dropzone pdf-zone" onclick="document.getElementById('file_gst').click()">
                            <input type="file" name="gst_certificate_doc" id="file_gst" accept=".pdf,application/pdf" style="display:none;" onchange="updateFileLabel(this, 'preview_gst')">
                            <div class="mg-upload-icon-circle pdf">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <h6 class="mg-upload-title">GST Certificate (PDF)</h6>
                            <p class="mg-upload-sub">Optional legal registration copy</p>
                            <span class="mg-upload-badge pdf">PDF ONLY</span>
                            <div class="mg-file-chosen-preview" id="preview_gst">
                                <i class="fas fa-check-circle"></i> <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================================
             SECTION 6: OWNER PHOTO & SIGNATURE ASSETS (Navy Theme)
             ========================================================================== -->
        <div class="mg-form-card">
            <div class="mg-chevron-header theme-navy-header">
                <div class="mg-chevron-left">
                    <div class="mg-chevron-icon-hole">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div>
                        <h5 class="mg-chevron-title">Section 6: Owner Photo &amp; Authorized Signature</h5>
                        <p class="mg-chevron-sub">Executive portrait and digital specimen signature for certificates and center profile</p>
                    </div>
                </div>
                <div class="mg-chevron-badge d-none d-md-block">Assets &bull; Step 6</div>
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
                            <h6 class="mg-upload-title">Upload Owner Passport Photo</h6>
                            <p class="mg-upload-sub">JPEG, PNG or WEBP (Square ratio recommended)</p>
                            <span class="mg-upload-badge img">IMAGE FILE</span>
                            <img src="" id="img_preview_photo" class="mg-img-preview-box" alt="Owner Photo Preview">
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
                            <h6 class="mg-upload-title">Upload Specimen Signature</h6>
                            <p class="mg-upload-sub">Clean signature on white paper background</p>
                            <span class="mg-upload-badge img">IMAGE FILE</span>
                            <img src="" id="img_preview_sig" class="mg-img-preview-box" alt="Owner Signature Preview">
                        </div>
                    </div>
                </div>

                <!-- Status Selector Row -->
                <div class="row mt-4 pt-3 border-top">
                    <div class="col-md-4">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">
                                <span class="mg-label-icon icon-navy"><i class="fas fa-toggle-on"></i></span>
                                Initial Center Status
                            </label>
                            <select name="status" class="form-select mg-input-control">
                                <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active &bull; Fully Authorized</option>
                                <option value="pending" <?= (($_POST['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending &bull; Verification in Progress</option>
                                <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive &bull; Temporarily Paused</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================================
             STICKY / FIXED FORM ACTION FOOTER
             ========================================================================== -->
        <div class="mg-form-footer">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-shield-halved text-success fa-lg"></i>
                <span class="text-muted fw-semibold" style="font-size: 0.88rem;">All records are saved directly to the central MG Education registry.</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="reset" class="btn mg-btn-reset">
                    <i class="fas fa-rotate-left me-1"></i> Reset Fields
                </button>
                <button type="submit" class="btn mg-btn-submit">
                    <i class="fas fa-floppy-disk me-1"></i> Register &amp; Save Study Center
                </button>
            </div>
        </div>

    </form>
</div>

<!-- India Post API Script & Interactive File Preview Handlers -->
<script>
$(document).ready(function() {
    let pincodeTimer = null;

    // PIN code auto lookup on input
    $('#mg_pincode_input').on('input keyup paste', function() {
        const pin = $(this).val().trim();

        // Clear previous states
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

                    // Autofill City (District / Region)
                    const city = primary.District || primary.Region || primary.Circle;
                    const state = primary.State;

                    $('#mg_city_input').val(city).addClass('is-valid');
                    $('#mg_state_input').val(state).addClass('is-valid');

                    // Flash feedback
                    $('#pincode_success').show();
                    $('#pincode_help_text').html(
                        '<span class="text-success"><i class="fas fa-check-circle"></i> India Post Verified: <strong>' + city + ', ' + state + '</strong> (' + postOffices.length + ' post offices found)</span>'
                    );

                    // Populate locality selector
                    const select = $('#mg_postoffice_select');
                    select.empty();
                    select.append('<option value="">-- Select Post Office to append to address --</option>');
                    postOffices.forEach(function(po) {
                        select.append('<option value="' + po.Name + '">' + po.Name + ' (' + po.BranchType + ')</option>');
                    });
                    $('#postoffice_select_wrap').slideDown();

                    // Optional: append locality to address on change
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
                    $('#pincode_help_text').html('<span class="text-danger"><i class="fas fa-triangle-exclamation"></i> No postal records found for this PIN. Please enter City &amp; State manually.</span>');
                }
            },
            error: function() {
                $('#pincode_loader').hide();
                $('#pincode_help_text').html('<span class="text-warning"><i class="fas fa-wifi"></i> India Post API unreachable. Please enter City &amp; State manually.</span>');
            }
        });
    }

    // Check on initial load if PIN already has 6 digits (e.g. on validation reload)
    const initialPin = $('#mg_pincode_input').val().trim();
    if (initialPin.length === 6 && /^\d{6}$/.test(initialPin) && !$('#mg_city_input').val()) {
        fetchIndiaPostData(initialPin);
    }
});

// Update label for PDF uploads
function updateFileLabel(input, previewId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name;
        const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2);
        
        // Strict client-side check
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

        $('#' + previewId).html('<i class="fas fa-check-circle"></i> ' + fileName + ' (' + fileSizeMb + ' MB)').css('display', 'flex');
    }
}

// Live Image preview for Owner Photo & Signature
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
    title: 'Center Created!',
    text: <?= json_encode($successMessage) ?>,
    icon: 'success',
    confirmButtonColor: '#10b981',
    confirmButtonText: 'Go to Centers List'
}).then((result) => {
    window.location.href = 'index.php?msg=created';
});
</script>
<?php endif; ?>

<?php 
// Include Admin Footer
require_once dirname(__DIR__) . '/footer.php'; 
?>
