<?php
/**
 * MG Education & Social Development Organization
 * Featured Internships Component (components/internships.php)
 * 
 * Clean, Professional Horizontal Carousel - Matching Courses UI Structure
 */

$featured_internships = [
    [
        'title'          => 'Full Stack Web Development Internship (Live Client Projects)',
        'lead'           => 'MG Tech Labs · Software Cell',
        'mode'           => 'Virtual / Remote',
        'duration'       => '3 Months',
        'badge'          => 'Certified',
        'badge_type'     => 'cyan',
        'rating'         => '4.9',
        'reviews_count'  => '1,840 reviews',
        'stipend_label'  => 'Stipend',
        'stipend'        => '₹8,000 / mo',
        'image'          => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'          => 'AI & Machine Learning Research Intern Program 2026',
        'lead'           => 'MG Data Intelligence Wing',
        'mode'           => 'Virtual / Hands-on',
        'duration'       => '2 Months',
        'badge'          => 'Top Rated',
        'badge_type'     => 'purple',
        'rating'         => '4.8',
        'reviews_count'  => '950 reviews',
        'stipend_label'  => 'Stipend',
        'stipend'        => '₹10,000 / mo',
        'image'          => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'          => 'Digital Marketing & Social Media Strategy Intern',
        'lead'           => 'MG Media & Communications Hub',
        'mode'           => 'Hybrid / Online',
        'duration'       => '3 Months',
        'badge'          => 'Fast Filling',
        'badge_type'     => 'gold',
        'rating'         => '4.7',
        'reviews_count'  => '1,210 reviews',
        'stipend_label'  => 'Stipend',
        'stipend'        => '₹6,500 / mo',
        'image'          => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'          => 'Cybersecurity Analyst & Penetration Testing Intern',
        'lead'           => 'MG Cyber Security Center',
        'mode'           => 'Virtual / Remote',
        'duration'       => '4 Months',
        'badge'          => 'Govt. Project',
        'badge_type'     => 'emerald',
        'rating'         => '4.9',
        'reviews_count'  => '840 reviews',
        'stipend_label'  => 'Stipend',
        'stipend'        => '₹9,000 / mo',
        'image'          => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'          => 'Computer Applications & Office Administration Trainee',
        'lead'           => 'MG Vocational Skill Mission',
        'mode'           => 'On-Campus / Center',
        'duration'       => '6 Months',
        'badge'          => 'Govt. Approved',
        'badge_type'     => 'cyan',
        'rating'         => '4.9',
        'reviews_count'  => '2,150 reviews',
        'stipend_label'  => 'Certification',
        'stipend'        => 'Govt. Certified',
        'image'          => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'          => 'Data Analytics & Business Intelligence Virtual Intern',
        'lead'           => 'MG Analytical Insights Team',
        'mode'           => 'Remote / Online',
        'duration'       => '3 Months',
        'badge'          => 'In Demand',
        'badge_type'     => 'purple',
        'rating'         => '4.8',
        'reviews_count'  => '1,430 reviews',
        'stipend_label'  => 'Stipend',
        'stipend'        => '₹7,500 / mo',
        'image'          => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=640&q=80'
    ]
];
?>

<!-- Internships Component Stylesheet -->
<link rel="stylesheet" href="assets/css/internships.css">

<!-- FEATURED INTERNSHIPS SECTION -->
<section class="mg-internships-section" aria-label="Featured Internships">
    <div class="mg-internships-container">
        
        <!-- Section Title Header -->
        <div class="mg-internships-header">
            <div class="mg-internships-title-wrap">
                <span class="mg-internships-subtitle">Practical Industry Exposure</span>
                <h2 class="mg-internships-title">Featured Internships</h2>
            </div>
            <a href="#all-internships" class="mg-internships-explore-link">
                <span>View All Internships</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Carousel Wrapper with Left & Right Floating Arrows -->
        <div class="mg-internships-carousel-wrapper">
            <!-- Left Arrow -->
            <button type="button" class="mg-internship-arrow prev" aria-label="Previous internships">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <!-- Scrollable Track of Internship Cards -->
            <div class="mg-internships-track" role="region" aria-label="Internships Carousel" tabindex="0">
                <?php foreach ($featured_internships as $internship): ?>
                    <article class="mg-internship-card">
                        
                        <!-- Thumbnail Area (Clean without labels/wishlist as preferred) -->
                        <div class="mg-internship-thumb-wrap">
                            <img 
                                src="<?php echo htmlspecialchars($internship['image']); ?>" 
                                alt="<?php echo htmlspecialchars($internship['title']); ?>" 
                                class="mg-internship-thumb"
                                loading="lazy"
                            >
                        </div>

                        <!-- Card Content Body -->
                        <div class="mg-internship-body">
                            <!-- Mode & Duration Specs -->
                            <div class="mg-internship-specs">
                                <span><i class="fa-solid fa-laptop-code"></i> <?php echo htmlspecialchars($internship['mode']); ?></span>
                                <span>•</span>
                                <span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($internship['duration']); ?></span>
                            </div>

                            <!-- Internship Title (Clamped to 2 lines) -->
                            <h3 class="mg-internship-title" title="<?php echo htmlspecialchars($internship['title']); ?>">
                                <?php echo htmlspecialchars($internship['title']); ?>
                            </h3>

                            <!-- Organization / Program Lead with Verified Badge -->
                            <span class="mg-internship-author" title="<?php echo htmlspecialchars($internship['lead']); ?>">
                                <span><?php echo htmlspecialchars($internship['lead']); ?></span>
                                <i class="fa-solid fa-circle-check" title="Verified Opportunity"></i>
                            </span>

                            <!-- Badges & Ratings Meta -->
                            <div class="mg-internship-meta">
                                <?php if (!empty($internship['badge'])): ?>
                                    <span class="mg-internship-badge-pill <?php echo $internship['badge_type']; ?>">
                                        <?php echo htmlspecialchars($internship['badge']); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="mg-internship-rating">
                                    <i class="fa-solid fa-star"></i>
                                    <span><?php echo htmlspecialchars($internship['rating']); ?></span>
                                </span>

                                <span class="mg-internship-ratings-count">
                                    (<?php echo htmlspecialchars($internship['reviews_count']); ?>)
                                </span>
                            </div>

                            <!-- Pricing / Stipend & Apply Button Footer -->
                            <div class="mg-internship-footer">
                                <div class="mg-internship-stipend-col">
                                    <span class="mg-stipend-label"><?php echo htmlspecialchars($internship['stipend_label']); ?></span>
                                    <span class="mg-stipend-amount"><?php echo htmlspecialchars($internship['stipend']); ?></span>
                                </div>

                                <button type="button" class="mg-btn-apply" aria-label="Apply for <?php echo htmlspecialchars($internship['title']); ?>">
                                    <span>Apply Now</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Right Arrow -->
            <button type="button" class="mg-internship-arrow next" aria-label="Next internships">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

    </div>
</section>

<!-- Internships Component JavaScript -->
<script src="assets/js/internships.js"></script>

