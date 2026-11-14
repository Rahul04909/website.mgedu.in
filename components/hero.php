<?php
/**
 * MG Education & Social Development Organization
 * Hero Component (components/hero.php)
 * 
 * Pure Image Slider - No Overlays - Fully Mobile Responsive
 */

$hero_slides = [
    [
        'image' => 'https://www.pw.live/gcms-fe/_next/image?url=https%3A%2F%2Fstatic.pw.live%2F5eb393ee95fab7468a79d189%2F2f68e00e-1c1d-4063-80c5-48d3077ce015.webp&w=1920&q=75',
        'alt'   => 'MG Education & Social Development Organization - Banner 1'
    ],
    [
        'image' => 'https://www.pw.live/gcms-fe/_next/image?url=https%3A%2F%2Fstatic.pw.live%2F5eb393ee95fab7468a79d189%2FGLOBAL_CMS%2F3c7ab180-ca53-4a9f-abde-25493082ed25.webp&w=1920&q=75',
        'alt'   => 'MG Education & Social Development Organization - Banner 2'
    ]
];
?>

<!-- Hero Slider Component Stylesheet -->
<link rel="stylesheet" href="assets/css/hero.css">

<!-- HERO SLIDER SECTION -->
<section class="mg-hero-slider" aria-label="Featured Announcements & Highlights" tabindex="0">
    <div class="mg-hero-track">
        <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="mg-hero-slide <?php echo ($index === 0) ? 'active' : ''; ?>" data-slide-index="<?php echo $index; ?>">
                <img 
                    src="<?php echo htmlspecialchars($slide['image']); ?>" 
                    alt="<?php echo htmlspecialchars($slide['alt']); ?>" 
                    class="mg-hero-img"
                    <?php echo ($index === 0) ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?>
                >
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Navigation Arrows -->
    <button type="button" class="mg-hero-arrow prev" aria-label="Previous Slide">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button type="button" class="mg-hero-arrow next" aria-label="Next Slide">
        <i class="fa-solid fa-chevron-right"></i>
    </button>

</section>

<!-- Hero Slider JavaScript -->
<script src="assets/js/hero.js"></script>

