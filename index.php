<?php
/**
 * MG Education & Social Development Organization
 * Main Index Page
 */

// Define page parameters
$page_title = "MG Education & Social Development Organization | Empowering Society Through Education";
$current_page = "home";

// Include Header
require_once __DIR__ . '/includes/header.php';

// Include Hero Component
require_once __DIR__ . '/components/hero.php';

// Include Hero Quick-Action Chevron Features
require_once __DIR__ . '/components/quick-features.php';

// Include Director's Message Component
require_once __DIR__ . '/components/director-message.php';

// Include Trending Courses Component
require_once __DIR__ . '/components/courses.php';

// Include Featured Internships Component
require_once __DIR__ . '/components/internships.php';

// Include Main Footer Component
require_once __DIR__ . '/includes/footer.php';
