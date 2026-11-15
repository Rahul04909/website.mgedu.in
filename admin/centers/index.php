<?php
/**
 * MG Education & Social Development Organization
 * Authorized Study Centers Management Panel
 * 
 * Features:
 * - Live KPI Overview Cards (Total, Active, Classrooms, Computers)
 * - Dynamic Filtering & Instant Search (Name, Code, City, State, Status)
 * - Complete Responsive Data Table with Quick Actions
 * - Comprehensive View Center Modal with PDF Documents & Owner Signatures
 * - Status Toggle & Delete Actions with SweetAlert2 Confirmation
 */

// Include Admin Top Header (Enforces JWT Auth & Navigation)
require_once dirname(__DIR__) . '/header.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';

$pdo = getDB();
$notification = '';
$notificationType = 'success';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_center'])) {
    $deleteId = (int)($_POST['center_id'] ?? 0);
    if ($deleteId > 0 && $pdo) {
        try {
            $delStmt = $pdo->prepare("DELETE FROM `centers` WHERE `id` = :id");
            $delStmt->execute([':id' => $deleteId]);
            $notification = "Study Center has been deleted successfully.";
            $notificationType = 'success';
        } catch (Throwable $e) {
            $notification = "Error deleting center: " . $e->getMessage();
            $notificationType = 'danger';
        }
    }
}

// Handle Status Toggle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle_status'])) {
    $toggleId = (int)($_POST['center_id'] ?? 0);
    $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'inactive', 'pending']) ? $_POST['new_status'] : 'active';
    if ($toggleId > 0 && $pdo) {
        try {
            $togStmt = $pdo->prepare("UPDATE `centers` SET `status` = :status WHERE `id` = :id");
            $togStmt->execute([':status' => $newStatus, ':id' => $toggleId]);
            $notification = "Center status updated to " . ucfirst($newStatus) . ".";
            $notificationType = 'success';
        } catch (Throwable $e) {
            $notification = "Error updating status: " . $e->getMessage();
            $notificationType = 'danger';
        }
    }
}

// Fetch KPI Stats
$stats = [
    'total'       => 0,
    'active'      => 0,
    'classrooms'  => 0,
    'computers'   => 0
];
$centersList = [];
$uniqueStates = [];

