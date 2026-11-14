<?php
/**
 * MG Education & Social Development Organization
 * Main Footer Component (includes/footer.php)
 * 
 * Colorful Chevron Menu Modules Design + Harmonized Vibrant Bottom Bar
 */

$footer_menus = [
    [
        'class'     => 'purple',
        'color'     => '#724c97',
        'icon'      => 'fa-solid fa-landmark',
        'title'     => 'About Organization',
        'has_arrow' => true,
        'links'     => [
            ['label' => 'About MG Education Trust',    'url' => '#about-trust'],
            ['label' => 'Vision, Mission & Goals',     'url' => '#vision-mission'],
            ['label' => 'Advisory Board & Trustees',   'url' => '#advisory-board'],
            ['label' => 'Legal Status & Approvals',    'url' => '#legal-approvals'],
            ['label' => 'Accreditation & ISO 9001',    'url' => '#accreditations'],
            ['label' => "President's Official Message",'url' => '#president-message'],
        ]
    ],
    [
        'class'     => 'amber',
        'color'     => '#d67d26',
        'icon'      => 'fa-solid fa-book-open',
        'title'     => 'Programs & Welfare',
        'has_arrow' => true,
        'links'     => [
            ['label' => 'Computer Education & ADCA',   'url' => '#computer-courses'],
            ['label' => 'Vocational & NTT Training',   'url' => '#vocational-training'],
            ['label' => 'Skill India Initiatives',     'url' => '#skill-india'],
            ['label' => 'Women Self-Employment',      'url' => '#women-empowerment'],
            ['label' => 'Free Rural Education Camps',  'url' => '#rural-education'],
            ['label' => 'Health & Eye Checkup Drives', 'url' => '#health-camps'],
        ]
    ],
    [
        'class'     => 'cyan',
        'color'     => '#00a2db',
        'icon'      => 'fa-solid fa-user-graduate',
        'title'     => 'Student Services',
        'has_arrow' => true,
        'links'     => [
            ['label' => 'Online Admission Form',       'url' => '#online-admission'],
            ['label' => 'Certificate Verification',    'url' => '#certificate-verification'],
            ['label' => 'Download Admit Card',         'url' => '#download-admit-card'],
            ['label' => 'Syllabus & Course Kits',      'url' => '#syllabus'],
            ['label' => 'Examination Datesheet',      'url' => '#exam-datesheet'],
            ['label' => 'Results & Marksheet Portal',  'url' => '#results-portal'],
        ]
    ],
    [
        'class'     => 'vermilion',
        'color'     => '#e34e26',
        'icon'      => 'fa-solid fa-headset',
        'title'     => 'Contact & Helpdesk',
        'has_arrow' => false,
        'contacts'  => [
            ['icon' => 'fa-solid fa-location-dot', 'text' => 'MG Education Head Campus, India'],
            ['icon' => 'fa-solid fa-phone',        'text' => '+91 98765 43210', 'url' => 'tel:+919876543210'],
            ['icon' => 'fa-solid fa-envelope',     'text' => 'support@mgedu.in', 'url' => 'mailto:support@mgedu.in'],
            ['icon' => 'fa-solid fa-clock',        'text' => 'Mon – Sat: 9:30 AM – 6:00 PM'],
            ['icon' => 'fa-solid fa-map-pin',      'text' => '150+ Study Centers Nationwide', 'url' => '#centers'],
        ]
    ]
];
?>

<!-- Footer Custom Stylesheet -->
<link rel="stylesheet" href="assets/css/footer.css">

<!-- ==========================================================================
     MG EDUCATION FOOTER - COLORFUL CHEVRON MENU DESIGN
     ========================================================================== */ -->
