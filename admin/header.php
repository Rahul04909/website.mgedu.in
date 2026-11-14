<?php
// Load Environment Variables (.env) & Global Config
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Enforce JWT Admin Authentication across all admin pages
$currentAdmin = requireAdminAuth();
$adminName   = htmlspecialchars($currentAdmin['name'] ?? 'Admin User');
$adminRole   = htmlspecialchars($currentAdmin['role'] ?? 'Administrator');
$adminEmail  = htmlspecialchars($currentAdmin['email'] ?? 'admin@mgedu.in');

// Calculate relative path prefixes so header works both from /admin/ and subdirectories like /admin/centers/
$isSubdir    = (basename(dirname($_SERVER['SCRIPT_FILENAME'])) !== 'admin');
$adminPrefix = $isSubdir ? '../' : './';
$sitePrefix  = $isSubdir ? '../../' : '../';

$rawAvatar   = !empty($currentAdmin['profile_pic']) ? $currentAdmin['profile_pic'] : './src/images/user-avtar.png';
$adminAvatar = (strpos($rawAvatar, './') === 0) ? ($adminPrefix . substr($rawAvatar, 2)) : htmlspecialchars($rawAvatar);

$currentPage = basename($_SERVER['SCRIPT_NAME']);
$currentRelPath = $isSubdir ? (basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' . $currentPage) : $currentPage;

$menuItems = [
    [
        "menuTitle" => "Dashboard",
        "icon"      => "fas fa-tachometer-alt",
        "theme"     => "theme-cyan",
        "url"       => "index.php"
    ],
    [
        "menuTitle" => "Academics & Courses",
        "icon"      => "fas fa-graduation-cap",
        "theme"     => "theme-purple",
        "pages"     => [
            ["title" => "All Courses",          "url" => "courses.php"],
            ["title" => "Add New Course",       "url" => "course-create.php"],
            ["title" => "Course Streams",       "url" => "categories.php"],
            ["title" => "Study Material Kits",  "url" => "study-materials.php"]
        ],
    ],
    [
        "menuTitle" => "Students & Admission",
        "icon"      => "fas fa-user-graduate",
        "theme"     => "theme-amber",
        "pages"     => [
            ["title" => "Online Applications",  "url" => "applications.php"],
            ["title" => "Enrolled Students",    "url" => "students.php"],
            ["title" => "Issue Admit Cards",    "url" => "admit-cards.php"],
            ["title" => "Results & Marksheets", "url" => "results.php"],
            ["title" => "Student Verification", "url" => "verifications.php"]
        ],
    ],
    [
        "menuTitle" => "Study Centers Network",
        "icon"      => "fas fa-building-columns",
        "theme"     => "theme-vermilion",
        "pages"     => [
            ["title" => "All Authorized Centers", "url" => "centers/index.php"],
            ["title" => "Add New Center",         "url" => "centers/add-center.php"],
            ["title" => "Franchise Verification",  "url" => "center-verify.php"],
            ["title" => "Affiliation Requests",    "url" => "center-requests.php"]
        ],
    ],
    [
        "menuTitle" => "Internships & Career",
        "icon"      => "fas fa-briefcase",
        "theme"     => "theme-emerald",
        "pages"     => [
            ["title" => "Active Internships",     "url" => "internships.php"],
            ["title" => "Candidate Submissions",  "url" => "intern-applicants.php"]
        ],
    ],
    [
        "menuTitle" => "System & Settings",
        "icon"      => "fas fa-sliders",
        "theme"     => "theme-navy",
        "pages"     => [
            ["title" => "Admin Profile",          "url" => "profile.php"],
            ["title" => "Website Notifications",  "url" => "notices.php"],
            ["title" => "General Settings",       "url" => "settings.php"]
        ],
    ]
];

$active_pageInfo = null;
foreach ($menuItems as $menuItem) {
    if (!empty($menuItem['pages'])) {
        foreach ($menuItem['pages'] as $page) {
            if ($currentRelPath === $page['url'] || $currentPage === basename($page['url'])) {
                $active_pageInfo = [
                    "breadcrumb_Items" => [
                        ["title" => $menuItem['menuTitle'], "url" => "#"],
                        ["title" => $page['title'], "url" => $page['url']]
                    ],
                    "page_title" => $page['title'],
                    "active_menu" => $menuItem,
                    "active_page" => $page
                ];
                break 2;
            }
        }
    } elseif (!empty($menuItem['url']) && ($currentRelPath === $menuItem['url'] || $currentPage === $menuItem['url'])) {
        $active_pageInfo = [
            "breadcrumb_Items" => [
                ["title" => $menuItem['menuTitle'], "url" => $menuItem['url']]
            ],
            "page_title" => $menuItem['menuTitle'],
            "active_menu" => $menuItem,
            "active_page" => null
        ];
        break;
    }
}

$breadcrumb_Items = $active_pageInfo['breadcrumb_Items'] ?? [];
$page_title = $active_pageInfo['page_title'] ?? '';
$active_menu = $active_pageInfo['active_menu'] ?? null;
$active_page = $active_pageInfo['active_page'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="icon" href="<?= $sitePrefix ?>favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <!-- MG Education Colorful Sidebar Stylesheet (Quick Features Theme) -->
    <link rel="stylesheet" href="<?= $adminPrefix ?>assets/css/sidebar-colorful.css">
    <!-- MG Education Quick Features Dashboard Stat Cards Stylesheet -->
    <link rel="stylesheet" href="<?= $adminPrefix ?>assets/css/dashboard-stats.css">
    <!-- MG Education Centers Management & Form Stylesheet -->
    <link rel="stylesheet" href="<?= $adminPrefix ?>assets/css/centers.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</head>

<body class="hold-transition sidebar-mini layout-footer-fixed sidebar-no-expand">
    <!-- Body started -->
    <div class="wrapper">
        <!-- Wrapper started -->

        <!-- Top Navbar: Left Sidebar Toggle & Right Profile Dropdown -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light mg-top-navbar">
            <!-- Left: Sidebar Collapse Open / Close Button Only -->
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <button type="button" class="btn mg-sidebar-toggle-btn" id="mgSidebarToggle" title="Toggle Sidebar">
                        <i class="fas fa-bars-staggered"></i>
                    </button>
                </li>
            </ul>

            <!-- Right: Admin Profile Avatar & Dropdown Menu Only -->
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item dropdown mg-profile-dropdown">
                    <a class="nav-link mg-profile-trigger" href="#" id="mgProfileMenuLink" role="button" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="mg-nav-avatar-wrap">
                            <img src="<?= $adminAvatar ?>" alt="<?= $adminName ?>" class="mg-nav-avatar">
                            <span class="mg-nav-online-dot"></span>
                        </div>
                        <div class="mg-nav-user-text d-none d-md-flex flex-column text-left">
                            <span class="mg-nav-user-name"><?= $adminName ?></span>
                            <span class="mg-nav-user-role"><?= $adminRole ?></span>
                        </div>
                        <i class="fas fa-chevron-down mg-nav-chevron"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-end mg-profile-dropdown-menu" aria-labelledby="mgProfileMenuLink">
                        <!-- Dropdown User Card -->
                        <div class="mg-dropdown-user-header">
                            <img src="<?= $adminAvatar ?>" alt="<?= $adminName ?>" class="mg-dropdown-header-avatar">
                            <div class="mg-dropdown-header-info">
                                <h6 class="mg-dropdown-header-name"><?= $adminName ?></h6>
                                <span class="mg-dropdown-header-email"><?= $adminEmail ?></span>
                                <span class="mg-dropdown-badge"><i class="fas fa-shield-alt"></i> <?= $adminRole ?></span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <!-- Navigation Links -->
                        <a href="<?= $adminPrefix ?>profile.php" class="dropdown-item mg-dropdown-item">
                            <i class="fas fa-user-circle text-primary"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="<?= $adminPrefix ?>settings.php" class="dropdown-item mg-dropdown-item">
                            <i class="fas fa-sliders-h text-info"></i>
                            <span>General Settings</span>
                        </a>
                        <a href="<?= $adminPrefix ?>notices.php" class="dropdown-item mg-dropdown-item">
                            <i class="fas fa-bell text-warning"></i>
                            <span>Website Notices</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <!-- Logout Action -->
                        <a href="javascript:void(0);" onclick="logout()" class="dropdown-item mg-dropdown-item text-danger">
                            <i class="fas fa-power-off text-danger"></i>
                            <span class="font-weight-bold">Logout Session</span>
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Live Executive Date & Time Topbar (Replacing Overview & Analytics Header) -->
        <div class="mg-header-topbar">
            <div class="mg-datetime-banner">
                <div class="mg-datetime-left">
                    <div class="mg-datetime-card">
                        <span class="mg-datetime-icon-circle date-icon"><i class="far fa-calendar-alt"></i></span>
                        <div class="mg-datetime-info">
                            <span class="mg-datetime-label">Today's Date</span>
                            <span class="mg-datetime-val" id="mg-live-date"><?= date('l, d F Y') ?></span>
                        </div>
                    </div>
                    <div class="mg-datetime-card">
                        <span class="mg-datetime-icon-circle time-icon"><i class="far fa-clock"></i></span>
                        <div class="mg-datetime-info">
                            <span class="mg-datetime-label">Live Time (IST)</span>
                            <span class="mg-datetime-val" id="mg-live-time"><?= date('h:i:s A') ?></span>
                        </div>
                    </div>
                </div>
                <div class="mg-datetime-right">
                    <div class="mg-live-status-pill">
                        <span class="mg-live-dot"></span>
                        <span>MG Portal Live &bull; Session 2026-27</span>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function updateAdminClock() {
                const now = new Date();
                const optionsDate = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
                const dateStr = now.toLocaleDateString('en-GB', optionsDate);
                const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                const dateEl = document.getElementById('mg-live-date');
                const timeEl = document.getElementById('mg-live-time');
                if (dateEl) dateEl.textContent = dateStr;
                if (timeEl) timeEl.textContent = timeStr;
            }
            setInterval(updateAdminClock, 1000);
            updateAdminClock();

            // Smooth Sidebar Collapse / Expand Toggle
            $(document).ready(function() {
                if (localStorage.getItem('mg_sidebar_collapsed') === 'true') {
                    $('body').addClass('sidebar-collapse');
                    $('#mgSidebarToggle i').removeClass('fa-bars-staggered').addClass('fa-bars');
                }

                $('#mgSidebarToggle').on('click', function(e) {
                    e.preventDefault();
                    $('body').toggleClass('sidebar-collapse');
                    const isCollapsed = $('body').hasClass('sidebar-collapse');
                    if (isCollapsed) {
                        $(this).find('i').removeClass('fa-bars-staggered').addClass('fa-bars');
                    } else {
                        $(this).find('i').removeClass('fa-bars').addClass('fa-bars-staggered');
                    }
                    localStorage.setItem('mg_sidebar_collapsed', isCollapsed);
                });
            });
        </script>

        <aside class="main-sidebar sidebar-no-expand elevation-3">
            <a href="<?= $adminPrefix ?>index.php" class="brand-link">
                <img src="<?= $sitePrefix ?>assets/images/logo.jpg" alt="MG Education Logo" class="brand-logo-img">
            </a>
            <div class="sidebar">
                <!-- Admin User Card (Executive Centered Profile) -->
                <div class="mg-admin-user-card">
                    <a href="<?= $adminPrefix ?>profile.php" class="mg-user-card-inner">
                        <div class="mg-user-avatar-wrap">
                            <img src="<?= $adminAvatar ?>" class="mg-user-avatar-img" alt="<?= $adminName ?>">
                            <span class="mg-online-dot" title="Online"></span>
                        </div>
                        <div class="mg-user-meta">
                            <span class="mg-user-name"><?= $adminName ?></span>
                            <span class="mg-user-role-badge">
                                <i class="fas fa-shield-alt"></i> <?= $adminRole ?>
                            </span>
                        </div>
                    </a>
                </div>

                <div class="mg-sidebar-divider">
                    <span>Navigation Modules</span>
                </div>

                <nav class="mt-1">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <?php foreach ($menuItems as $menuItem): 
                            $hasPages = !empty($menuItem['pages']);
                            $itemHref = $hasPages ? '#' : ($adminPrefix . ($menuItem['url'] ?? '#'));
                            $treeviewClass = $hasPages ? 'has-treeview' : '';
                            $menuOpenClass = ($hasPages && $menuItem === $active_menu) ? 'menu-open' : '';
                        ?>
                            <li class="nav-item <?= $treeviewClass ?> <?= $menuItem['theme'] ?> <?= $menuOpenClass ?>">
                                <a class="nav-link <?= $menuItem === $active_menu ? 'active' : '' ?>" href="<?= $itemHref ?>">
                                     <div class="mg-menu-badge-icon">
                                         <i class="<?= $menuItem['icon'] ?>"></i>
                                     </div>
                                     <p>
                                         <span class="mg-menu-text"><?= $menuItem['menuTitle'] ?></span>
                                     </p>
                                </a>
                                <?php if ($hasPages): ?>
                                     <ul class="nav nav-treeview">
                                         <?php foreach ($menuItem['pages'] as $page): ?>
                                             <li class="nav-item">
                                                 <a href="<?= $adminPrefix . $page['url'] ?>"
                                                     class="nav-link <?= $page === $active_page ? 'active' : '' ?>">
                                                     <div class="mg-sub-badge-icon">
                                                         <i class="fas fa-chevron-right"></i>
                                                     </div>
                                                     <p><?= $page['title'] ?></p>
                                                 </a>
                                             </li>
                                         <?php endforeach; ?>
                                     </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        
                        <!-- Logout Action Button -->
                        <li class="nav-item mg-sidebar-logout-btn" onclick="logout()">
                            <a href="javascript:void(0);" class="nav-link">
                                <i class="fas fa-power-off"></i>
                                <p>Logout Session</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <!-- Content-wrapper started -->
            <section class="content">
                <!-- Content section started -->
                <div class="container-fluid">
                    <!-- Container-fluid started -->