if ($pdo) {
    try {
        // Query KPI counts
        $statRow = $pdo->query("SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) AS active_cnt,
            SUM(`classrooms_count`) AS total_classrooms,
            SUM(`computers_count`) AS total_computers
            FROM `centers`")->fetch(PDO::FETCH_ASSOC);

        if ($statRow) {
            $stats['total']      = (int)($statRow['total'] ?? 0);
            $stats['active']     = (int)($statRow['active_cnt'] ?? 0);
            $stats['classrooms'] = (int)($statRow['total_classrooms'] ?? 0);
            $stats['computers']  = (int)($statRow['total_computers'] ?? 0);
        }

        // Query Centers List
        $centersStmt = $pdo->query("SELECT * FROM `centers` ORDER BY `id` DESC");
        $centersList = $centersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Extract unique states for filter
        foreach ($centersList as $c) {
            if (!empty($c['state']) && !in_array($c['state'], $uniqueStates)) {
                $uniqueStates[] = $c['state'];
            }
        }
        sort($uniqueStates);

    } catch (Throwable $e) {
        // Table may not exist yet
    }
}
?>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- Notification Alert -->
    <?php if (!empty($notification)): ?>
        <div class="alert alert-<?= $notificationType ?> alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-circle-check fa-lg me-2"></i> <?= htmlspecialchars($notification) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Cards (Quick Features Solid Palette) -->
    <div class="row g-3 mb-4">
        <!-- Total Centers -->
        <div class="col-xl-3 col-sm-6">
            <div class="mg-stat-card vermilion">
                <div class="mg-stat-content">
                    <div class="mg-stat-number"><?= number_format($stats['total']) ?><span class="mg-stat-suffix">+</span></div>
                    <div class="mg-stat-title">Authorized Centers</div>
                    <div class="mg-stat-sublabel">Total Network Count</div>
                </div>
                <div class="mg-stat-badge-icon">
                    <i class="fas fa-building-columns"></i>
                </div>
            </div>
        </div>

        <!-- Active Centers -->
        <div class="col-xl-3 col-sm-6">
            <div class="mg-stat-card emerald">
                <div class="mg-stat-content">
                    <div class="mg-stat-number"><?= number_format($stats['active']) ?></div>
                    <div class="mg-stat-title">Active &amp; Verified</div>
                    <div class="mg-stat-sublabel">Operational Branches</div>
                </div>
                <div class="mg-stat-badge-icon">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
        </div>

        <!-- Total Classrooms -->
        <div class="col-xl-3 col-sm-6">
            <div class="mg-stat-card purple">
                <div class="mg-stat-content">
                    <div class="mg-stat-number"><?= number_format($stats['classrooms']) ?></div>
                    <div class="mg-stat-title">IT Classrooms</div>
                    <div class="mg-stat-sublabel">Academic Infrastructure</div>
                </div>
                <div class="mg-stat-badge-icon">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
            </div>
        </div>

        <!-- Total Computers -->
        <div class="col-xl-3 col-sm-6">
            <div class="mg-stat-card cyan">
                <div class="mg-stat-content">
                    <div class="mg-stat-number"><?= number_format($stats['computers']) ?><span class="mg-stat-suffix">+</span></div>
                    <div class="mg-stat-title">Computer Lab PCs</div>
                    <div class="mg-stat-sublabel">Student Terminal Seats</div>
                </div>
                <div class="mg-stat-badge-icon">
                    <i class="fas fa-desktop"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card with Toolbar -->
    <div class="mg-table-card">
        <!-- Toolbar with Filters, Search & Action Buttons -->
        <div class="mg-table-toolbar">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search -->
                <div class="mg-search-input flex-grow-1" style="max-width: 340px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="centerTableSearch" class="form-control mg-input-control" placeholder="Search center, code, city, owner...">
                </div>

                <!-- State Filter -->
                <select id="stateFilter" class="form-select mg-input-control" style="width: auto; min-width: 140px;">
                    <option value="">All States</option>
                    <?php foreach ($uniqueStates as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Status Filter -->
                <select id="statusFilter" class="form-select mg-input-control" style="width: auto; min-width: 130px;">
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="pending">Pending Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>

                <!-- Lab Type Filter -->
                <select id="labFilter" class="form-select mg-input-control" style="width: auto; min-width: 130px;">
                    <option value="">All Lab Types</option>
                    <option value="Basic">Basic Labs</option>
                    <option value="Advanced">Advanced Labs</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="../../sql-tables/import-centers.php" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-3 px-3 py-2 fw-semibold" title="Database Migration">
                    <i class="fas fa-database"></i>
                    <span>Migration</span>
                </a>
                <a href="add-center.php" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-bold text-white shadow-sm" style="background: var(--mg-navy); border-color: var(--mg-navy);">
                    <i class="fas fa-circle-plus"></i>
                    <span>Add New Center</span>
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-responsive">
            <table class="table mg-centers-table align-middle" id="centersDataTable">
                <thead>
                    <tr>
                        <th style="width: 130px;">Center Code</th>
                        <th>Institute &amp; Contact</th>
                        <th>Location</th>
                        <th>Infrastructure</th>
                        <th>Owner &amp; Helpline</th>
                        <th>Royalty</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($centersList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-building-circle-exclamation fa-3x mb-3 text-warning"></i>
                                    <h5>No Authorized Centers Found</h5>
                                    <p class="mb-3">Start by running migration or register your first authorized franchise branch.</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="../../sql-tables/import-centers.php" class="btn btn-sm btn-outline-primary">Run Migration</a>
                                        <a href="add-center.php" class="btn btn-sm btn-primary">Add New Center</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($centersList as $center): 
                            $statusClass = $center['status'] === 'active' ? 'active' : ($center['status'] === 'pending' ? 'pending' : 'inactive');
                            $centerJson = htmlspecialchars(json_encode($center), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="center-row" 
                                data-name="<?= strtolower(htmlspecialchars($center['center_name'])) ?>"
                                data-code="<?= strtolower(htmlspecialchars($center['center_code'])) ?>"
                                data-city="<?= strtolower(htmlspecialchars($center['city'])) ?>"
                                data-state="<?= htmlspecialchars($center['state']) ?>"
                                data-owner="<?= strtolower(htmlspecialchars($center['owner_name'])) ?>"
                                data-status="<?= htmlspecialchars($center['status']) ?>"
                                data-lab="<?= htmlspecialchars($center['lab_type']) ?>">
                                
                                <!-- Code -->
                                <td>
                                    <span class="mg-code-pill"><?= htmlspecialchars($center['center_code']) ?></span>
                                </td>

                                <!-- Institute & Email -->
                                <td>
                                    <div class="mg-center-title"><?= htmlspecialchars($center['center_name']) ?></div>
                                    <div class="mg-center-sub">
                                        <i class="far fa-envelope me-1 text-primary"></i> <?= htmlspecialchars($center['center_email']) ?>
                                    </div>
                                    <div class="mg-center-sub">
                                        <i class="fas fa-phone me-1 text-success"></i> <?= htmlspecialchars($center['center_mobile']) ?>
                                    </div>
                                </td>

                                <!-- Location -->
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($center['city']) ?>, <?= htmlspecialchars($center['state']) ?>
                                    </div>
                                    <div class="mg-center-sub">
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-map-pin text-danger me-1"></i> PIN: <?= htmlspecialchars($center['pincode']) ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Infrastructure -->
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" title="Classrooms">
                                            <i class="fas fa-chalkboard me-1"></i> <?= (int)$center['classrooms_count'] ?> Class
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Computers">
                                            <i class="fas fa-desktop me-1"></i> <?= (int)$center['computers_count'] ?> PCs
                                        </span>
                                    </div>
                                    <div class="mg-center-sub">
                                        <span class="badge <?= $center['lab_type'] === 'Advanced' ? 'bg-purple-subtle text-purple' : 'bg-secondary-subtle text-secondary' ?>" style="font-size:0.7rem;">
                                            <i class="fas fa-microchip me-1"></i> <?= htmlspecialchars($center['lab_type']) ?> Lab
                                        </span>
                                    </div>
                                </td>

                                <!-- Owner Info -->
                                <td>
                                    <div class="fw-bold text-dark">
                                        <i class="far fa-user me-1 text-muted"></i> <?= htmlspecialchars($center['owner_name']) ?>
                                    </div>
                                    <?php if (!empty($center['helpline_number'])): ?>
                                        <div class="mg-center-sub text-muted">
                                            <i class="fas fa-headset me-1 text-warning"></i> <?= htmlspecialchars($center['helpline_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Royalty -->
                                <td>
                                    <span class="badge bg-amber-subtle text-amber fw-bold fs-7" style="color: #b45309; background: #fef3c7; border: 1px solid #fde68a;">
                                        <?= number_format((float)$center['royalty_percentage'], 2) ?>%
                                    </span>
                                </td>

                                <!-- Status -->
                                <td>
                                    <span class="mg-status-badge <?= $statusClass ?>">
                                        <span class="dot"></span>
                                        <?= ucfirst(htmlspecialchars($center['status'])) ?>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="mg-table-actions justify-content-end">
                                        <!-- View Modal Trigger -->
                                        <button type="button" class="mg-btn-sm-action view" title="View Full Center Profile" 
                                            onclick='openViewCenterModal(<?= $centerJson ?>)'>
                                            <i class="far fa-eye"></i>
                                        </button>

                                        <!-- Edit Center -->
                                        <a href="edit-center.php?id=<?= $center['id'] ?>" class="mg-btn-sm-action edit" title="Edit Center">
                                            <i class="far fa-pen-to-square"></i>
                                        </a>

                                        <!-- Toggle Status -->
                                        <button type="button" class="mg-btn-sm-action" title="Change Status" 
                                            onclick="toggleCenterStatus(<?= $center['id'] ?>, '<?= $center['status'] ?>', '<?= htmlspecialchars($center['center_name'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-power-off <?= $center['status'] === 'active' ? 'text-success' : 'text-muted' ?>"></i>
                                        </button>

                                        <!-- Delete Center -->
                                        <button type="button" class="mg-btn-sm-action delete" title="Delete Center" 
                                            onclick="confirmDeleteCenter(<?= $center['id'] ?>, '<?= htmlspecialchars($center['center_name'], ENT_QUOTES) ?>')">
                                            <i class="far fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================================================
     VIEW CENTER DETAILS MODAL (With PDF View & Owner Signature Preview)
     ========================================================================== -->
<div class="modal fade" id="viewCenterModal" tabindex="-1" aria-labelledby="viewCenterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header with Colorful Banner -->
            <div class="modal-header text-white" id="modalHeaderWrap" style="background: linear-gradient(135deg, var(--mg-navy) 0%, var(--mg-navy-light) 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="mg-chevron-icon-hole" style="width: 48px; height: 48px;">
                        <i class="fas fa-building-columns text-primary fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCenterName">Center Profile</h5>
                        <div class="small opacity-75">
                            Code: <span id="modalCenterCode" class="fw-bold font-monospace"></span> &bull; 
                            Status: <span id="modalCenterStatus" class="badge bg-light text-dark text-capitalize"></span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Basic Contact Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-muted small text-uppercase fw-bold mb-1"><i class="fas fa-envelope text-primary me-1"></i> Center Email</div>
                            <div class="fw-semibold text-dark text-break" id="modalCenterEmail">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-muted small text-uppercase fw-bold mb-1"><i class="fas fa-phone text-success me-1"></i> Center Mobile</div>
                            <div class="fw-semibold text-dark" id="modalCenterMobile">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="text-muted small text-uppercase fw-bold mb-1"><i class="fas fa-key text-warning me-1"></i> Portal Security</div>
                            <div class="fw-semibold text-dark"><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-shield-halved me-1"></i> Configured (Bcrypt)</span></div>
                        </div>
                    </div>
                </div>

                <!-- Complete Address Section -->
                <div class="p-3 rounded-3 bg-light border mb-4">
                    <div class="text-muted small text-uppercase fw-bold mb-1">
                        <i class="fas fa-map-location-dot text-danger me-1"></i> Center Complete Address
                    </div>
                    <div class="fw-semibold text-dark mb-1" id="modalCenterAddress">-</div>
                    <div class="small text-muted">
                        City: <strong id="modalCenterCity"></strong> &bull; 
                        State: <strong id="modalCenterState"></strong> &bull; 
                        PIN: <strong id="modalCenterPincode"></strong> &bull; 
                        Country: <strong>India</strong>
                    </div>
                </div>

                <!-- Infrastructure & Schedule Grid -->
                <h6 class="fw-bold text-uppercase text-muted mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    <i class="fas fa-network-wired text-purple me-1"></i> Infrastructure &amp; Academic Setup
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-sm-3 col-6">
                        <div class="text-center p-2 rounded-3 border bg-white">
                            <div class="text-muted small">Classrooms</div>
                            <div class="h5 fw-bold text-primary mb-0" id="modalClassrooms">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="text-center p-2 rounded-3 border bg-white">
                            <div class="text-muted small">Computers</div>
                            <div class="h5 fw-bold text-cyan mb-0" id="modalComputers">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="text-center p-2 rounded-3 border bg-white">
                            <div class="text-muted small">Total Staff</div>
                            <div class="h5 fw-bold text-success mb-0" id="modalStaff">0</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="text-center p-2 rounded-3 border bg-white">
                            <div class="text-muted small">Lab Type</div>
                            <div class="h5 fw-bold text-purple mb-0" id="modalLabType">-</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted p-2 rounded-2 bg-light border d-flex flex-wrap justify-content-between">
                            <span><i class="far fa-calendar-days me-1"></i> Working Days: <strong id="modalWorkingDays">-</strong></span>
                            <span><i class="far fa-clock me-1"></i> Working Hours: <strong id="modalWorkingHours">-</strong></span>
                            <span><i class="fas fa-percent me-1"></i> Royalty: <strong id="modalRoyalty">-</strong></span>
                        </div>
                    </div>
                    <!-- Amenities Badges -->
                    <div class="col-12">
                        <div class="text-muted small fw-bold mb-1">Available Amenities:</div>
                        <div id="modalAmenitiesList" class="d-flex flex-wrap gap-1"></div>
                    </div>
                </div>

                <!-- Owner Details & Assets -->
                <h6 class="fw-bold text-uppercase text-muted mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    <i class="fas fa-user-tie text-amber me-1"></i> Center Owner &amp; Executive Verification
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="fw-bold text-dark fs-6" id="modalOwnerName">-</div>
                            <div class="small text-muted mb-1"><i class="far fa-envelope me-1"></i> <span id="modalOwnerEmail">-</span></div>
                            <div class="small text-muted mb-1"><i class="fas fa-phone me-1"></i> <span id="modalOwnerMobile">-</span></div>
                            <div class="small text-muted" id="modalHelplineWrap"><i class="fas fa-headset me-1 text-warning"></i> Helpline: <span id="modalHelpline">-</span></div>
                            <div class="small text-muted mt-2 pt-2 border-top" id="modalGstWrap">GSTIN: <strong id="modalGst">-</strong></div>
                        </div>
                    </div>

                    <!-- Owner Photo & Signature Previews -->
                    <div class="col-md-5">
                        <div class="d-flex gap-2">
                            <div class="text-center border rounded-3 p-2 flex-fill bg-white">
                                <div class="text-muted small mb-1">Owner Photo</div>
                                <img src="../../src/images/user-avtar.png" id="modalOwnerPhoto" class="rounded-3 border" style="width: 80px; height: 80px; object-fit: cover;" alt="Owner">
                            </div>
                            <div class="text-center border rounded-3 p-2 flex-fill bg-white">
                                <div class="text-muted small mb-1">Specimen Signature</div>
                                <img src="" id="modalOwnerSig" class="rounded-3 border" style="width: 80px; height: 80px; object-fit: contain; background: #fff;" alt="Signature">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legal PDF Documents Section -->
                <h6 class="fw-bold text-uppercase text-muted mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    <i class="fas fa-file-pdf text-danger me-1"></i> Verification Documents (PDF Downloads)
                </h6>
                <div class="row g-2" id="modalDocsRow">
                    <!-- Aadhar PDF -->
                    <div class="col-md-4">
                        <div class="p-2 border rounded-3 text-center bg-light" id="modalAadharBox">
                            <i class="fas fa-file-pdf text-danger fa-2x mb-1"></i>
                            <div class="small fw-bold">Aadhar Card</div>
                            <a href="#" id="modalAadharLink" target="_blank" class="btn btn-sm btn-outline-danger mt-1 w-100" style="font-size: 0.75rem;">
                                <i class="fas fa-external-link-alt me-1"></i> View PDF
                            </a>
                        </div>
                    </div>
                    <!-- PAN PDF -->
                    <div class="col-md-4">
                        <div class="p-2 border rounded-3 text-center bg-light" id="modalPanBox">
                            <i class="fas fa-file-pdf text-danger fa-2x mb-1"></i>
                            <div class="small fw-bold">PAN Card</div>
                            <a href="#" id="modalPanLink" target="_blank" class="btn btn-sm btn-outline-danger mt-1 w-100" style="font-size: 0.75rem;">
                                <i class="fas fa-external-link-alt me-1"></i> View PDF
                            </a>
                        </div>
                    </div>
                    <!-- GST PDF -->
                    <div class="col-md-4">
                        <div class="p-2 border rounded-3 text-center bg-light" id="modalGstBox">
                            <i class="fas fa-file-pdf text-danger fa-2x mb-1"></i>
                            <div class="small fw-bold">GST Certificate</div>
                            <a href="#" id="modalGstLink" target="_blank" class="btn btn-sm btn-outline-danger mt-1 w-100" style="font-size: 0.75rem;">
                                <i class="fas fa-external-link-alt me-1"></i> View PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light">
                <a href="#" id="modalEditBtn" class="btn btn-warning fw-bold">
                    <i class="far fa-pen-to-square me-1"></i> Edit This Center
                </a>
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Action Forms for Delete & Toggle Status -->
<form id="formDeleteCenter" method="POST" style="display:none;">
    <input type="hidden" name="action_delete_center" value="1">
    <input type="hidden" name="center_id" id="deleteCenterId">
</form>

<form id="formToggleStatus" method="POST" style="display:none;">
    <input type="hidden" name="action_toggle_status" value="1">
    <input type="hidden" name="center_id" id="toggleCenterId">
    <input type="hidden" name="new_status" id="toggleNewStatus">
</form>

<!-- Table Filtering & SweetAlert2 Action Script -->
<script>
$(document).ready(function() {
    // Instant Table Search and Dropdown Filter
    function filterCentersTable() {
        const query = $('#centerTableSearch').val().toLowerCase().trim();
        const selectedState = $('#stateFilter').val();
        const selectedStatus = $('#statusFilter').val();
        const selectedLab = $('#labFilter').val();

        $('.center-row').each(function() {
            const row = $(this);
            const name = row.data('name') || '';
            const code = row.data('code') || '';
            const city = row.data('city') || '';
            const state = row.data('state') || '';
            const owner = row.data('owner') || '';
            const status = row.data('status') || '';
            const lab = row.data('lab') || '';

            const matchQuery = !query || name.indexOf(query) !== -1 || code.indexOf(query) !== -1 || city.indexOf(query) !== -1 || owner.indexOf(query) !== -1;
            const matchState = !selectedState || state === selectedState;
            const matchStatus = !selectedStatus || status === selectedStatus;
            const matchLab = !selectedLab || lab === selectedLab;

            if (matchQuery && matchState && matchStatus && matchLab) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    $('#centerTableSearch').on('keyup input', filterCentersTable);
    $('#stateFilter, #statusFilter, #labFilter').on('change', filterCentersTable);
});

// Open View Center Modal with populated data
function openViewCenterModal(center) {
    $('#modalCenterName').text(center.center_name);
    $('#modalCenterCode').text(center.center_code);
    $('#modalCenterStatus').text(center.status);

    $('#modalCenterEmail').text(center.center_email);
    $('#modalCenterMobile').text(center.center_mobile);
    $('#modalCenterAddress').text(center.address);
    $('#modalCenterCity').text(center.city);
    $('#modalCenterState').text(center.state);
    $('#modalCenterPincode').text(center.pincode);

    $('#modalClassrooms').text(center.classrooms_count || 0);
    $('#modalComputers').text(center.computers_count || 0);
    $('#modalStaff').text(center.total_staff || 0);
    $('#modalLabType').text((center.lab_type || 'Basic') + ' Lab');

    $('#modalWorkingDays').text((center.working_days_from || 'Mon') + ' to ' + (center.working_days_to || 'Sat'));
    $('#modalWorkingHours').text((center.working_hours_from || '09:00 AM') + ' - ' + (center.working_hours_to || '06:00 PM'));
    $('#modalRoyalty').text((center.royalty_percentage || '0') + '%');

    // Amenities
    const amenitiesWrap = $('#modalAmenitiesList');
    amenitiesWrap.empty();
    if (center.amenities) {
        try {
            const list = JSON.parse(center.amenities);
            if (Array.isArray(list) && list.length > 0) {
                list.forEach(function(item) {
                    amenitiesWrap.append('<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.75rem;"><i class="fas fa-check-circle me-1"></i> ' + item + '</span>');
                });
            } else {
                amenitiesWrap.html('<span class="text-muted small">No specific amenities listed.</span>');
            }
        } catch(e) {
            amenitiesWrap.html('<span class="text-muted small">Standard basic setup.</span>');
        }
    } else {
        amenitiesWrap.html('<span class="text-muted small">Standard basic setup.</span>');
    }

    // Owner info
    $('#modalOwnerName').text(center.owner_name);
    $('#modalOwnerEmail').text(center.owner_email);
    $('#modalOwnerMobile').text(center.owner_mobile);
    if (center.helpline_number) {
        $('#modalHelpline').text(center.helpline_number);
        $('#modalHelplineWrap').show();
    } else {
        $('#modalHelplineWrap').hide();
    }
    if (center.gst_number) {
        $('#modalGst').text(center.gst_number);
        $('#modalGstWrap').show();
    } else {
        $('#modalGstWrap').hide();
    }

    // Photo & Signature
    const photoUrl = center.owner_photo ? ('../../' + center.owner_photo) : '../../src/images/user-avtar.png';
    $('#modalOwnerPhoto').attr('src', photoUrl);

    if (center.owner_signature) {
        $('#modalOwnerSig').attr('src', '../../' + center.owner_signature).show();
    } else {
        $('#modalOwnerSig').attr('src', '').hide();
    }

    // PDF Documents
    if (center.aadhar_card_doc) {
        $('#modalAadharLink').attr('href', '../../' + center.aadhar_card_doc);
        $('#modalAadharBox').show();
    } else {
        $('#modalAadharBox').hide();
    }

    if (center.pan_card_doc) {
        $('#modalPanLink').attr('href', '../../' + center.pan_card_doc);
        $('#modalPanBox').show();
    } else {
        $('#modalPanBox').hide();
    }

    if (center.gst_certificate_doc) {
        $('#modalGstLink').attr('href', '../../' + center.gst_certificate_doc);
        $('#modalGstBox').show();
    } else {
        $('#modalGstBox').hide();
    }

    // Edit button URL
    $('#modalEditBtn').attr('href', 'edit-center.php?id=' + center.id);

    // Show modal
    const modalEl = new bootstrap.Modal(document.getElementById('viewCenterModal'));
    modalEl.show();
}

// Confirm Delete Center
function confirmDeleteCenter(id, name) {
    Swal.fire({
        title: 'Delete Study Center?',
        text: 'Are you sure you want to delete "' + name + '"? This will remove all franchise records permanently.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d93829',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete Center'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#deleteCenterId').val(id);
            $('#formDeleteCenter').submit();
        }
    });
}

// Toggle Center Status (Active / Inactive)
function toggleCenterStatus(id, currentStatus, name) {
    const nextStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const actionWord = nextStatus === 'active' ? 'activate' : 'deactivate';

    Swal.fire({
        title: (nextStatus === 'active' ? 'Activate' : 'Deactivate') + ' Center?',
        text: 'Do you want to ' + actionWord + ' "' + name + '"?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nextStatus === 'active' ? '#10b981' : '#f59e0b',
        confirmButtonText: 'Yes, Change Status'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#toggleCenterId').val(id);
            $('#toggleNewStatus').val(nextStatus);
            $('#formToggleStatus').submit();
        }
    });
}
</script>

<?php 
// Include Admin Footer
require_once dirname(__DIR__) . '/footer.php'; 
?>