<footer class="mg-footer-wrapper" role="contentinfo">

    <!-- 1. 4 COLORFUL CHEVRON MENU MODULES (Purple, Amber, Cyan, Vermilion) -->
    <div class="mg-footer-chevron-section">
        <div class="mg-footer-chevron-wrapper">
            
            <?php foreach ($footer_menus as $menu): ?>
                <div class="mg-fmenu-col <?php echo $menu['class']; ?>">
                    
                    <!-- Overlapping Circular Badge with Bright Golden-Yellow Ring -->
                    <div class="mg-fmenu-badge" aria-hidden="true">
                        <i class="<?php echo $menu['icon']; ?>"></i>
                    </div>

                    <!-- Menu Card Content -->
                    <div class="mg-fmenu-content">
                        <h4 class="mg-fmenu-title"><?php echo htmlspecialchars($menu['title']); ?></h4>
                        
                        <?php if (isset($menu['links'])): ?>
                            <ul class="mg-fmenu-list">
                                <?php foreach ($menu['links'] as $link): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($link['url']); ?>">
                                            <?php echo htmlspecialchars($link['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (isset($menu['contacts'])): ?>
                            <div class="mg-fmenu-contacts">
                                <?php foreach ($menu['contacts'] as $contact): ?>
                                    <div class="mg-fmenu-contact-item">
                                        <i class="<?php echo $contact['icon']; ?>"></i>
                                        <?php if (!empty($contact['url'])): ?>
                                            <a href="<?php echo htmlspecialchars($contact['url']); ?>">
                                                <?php echo htmlspecialchars($contact['text']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($contact['text']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Seamless Vector Chevron Arrow Overlap (Except Last Block) -->
                    <?php if (!empty($menu['has_arrow'])): ?>
                        <svg class="mg-fmenu-arrow" viewBox="0 0 30 180" preserveAspectRatio="none" aria-hidden="true">
                            <polygon points="0,0 28,90 0,180" fill="<?php echo $menu['color']; ?>" />
                            <polyline points="0,0 28,90 0,180" fill="none" stroke="#ffffff" stroke-width="4.5" stroke-linejoin="round" stroke-linecap="round" />
                        </svg>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- SPECTRUM CONNECTOR STRIP (Harmonizing with Purple, Amber, Cyan, Vermilion) -->
    <div class="mg-footer-spectrum-bar" aria-hidden="true"></div>

    <!-- 2. VIBRANT, COLORFUL & PROFESSIONAL BOTTOM BAR -->
    <div class="mg-footer-bottom">
        <div class="mg-footer-container">
            
            <!-- Branding, Trust Badges & Social Media Row -->
            <div class="mg-footer-bottom-main">
                
                <div class="mg-fbottom-brand-group">
                    <!-- Brand Identity -->
                    <a href="index.php" class="mg-fbottom-brand" aria-label="MG Education Home">
                        <img src="assets/images/logo.jpg" alt="MG Education Logo" class="mg-fbottom-logo">
                        <div class="mg-fbottom-brand-text">
                            <span class="mg-fbottom-title-en">
                                <span class="mg-word-blue">MG EDUCATION</span> & <span class="mg-word-orange">SOCIAL DEVELOPMENT ORG.</span>
                            </span>
                            <span class="mg-fbottom-title-hi">एम जी शिक्षा एवं सामाजिक विकास संस्थान</span>
                        </div>
                    </a>

                    <!-- Colorful Accreditation Micro-Pills -->
                    <div class="mg-fbottom-trust-badges">
                        <span class="mg-ftrust-pill iso">
                            <i class="fa-solid fa-medal"></i> ISO 9001:2015
                        </span>
                        <span class="mg-ftrust-pill govt">
                            <i class="fa-solid fa-building-columns"></i> NITI Aayog
                        </span>
                        <span class="mg-ftrust-pill trust">
                            <i class="fa-solid fa-circle-check"></i> Govt. Regd.
                        </span>
                    </div>
                </div>

                <!-- Vibrant Official Social Profiles -->
                <div class="mg-fbottom-social" aria-label="Official Social Profiles">
                    <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="mg-fsocial-btn x-tw" title="Follow on X (Twitter)">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="mg-fsocial-btn fb" title="Follow on Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="mg-fsocial-btn yt" title="Subscribe to YouTube">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="mg-fsocial-btn insta" title="Follow on Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="mg-fsocial-btn in" title="Connect on LinkedIn">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                </div>
            </div>

            <!-- Copyright & Legal Policies Sub-strip -->
            <div class="mg-footer-bottom-sub">
                <div class="mg-fbottom-copy">
                    &copy; <?php echo date('Y'); ?> <strong>MG Education & Social Development Organization</strong>. All Rights Reserved. <span class="govt-badge">• An Autonomous National Trust</span>
                </div>

                <div class="mg-fbottom-legal">
                    <a href="#privacy">Privacy Policy</a>
                    <span style="opacity: 0.3;">|</span>
                    <a href="#terms">Terms of Service</a>
                    <span style="opacity: 0.3;">|</span>
                    <a href="#refund">Refund Policy</a>
                    <span style="opacity: 0.3;">|</span>
                    <a href="#rti">RTI</a>
                    <span style="opacity: 0.3;">|</span>
                    <a href="#disclaimer">Disclaimer</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Floating Back to Top Button with Dynamic Gradient -->
    <button type="button" class="mg-back-to-top" id="mgBackToTop" title="Back to top" aria-label="Back to top">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

</footer>

<!-- Footer JavaScript -->
<script src="assets/js/footer.js"></script>
</body>
</html>
