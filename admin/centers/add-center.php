<?php
/**
 * MG Education & Social Development Organization
 * Add New Authorized Study Center
 * 
 * Features:
 * - Enterprise-grade Clean & Professional Form Architecture
 * - Center ID Format: MGEDU2026001 (MGEDU: Prefix | 2026: Year | 001: Serial)
 * - India Post PIN Code Verification with Single Dynamic Status (No Overlapping Text)
 * - Strict PDF Document Verification & Responsive Media Upload Cards
 */

// Include Admin Top Header (Enforces JWT Auth & Navigation)
require_once dirname(__DIR__) . '/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';

$pdo = getDB();
$errors = [];
$successMessage = '';

// Generate Center ID format: MGEDU2026001 (MGEDU + Year + 3-digit serial)
$currentYear = date('Y');
$codePrefix = 'MGEDU' . $currentYear;
$suggestedCode = $codePrefix . '001';

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT `center_code` FROM `centers` WHERE `center_code` LIKE :prefix ORDER BY `center_code` DESC LIMIT 1");
        $stmt->execute([':prefix' => $codePrefix . '%']);
        $lastCode = $stmt->fetchColumn();
        if ($lastCode && strlen($lastCode) >= strlen($codePrefix) + 3) {
            $lastNum = (int)substr($lastCode, strlen($codePrefix));
            $suggestedCode = $codePrefix . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `centers`");
            $count = (int)$countStmt->fetchColumn();
            $suggestedCode = $codePrefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        }
    } catch (Throwable $e) {
        $suggestedCode = $codePrefix . '001';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_center'])) {
    $centerCode         = trim($_POST['center_code'] ?? $suggestedCode);
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

    // Ensure upload directories
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

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'])) {
            $errors[] = "Document '{$fileKey}' must be a PDF file.";
            return null;
        }

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

    // Helper for uploading images
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

    $aadharDocPath = $handlePdfUpload('aadhar_card_doc', 'aadhar');
    $panDocPath    = $handlePdfUpload('pan_card_doc', 'pan');
    $gstDocPath    = $handlePdfUpload('gst_certificate_doc', 'gst');

    $ownerPhotoPath     = $handleImageUpload('owner_photo', $photosDir, 'uploads/centers/photos', 'owner_photo');
    $ownerSignaturePath = $handleImageUpload('owner_signature', $sigsDir, 'uploads/centers/signatures', 'owner_sig');

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

    <!-- Page Header -->
    <div class="mg-page-header">
        <div class="mg-page-header-title">
            <div class="mg-page-header-icon">
                <i class="fas fa-plus"></i>
            </div>
            <div>
                <h4>Add Authorized Study Center</h4>
                <p>Register new franchise institute, infrastructure details and legal verification records.</p>
            </div>
        </div>
        <div>
            <a href="index.php" class="mg-btn-action mg-btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Centers</span>
            </a>
        </div>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center mb-1">
                <i class="fas fa-circle-exclamation me-2"></i>
                <strong>Please review the required fields:</strong>
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
    <form method="POST" action="" enctype="multipart/form-data" id="mgAddCenterForm" autocomplete="off">
        <input type="hidden" name="action_save_center" value="1">

        <!-- 1. Center Identity & Contact -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon blue">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Center Identity &amp; Contact Details</h6>
                        <span class="mg-section-sub">Official center profile, unique registration ID and communications</span>
                    </div>
                </div>
                <span class="mg-section-tag">Identity</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <!-- Center Code (MGEDU2026001 format) -->
                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Center ID / Code <span class="mg-required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="center_code" class="form-control mg-input-control font-monospace fw-bold" 
                                    value="<?= htmlspecialchars($_POST['center_code'] ?? $suggestedCode) ?>" required>
                                <span class="input-group-text bg-light text-muted" style="font-size:0.75rem;">
                                    <i class="fas fa-fingerprint me-1"></i> Auto
                                </span>
                            </div>
                            <small class="text-muted" style="font-size:0.75rem;">Format: <strong>MGEDU</strong> (Prefix) + <strong><?= $currentYear ?></strong> (Year) + <strong>001</strong> (Serial)</small>
                        </div>
                    </div>

                    <!-- Center Name -->
                    <div class="col-md-8">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Center / Institute Name <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="center_name" class="form-control mg-input-control" 
                                placeholder="e.g. Prayag Institute of Computer & Skill Development"
                                value="<?= htmlspecialchars($_POST['center_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Center Email -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Center Official Email <span class="mg-required">*</span>
                            </label>
                            <input type="email" name="center_email" class="form-control mg-input-control" 
                                placeholder="center.branch@mgedu.in"
                                value="<?= htmlspecialchars($_POST['center_email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Center Mobile (+91) -->
                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Center Mobile Number <span class="mg-required">*</span>
                            </label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix">+91</span>
                                <input type="tel" name="center_mobile" class="form-control mg-input-control" 
                                    placeholder="8059982049" maxlength="15"
                                    value="<?= htmlspecialchars($_POST['center_mobile'] ?? '') ?>" required>
                            </div>
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
                        <span class="mg-section-sub">Enter 6-digit Postal PIN code to automatically retrieve City and State</span>
                    </div>
                </div>
                <span class="mg-section-tag">India Post API</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <!-- PIN Code Lookup with Clean Single Status Badge -->
                    <div class="col-md-5">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Postal PIN Code <span class="mg-required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="pincode" id="mg_pincode_input" 
                                    class="form-control mg-input-control fw-bold" 
                                    placeholder="e.g. 110001" 
                                    maxlength="6" 
                                    pattern="\d{6}"
                                    value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>" required>
                                <button type="button" class="btn btn-outline-secondary" id="btn_verify_pin" style="border-color:#cbd5e1; font-size:0.82rem; font-weight:600;">
                                    <i class="fas fa-magnifying-glass me-1"></i> Verify
                                </button>
                            </div>
                            <!-- Single Dynamic Status Container (No Colliding Text) -->
                            <div id="pin_status_container" class="mt-1"></div>
                        </div>
                    </div>

                    <!-- City / District -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                City / District <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="city" id="mg_city_input" class="form-control mg-input-control" 
                                placeholder="Autofilled" 
                                value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- State -->
                    <div class="col-md-2 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                State <span class="mg-required">*</span>
                            </label>
                            <input type="text" name="state" id="mg_state_input" class="form-control mg-input-control" 
                                placeholder="Autofilled" 
                                value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Country (India - Locked) -->
                    <div class="col-md-2 col-12">
                        <div class="mg-field-group">
                            <label class="mg-form-label">
                                Country <span class="text-muted">(Default)</span>
                            </label>
                            <input type="text" name="country" class="form-control mg-input-control mg-input-readonly" 
                                value="India" readonly tabindex="-1">
                        </div>
                    </div>

                    <!-- Post Office Branch Suggestion (If multiple returned) -->
                    <div class="col-12" id="postoffice_select_wrap" style="display: none;">
                        <div class="p-2 rounded-2 bg-light border mb-2 d-flex flex-wrap align-items-center gap-2">
                            <span class="text-muted small fw-semibold"><i class="fas fa-envelope-open-text me-1 text-primary"></i> Post Office Locality:</span>
                            <select id="mg_postoffice_select" class="form-select form-select-sm d-inline-block w-auto" style="min-width: 250px;">
                                <option value="">Select post office branch to append to address...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Full Address -->
                    <div class="col-12">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">
                                Complete Center Address <span class="mg-required">*</span>
                            </label>
                            <textarea name="address" id="mg_address_textarea" class="form-control mg-input-control" rows="2" 
                                placeholder="Premises/Shop/Building Name, Street Road, Area, Landmark..." required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
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
                        <span class="mg-section-sub">Classroom capacity, IT equipment, working schedules and amenities</span>
                    </div>
                </div>
                <span class="mg-section-tag">Facilities</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">No. of Classrooms <span class="mg-required">*</span></label>
                            <input type="number" name="classrooms_count" class="form-control mg-input-control" min="1" max="100" 
                                value="<?= htmlspecialchars($_POST['classrooms_count'] ?? '2') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">No. of Computers <span class="mg-required">*</span></label>
                            <input type="number" name="computers_count" class="form-control mg-input-control" min="0" max="500" 
                                value="<?= htmlspecialchars($_POST['computers_count'] ?? '20') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Total Staff Count <span class="mg-required">*</span></label>
                            <input type="number" name="total_staff" class="form-control mg-input-control" min="0" max="100" 
                                value="<?= htmlspecialchars($_POST['total_staff'] ?? '5') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Lab Type <span class="mg-required">*</span></label>
                            <select name="lab_type" class="form-select mg-input-control">
                                <option value="Basic" <?= (($_POST['lab_type'] ?? '') === 'Basic') ? 'selected' : '' ?>>Basic (Standard PC Lab)</option>
                                <option value="Advanced" <?= (($_POST['lab_type'] ?? 'Advanced') === 'Advanced') ? 'selected' : '' ?>>Advanced (High-End Lab)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Working Days -->
                    <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; ?>
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Days: From</label>
                            <select name="working_days_from" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= (($_POST['working_days_from'] ?? 'Monday') === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Days: To</label>
                            <select name="working_days_to" class="form-select mg-input-control">
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $day ?>" <?= (($_POST['working_days_to'] ?? 'Saturday') === $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Hours: From</label>
                            <input type="text" name="working_hours_from" class="form-control mg-input-control" 
                                placeholder="09:00 AM" value="<?= htmlspecialchars($_POST['working_hours_from'] ?? '09:00 AM') ?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Working Hours: To</label>
                            <input type="text" name="working_hours_to" class="form-control mg-input-control" 
                                placeholder="06:00 PM" value="<?= htmlspecialchars($_POST['working_hours_to'] ?? '06:00 PM') ?>">
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
                            $selectedAmenities = $_POST['amenities'] ?? [
                                "High-Speed Internet", "Power Backup (Inverter/Generator)", "RO Pure Drinking Water"
                            ];
                            ?>
                            <?php foreach ($amenitiesList as $amenity): 
                                $isChecked = in_array($amenity, $selectedAmenities);
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
                        <span class="mg-section-sub">Center head information, public helpline number and royalty terms</span>
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
                                placeholder="e.g. Rahul Dhiman"
                                value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Owner Email Address <span class="mg-required">*</span></label>
                            <input type="email" name="owner_email" class="form-control mg-input-control" 
                                placeholder="owner@gmail.com"
                                value="<?= htmlspecialchars($_POST['owner_email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Owner Direct Mobile <span class="mg-required">*</span></label>
                            <div class="mg-input-group">
                                <span class="mg-input-prefix">+91</span>
                                <input type="tel" name="owner_mobile" class="form-control mg-input-control" 
                                    placeholder="8059982049" maxlength="15"
                                    value="<?= htmlspecialchars($_POST['owner_mobile'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Helpline / Enquiry Number</label>
                            <input type="text" name="helpline_number" class="form-control mg-input-control" 
                                placeholder="e.g. 1800-889-1001 or Landline"
                                value="<?= htmlspecialchars($_POST['helpline_number'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mg-field-group">
                            <label class="mg-form-label">Center Royalty Fee (%) <span class="mg-required">*</span></label>
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

        <!-- 5. Verification Documents (PDF Only) -->
        <div class="mg-section-card">
            <div class="mg-section-header">
                <div class="mg-section-header-left">
                    <div class="mg-section-icon coral">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h6 class="mg-section-title">Verification Documents (PDF Format Only)</h6>
                        <span class="mg-section-sub">Statutory identification files for center verification (Max 10MB per PDF)</span>
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
                                    <div class="mg-doc-icon-badge pdf">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="mg-doc-upload-info">
                                        <h6>Owner Aadhar Card</h6>
                                        <span>PDF format only</span>
                                    </div>
                                </div>
                                <div class="mg-file-status-line" id="status_aadhar">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="aadhar_card_doc" id="file_aadhar" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_aadhar')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_aadhar').click()">
                                    <i class="fas fa-upload"></i> Browse PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Owner PAN PDF -->
                    <div class="col-md-4">
                        <div class="mg-doc-upload-card">
                            <div>
                                <div class="mg-doc-upload-top">
                                    <div class="mg-doc-icon-badge pdf">
                                        <i class="fas fa-file-lines"></i>
                                    </div>
                                    <div class="mg-doc-upload-info">
                                        <h6>Owner PAN Card</h6>
                                        <span>PDF format only</span>
                                    </div>
                                </div>
                                <div class="mg-file-status-line" id="status_pan">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="pan_card_doc" id="file_pan" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_pan')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_pan').click()">
                                    <i class="fas fa-upload"></i> Browse PDF
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GST Number & PDF -->
                    <div class="col-md-4">
                        <div class="mg-doc-upload-card">
                            <div>
                                <div class="mg-doc-upload-top">
                                    <div class="mg-doc-icon-badge pdf">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div class="mg-doc-upload-info">
                                        <h6>GST Registration</h6>
                                        <span>Optional business filing</span>
                                    </div>
                                </div>
                                <input type="text" name="gst_number" class="form-control mg-input-control mb-2 text-uppercase" 
                                    style="height:36px; font-size:0.8rem;"
                                    placeholder="GSTIN (Optional)"
                                    value="<?= htmlspecialchars($_POST['gst_number'] ?? '') ?>">
                                <div class="mg-file-status-line" id="status_gst">
                                    <i class="fas fa-check-circle"></i> <span class="file-name"></span>
                                </div>
                            </div>
                            <div class="mg-upload-btn-row">
                                <input type="file" name="gst_certificate_doc" id="file_gst" accept=".pdf,application/pdf" style="display:none;" onchange="handleDocSelected(this, 'status_gst')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_gst').click()">
                                    <i class="fas fa-upload"></i> Upload Certificate (PDF)
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
                        <span class="mg-section-sub">Executive portrait and specimen signature for center records</span>
                    </div>
                </div>
                <span class="mg-section-tag">Media Assets</span>
            </div>

            <div class="mg-section-body">
                <div class="row g-3">
                    <!-- Owner Photo -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-2 border bg-light d-flex align-items-center gap-3">
                            <img src="" id="preview_photo_img" class="mg-img-preview-frame" alt="Owner Photo">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark" style="font-size:0.88rem;">Owner Passport Photo</div>
                                <div class="text-muted small mb-2" style="font-size:0.75rem;">JPG, PNG, or WEBP (Square ratio)</div>
                                <input type="file" name="owner_photo" id="file_photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleImgSelected(this, 'preview_photo_img')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_photo').click()">
                                    <i class="fas fa-camera me-1"></i> Select Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Signature -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-2 border bg-light d-flex align-items-center gap-3">
                            <img src="" id="preview_sig_img" class="mg-img-preview-frame" style="background:#fff; padding:4px;" alt="Signature">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark" style="font-size:0.88rem;">Specimen Signature Image</div>
                                <div class="text-muted small mb-2" style="font-size:0.75rem;">Clear signature on clean white paper</div>
                                <input type="file" name="owner_signature" id="file_sig" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleImgSelected(this, 'preview_sig_img')">
                                <button type="button" class="mg-btn-browse" onclick="document.getElementById('file_sig').click()">
                                    <i class="fas fa-pen-nib me-1"></i> Select Signature
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 pt-3 border-top align-items-center">
                    <div class="col-md-4">
                        <div class="mg-field-group mb-0">
                            <label class="mg-form-label">Initial Franchise Status</label>
                            <select name="status" class="form-select mg-input-control">
                                <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active &bull; Fully Authorized</option>
                                <option value="pending" <?= (($_POST['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending Verification</option>
                                <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Form Footer -->
        <div class="mg-form-footer-bar">
            <div class="text-muted small">
                <i class="fas fa-info-circle me-1 text-primary"></i> Data will be registered immediately to MG Education centralized server.
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-light border px-3" style="font-size:0.88rem; font-weight:600;">Cancel</a>
                <button type="submit" class="mg-btn-save-main">
                    <i class="fas fa-check me-1"></i> Save &amp; Register Center
                </button>
            </div>
        </div>

    </form>
</div>

<!-- India Post API & Interactive Handlers Script -->
<script>
$(document).ready(function() {
    let pinTimer = null;

    // Trigger lookup on 6 digits
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

    // Manual Verify Button click
    $('#btn_verify_pin').on('click', function(e) {
        e.preventDefault();
        const pin = $('#mg_pincode_input').val().trim();
        if (pin.length === 6 && /^\d{6}$/.test(pin)) {
            lookupIndiaPost(pin);
        } else {
            $('#pin_status_container').html('<span class="mg-pin-status-pill error"><i class="fas fa-triangle-exclamation"></i> Please enter a 6-digit PIN code.</span>');
        }
    });

    function lookupIndiaPost(pin) {
        const statusBox = $('#pin_status_container');
        statusBox.html('<span class="mg-pin-status-pill loading"><i class="fas fa-spinner fa-spin"></i> Checking India Post postal API...</span>');

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

                    // Post offices dropdown
                    const sel = $('#mg_postoffice_select');
                    sel.empty();
                    sel.append('<option value="">-- Select Post Office locality to append --</option>');
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
                    statusBox.html('<span class="mg-pin-status-pill error"><i class="fas fa-circle-xmark"></i> No records found on India Post for ' + pin + '. Please enter manually.</span>');
                }
            },
            error: function() {
                statusBox.html('<span class="mg-pin-status-pill error"><i class="fas fa-wifi"></i> India Post API unreachable. Please enter City &amp; State manually.</span>');
            }
        });
    }

    // Auto-check on load if already present
    const curPin = $('#mg_pincode_input').val().trim();
    if (curPin.length === 6 && /^\d{6}$/.test(curPin) && !$('#mg_city_input').val()) {
        lookupIndiaPost(curPin);
    }
});

// Toggle amenity pill active state
function togglePillActive(checkbox) {
    const pill = $(checkbox).closest('.mg-amenity-pill');
    if (checkbox.checked) {
        pill.addClass('active');
    } else {
        pill.removeClass('active');
    }
}

// Handle PDF document selection
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

// Handle image selection
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
</script>

<?php if (!empty($successMessage)): ?>
<script>
Swal.fire({
    title: 'Center Registered!',
    text: <?= json_encode($successMessage) ?>,
    icon: 'success',
    confirmButtonColor: '#071d33',
    confirmButtonText: 'Go to Centers List'
}).then(() => {
    window.location.href = 'index.php?msg=created';
});
</script>
<?php endif; ?>

<?php 
// Include Admin Footer
require_once dirname(__DIR__) . '/footer.php'; 
?>
