<?php
/**
 * MG Education & Social Development Organization
 * Header Component (includes/header.php)
 * 
 * Color Scheme: Balanced Educational Blue (#0b63b6 / #084e96) + Crisp White Main Area + Royal Navy Topbar (#082646)
 */

// Load Environment Variables (.env) & Global Config
require_once __DIR__ . '/config.php';

if (!isset($page_title)) {
    $page_title = env('APP_NAME', 'MG Education & Social Development Organization') . " | Empowering Society Through Education";
}
if (!isset($current_page)) {
    $current_page = "home";
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans (English) & Mukta (Hindi) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons (v6.5.1) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Header Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="stylesheet" href="assets/css/quick-features.css">
    <link rel="stylesheet" href="assets/css/director-message.css">
    <link rel="stylesheet" href="assets/css/courses.css">
    <link rel="stylesheet" href="assets/css/internships.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>

<!-- ==========================================
     MG EDUCATION & SOCIAL DEVELOPMENT HEADER
     ========================================== -->
<header class="mg-header-wrapper" role="banner">

    <!-- 1. TOP UTILITY BAR (Royal Navy Theme, Accessibility, Social, Alert, Language) -->
    <div class="mg-topbar">
        <div class="mg-container">
            <!-- Left: Social Media & Quick Highlight Link -->
            <div class="mg-topbar-left">
                <div class="mg-social-links" aria-label="Social Media Links">
                    <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="mg-social-btn" title="Follow us on X (Twitter)">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="mg-social-btn" title="Follow us on Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="mg-social-btn" title="Subscribe to YouTube Channel">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="mg-social-btn" title="Follow on Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                </div>

                <div class="mg-topbar-divider"></div>

                <!-- Live Quick Notice / Highlight -->
                <a href="#portal" class="mg-quick-badge-link" title="Online Admission & Student Portal">
                    <i class="fa-solid fa-certificate"></i>
                    <span>MG EDU's Online Portal & Verification</span>
                </a>
            </div>

            <!-- Right: Search, Accessibility Tools & Bilingual Switcher -->
            <div class="mg-topbar-right">
                <!-- Search Button -->
                <button type="button" class="mg-top-search-btn" id="mgSearchOpen" title="Search Portal" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <div class="mg-topbar-divider"></div>

                <!-- Font Resizer for Accessibility -->
                <div class="mg-font-resizer" role="group" aria-label="Font size controls">
                    <button type="button" class="mg-font-btn" id="mgFontDecrease" title="Decrease Font Size" aria-label="Decrease Font Size">A-</button>
                    <button type="button" class="mg-font-btn active" id="mgFontReset" title="Default Font Size" aria-label="Default Font Size">A</button>
                    <button type="button" class="mg-font-btn" id="mgFontIncrease" title="Increase Font Size" aria-label="Increase Font Size">A+</button>
                </div>

                <!-- Contrast / Theme Switcher -->
                <div class="mg-theme-switchers" role="group" aria-label="Display themes">
                    <button type="button" class="mg-theme-circle white" id="mgThemeDefault" title="Standard White Mode" aria-label="Standard White Mode"></button>
                    <button type="button" class="mg-theme-circle black" id="mgThemeContrast" title="High Contrast Mode" aria-label="High Contrast Mode"></button>
                </div>

                <div class="mg-topbar-divider"></div>

                <!-- Language Switcher -->
                <div class="mg-lang-selector" aria-label="Language Selector">
                    <button type="button" class="mg-lang-btn active" id="mgLangEn" title="English">English</button>
                    <span style="opacity: 0.4;">|</span>
                    <button type="button" class="mg-lang-btn" id="mgLangHi" title="हिन्दी">हिन्दी</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MAIN MIDDLE HEADER AREA (Crisp White Background, Full-Width / Minimal Side Padding) -->
    <div class="mg-main-header">
        <div class="mg-container">
            
            <!-- Organization Logo (assets/images/logo.jpg) -->
            <a href="index.php" class="mg-brand-area" aria-label="MG Education & Social Development Organization Home">
                <div class="mg-logo-wrapper">
                    <img src="assets/images/logo.jpg" alt="MG Education Logo" class="mg-logo-img">
                </div>
            </a>

            <!-- Central Bilingual Titles -->
            <div class="mg-header-titles">
                <h1 class="mg-title-en">
                    <span>MG EDUCATION</span> & SOCIAL DEVELOPMENT ORGANIZATION
                </h1>
                <h2 class="mg-title-hi">
                    एम जी शिक्षा एवं सामाजिक विकास संस्थान
                </h2>
                <div class="mg-subtitle-tag">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 5px;"></i>
                    Registered Under Govt. of India | An Autonomous National Educational & Social Welfare Trust
                </div>
            </div>

            <!-- Right Accreditation & Recognition Badges (ISO & HSCIT Logos) -->
            <div class="mg-header-badges">
                <div class="mg-badge-img-wrapper" title="ISO 9001:2015 Certified">
                    <img src="assets/images/iso.jpg" alt="ISO 9001:2015 Certified" class="mg-badge-img">
                </div>
                <div class="mg-badge-img-wrapper" title="HSCIT Registered">
                    <img src="assets/images/hscit.png" alt="HSCIT Logo" class="mg-badge-img">
                </div>
            </div>

        </div>
    </div>

    <!-- 3. MAIN NAVIGATION BAR (Balanced Royal Educational Blue, Icons, Dropdowns) -->
    <nav class="mg-navbar" aria-label="Main Navigation">
        <div class="mg-container">
            
            <!-- Mobile Menu Toggle Button -->
            <button type="button" class="mg-nav-toggle" id="mgNavToggle" aria-label="Toggle navigation menu" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
                <span>MENU</span>
            </button>

            <!-- Navigation Links -->
            <ul class="mg-nav-menu" id="mgNavMenu">
                <!-- Home -->
                <li class="mg-nav-item">
                    <a href="index.php" class="mg-nav-link <?php echo ($current_page === 'home') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>

                <!-- About Us (With Dropdown) -->
                <li class="mg-nav-item mg-has-dropdown">
                    <a href="#about" class="mg-nav-link <?php echo ($current_page === 'about') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-landmark"></i>
                        <span>About Us</span>
                        <i class="fa-solid fa-chevron-down mg-arrow"></i>
                    </a>
                    <ul class="mg-dropdown">
                        <li><a href="#organization-profile"><i class="fa-solid fa-id-badge"></i> Organization Profile</a></li>
                        <li><a href="#vision-mission"><i class="fa-solid fa-bullseye"></i> Vision & Mission</a></li>
                        <li><a href="#board-members"><i class="fa-solid fa-users-gear"></i> Advisory Board</a></li>
                        <li><a href="#legal-status"><i class="fa-solid fa-file-shield"></i> Legal Status & Approvals</a></li>
                        <li><a href="#president-desk"><i class="fa-solid fa-envelope-open-text"></i> President's Message</a></li>
                    </ul>
                </li>

                <!-- Programs / Education (With Dropdown) -->
                <li class="mg-nav-item mg-has-dropdown">
                    <a href="#programs" class="mg-nav-link <?php echo ($current_page === 'programs') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>Programs</span>
                        <i class="fa-solid fa-chevron-down mg-arrow"></i>
                    </a>
                    <ul class="mg-dropdown">
                        <li><a href="#computer-courses"><i class="fa-solid fa-laptop-code"></i> Computer Education</a></li>
                        <li><a href="#vocational-training"><i class="fa-solid fa-screwdriver-wrench"></i> Vocational Training</a></li>
                        <li><a href="#skill-development"><i class="fa-solid fa-lightbulb"></i> Skill India Initiatives</a></li>
                        <li><a href="#teacher-training"><i class="fa-solid fa-chalkboard-user"></i> Teacher Training (NTT)</a></li>
                        <li><a href="#women-empowerment"><i class="fa-solid fa-person-dress"></i> Women Self-Employment</a></li>
                    </ul>
                </li>

                <!-- Student Services (With Dropdown) -->
                <li class="mg-nav-item mg-has-dropdown">
                    <a href="#students" class="mg-nav-link <?php echo ($current_page === 'students') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user-graduate"></i>
                        <span>Student Services</span>
                        <i class="fa-solid fa-chevron-down mg-arrow"></i>
                    </a>
                    <ul class="mg-dropdown">
                        <li><a href="#online-admission"><i class="fa-solid fa-file-signature"></i> Online Admission Form</a></li>
                        <li><a href="#download-admit-card"><i class="fa-solid fa-ticket"></i> Download Admit Card</a></li>
                        <li><a href="#syllabus"><i class="fa-solid fa-book-bookmark"></i> Syllabus & Study Kit</a></li>
                        <li><a href="#exam-dates"><i class="fa-solid fa-calendar-days"></i> Examination Schedule</a></li>
                        <li><a href="#results"><i class="fa-solid fa-square-poll-vertical"></i> View Results</a></li>
                    </ul>
                </li>

                <!-- Social Welfare & Projects -->
                <li class="mg-nav-item mg-has-dropdown">
                    <a href="#social-work" class="mg-nav-link <?php echo ($current_page === 'social-work') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-hands-holding-child"></i>
                        <span>Social Welfare</span>
                        <i class="fa-solid fa-chevron-down mg-arrow"></i>
                    </a>
                    <ul class="mg-dropdown">
                        <li><a href="#rural-education"><i class="fa-solid fa-school"></i> Free Rural Education</a></li>
                        <li><a href="#health-camps"><i class="fa-solid fa-heart-pulse"></i> Free Health & Eye Camps</a></li>
                        <li><a href="#environmental-drives"><i class="fa-solid fa-seedling"></i> Tree Plantation Drives</a></li>
                        <li><a href="#food-distribution"><i class="fa-solid fa-bowl-rice"></i> Nutrition & Food Support</a></li>
                    </ul>
                </li>

                <!-- Announcements with Animated NEW Badge -->
                <li class="mg-nav-item">
                    <a href="#announcements" class="mg-nav-link <?php echo ($current_page === 'announcements') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Announcements</span>
                        <span class="mg-new-pill">New</span>
                    </a>
                </li>

                <!-- Media & Gallery -->
                <li class="mg-nav-item">
                    <a href="#media" class="mg-nav-link <?php echo ($current_page === 'media') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-photo-film"></i>
                        <span>Media</span>
                    </a>
                </li>

                <!-- Online Verification -->
                <li class="mg-nav-item">
                    <a href="#verification" class="mg-nav-link <?php echo ($current_page === 'verification') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <span>Verification</span>
                    </a>
                </li>

                <!-- Contact Us -->
                <li class="mg-nav-item">
                    <a href="#contact" class="mg-nav-link <?php echo ($current_page === 'contact') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-headset"></i>
                        <span>Contact Us</span>
                    </a>
                </li>
            </ul>

            <!-- Right CTA Button -->
            <div class="mg-nav-cta">
                <a href="#portal-login" class="mg-btn-portal">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Portal Login</span>
                </a>
            </div>

        </div>
    </nav>

</header>

<!-- SEARCH MODAL OVERLAY -->
<div class="mg-search-overlay" id="mgSearchOverlay" role="dialog" aria-modal="true" aria-label="Search Site">
    <div class="mg-search-card">
        <button type="button" class="mg-search-close" id="mgSearchClose" aria-label="Close Search">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h3 style="font-size: 18px; color: #07223d; font-weight: 700;">
            <i class="fa-solid fa-magnifying-glass" style="color: #0b63b6; margin-right: 8px;"></i>
            Search MG Education Portal
        </h3>
        <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Search courses, notifications, verification, study centers & services</p>
        
        <form class="mg-search-form" action="#search" method="GET">
            <input type="search" name="q" class="mg-search-input" id="mgSearchInput" placeholder="Enter keyword, roll number, course name..." required>
            <button type="submit" class="mg-search-submit">
                <i class="fa-solid fa-arrow-right"></i> Search
            </button>
        </form>
    </div>
</div>

<!-- Header Scripts -->
<script src="assets/js/header.js"></script>
