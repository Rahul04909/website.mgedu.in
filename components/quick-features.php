<?php
/**
 * MG Education & Social Development Organization
 * Hero Quick-Action Chevron Modules (components/quick-features.php)
 * 
 * 100% Visual Replica with Overlapping Hero Alignment & Seamless Vector Chevrons
 */

$feature_blocks = [
    [
        'class'       => 'purple',
        'color'       => '#724c97',
        'icon'        => 'fa-solid fa-graduation-cap',
        'title'       => 'Online Education',
        'has_arrow'   => true,
        'items'       => [
            ['label' => 'Online Programmes', 'url' => '#online-programmes'],
            ['label' => 'SWAYAM Courses',    'url' => '#swayam-courses'],
            ['label' => 'Virtual Classes',   'url' => '#virtual-classes'],
        ]
    ],
    [
        'class'       => 'amber',
        'color'       => '#d67d26',
        'icon'        => 'fa-solid fa-book-open',
        'title'       => 'Browse our Programme',
        'has_arrow'   => true,
        'items'       => [
            ['label' => 'Under Graduate',    'url' => '#under-graduate'],
            ['label' => 'Post Graduate',     'url' => '#post-graduate'],
            ['label' => 'Others',            'url' => '#other-programmes'],
            ['label' => 'Doctorate (PHD)',   'url' => '#phd-programmes'],
        ]
    ],
    [
        'class'       => 'cyan',
        'color'       => '#00a2db',
        'icon'        => 'fa-solid fa-user-pen',
        'title'       => 'Admission',
        'has_arrow'   => true,
        'items'       => [
            ['label' => 'Apply for ODL Programme',     'url' => '#apply-odl'],
            ['label' => 'Apply for Online Programme',  'url' => '#apply-online'],
            ['label' => 'Apply for foreign IOP',       'url' => '#apply-foreign'],
            ['label' => 'Apply for Ph.D. Programmes',  'url' => '#apply-phd'],
            ['label' => 'Re-Registration',             'url' => '#re-registration'],
        ]
    ],
    [
        'class'       => 'vermilion',
        'color'       => '#e34e26',
        'icon'        => 'fa-solid fa-globe',
        'title'       => 'Our Network',
        'has_arrow'   => false,
        'items'       => [
            ['label' => 'Regional Centers',          'url' => '#regional-centers'],
            ['label' => 'Learner Support Centers',    'url' => '#support-centers'],
            ['label' => 'Overseas Study Centres',     'url' => '#overseas-centres'],
        ]
    ]
];
?>

<!-- Quick Action Chevron Component Stylesheet -->
<link rel="stylesheet" href="assets/css/quick-features.css">

<!-- HERO QUICK-ACTION CHEVRON COMPONENT (Attached directly to hero) -->
<section class="mg-qf-section" aria-label="Student Services & Quick Access">
    
    <!-- 4 Interconnected Chevron Action Modules -->
    <div class="mg-qf-wrapper">
        <?php foreach ($feature_blocks as $block): ?>
            <div class="mg-qf-col <?php echo $block['class']; ?>">
                
                <!-- Overlapping Circular Badge with Golden-Yellow Ring -->
                <div class="mg-qf-badge" aria-hidden="true">
                    <i class="<?php echo $block['icon']; ?>"></i>
                </div>

                <!-- Card Content -->
                <div class="mg-qf-content">
                    <h3 class="mg-qf-title"><?php echo htmlspecialchars($block['title']); ?></h3>
                    <ul class="mg-qf-list">
                        <?php foreach ($block['items'] as $item): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Seamless Chevron Arrow Overlap (Except Last Block) -->
                <?php if (!empty($block['has_arrow'])): ?>
                    <svg class="mg-qf-arrow" viewBox="0 0 30 180" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="0,0 28,90 0,180" fill="<?php echo $block['color']; ?>" />
                        <polyline points="0,0 28,90 0,180" fill="none" stroke="#ffffff" stroke-width="4.5" stroke-linejoin="round" stroke-linecap="round" />
                    </svg>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

</section>
