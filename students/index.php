<?php 
/**
 * MG Education & Social Development Organization
 * Admin Control Panel - Main Dashboard Overview
 * Styled with 100% Quick Features Visual Language: Interconnected Chevrons, Golden-Ring Floating Badges, Solid Palette
 */
include './header.php'; 

$stat_cards = [
    [
        'class'     => 'purple',
        'color'     => '#724c97',
        'icon'      => 'fas fa-graduation-cap',
        'number'    => '48',
        'suffix'    => '+',
        'title'     => 'Academic Courses',
        'sublabel'  => 'Computer & Skill Programs',
        'link'      => 'courses.php',
        'has_arrow' => true
    ],
    [
        'class'     => 'amber',
        'color'     => '#d67d26',
        'icon'      => 'fas fa-building-columns',
        'number'    => '150',
        'suffix'    => '+',
        'title'     => 'Authorized Centers',
        'sublabel'  => 'Pan-India Active Network',
        'link'      => 'centers.php',
        'has_arrow' => true
    ],
    [
        'class'     => 'cyan',
        'color'     => '#00a2db',
        'icon'      => 'fas fa-user-graduate',
        'number'    => '1,280',
        'suffix'    => '+',
        'title'     => 'Enrolled Students',
        'sublabel'  => 'Online & Center Admissions',
        'link'      => 'students.php',
        'has_arrow' => true
    ],
    [
        'class'     => 'vermilion',
        'color'     => '#e34e26',
        'icon'      => 'fas fa-award',
        'number'    => '4,650',
        'suffix'    => '+',
        'title'     => 'Issued Certificates',
        'sublabel'  => '24x7 Online Verification',
        'link'      => 'results.php',
        'has_arrow' => false
    ]
];
?>

<!-- QUICK FEATURES STYLE STAT CARDS COMPONENT -->
<div class="mg-stat-chevron-container">
    <div class="mg-stat-wrapper">
        <?php foreach ($stat_cards as $card): ?>
            <div class="mg-stat-col <?= $card['class'] ?>">
                
                <!-- Floating Circular Badge with Signature Golden-Yellow Ring -->
                <div class="mg-stat-badge" aria-hidden="true">
                    <i class="<?= $card['icon'] ?>"></i>
                </div>

                <!-- Card Metric Information -->
                <div class="mg-stat-content">
                    <div class="mg-stat-number-wrap">
                        <span class="mg-stat-number"><?= $card['number'] ?></span>
                        <span class="mg-stat-suffix"><?= $card['suffix'] ?></span>
                    </div>
                    <h3 class="mg-stat-title"><?= $card['title'] ?></h3>
                    <div class="mg-stat-sublabel">
                        <i class="fas fa-check-circle"></i>
                        <span><?= $card['sublabel'] ?></span>
                    </div>
                </div>

                <!-- Bottom Action Link ("More Info" / "View Details") -->
                <div class="mg-stat-footer">
                    <a href="<?= $card['link'] ?>" class="mg-stat-link">
                        <span>More info</span>
                        <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>

                <!-- Seamless Chevron Arrow Overlap (Except Last Card) -->
                <?php if (!empty($card['has_arrow'])): ?>
                    <svg class="mg-stat-arrow" viewBox="0 0 30 180" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="0,0 28,90 0,180" fill="<?= $card['color'] ?>" />
                        <polyline points="0,0 28,90 0,180" fill="none" stroke="#ffffff" stroke-width="4.5" stroke-linejoin="round" stroke-linecap="round" />
                    </svg>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include './footer.php'; ?>
