<?php
/**
 * MG Education & Social Development Organization
 * Trending Courses Component (components/courses.php)
 * 
 * Unique, Colorful, and Professional Course Cards Design
 */

$trending_courses = [
    [
        'title'         => 'AI Coder: Complete Claude Code & Coding Agents Course',
        'category'      => 'Artificial Intelligence',
        'theme'         => 'theme-purple',
        'author'        => 'Ligency, Ed Donner',
        'badge'         => 'Bestseller',
        'badge_type'    => 'purple',
        'rating'        => '4.8',
        'ratings_count' => '13,593 ratings',
        'duration'      => '42 Hours',
        'level'         => 'All Levels',
        'price'         => '449.00',
        'original_price'=> '799.00',
        'discount'      => '44% OFF',
        'image'         => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'         => 'Ultimate AWS Certified Solutions Architect Associate 2026',
        'category'      => 'Cloud Computing',
        'theme'         => 'theme-amber',
        'author'        => 'Stephane Maarek | AWS Cloud Specialist',
        'badge'         => 'Bestseller',
        'badge_type'    => 'gold',
        'rating'        => '4.7',
        'ratings_count' => '300,987 ratings',
        'duration'      => '56 Hours',
        'level'         => 'Intermediate',
        'price'         => '499.00',
        'original_price'=> '3,459.00',
        'discount'      => '86% OFF',
        'image'         => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'         => 'Microsoft Excel - Excel from Beginner to Advanced 2026',
        'category'      => 'Office & Analytics',
        'theme'         => 'theme-emerald',
        'author'        => 'Kyle Pew · Office Productivity Master',
        'badge'         => 'Bestseller',
        'badge_type'    => 'emerald',
        'rating'        => '4.7',
        'ratings_count' => '547,871 ratings',
        'duration'      => '32 Hours',
        'level'         => 'Beginner to Pro',
        'price'         => '469.00',
        'original_price'=> '3,299.00',
        'discount'      => '86% OFF',
        'image'         => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'         => 'CompTIA Security+ (SY0-701) Complete Prep & Cyber Defense',
        'category'      => 'Cyber Security',
        'theme'         => 'theme-rose',
        'author'        => 'Jason Dion · Global Cyber Shield Academy',
        'badge'         => 'Trending',
        'badge_type'    => 'gold',
        'rating'        => '4.8',
        'ratings_count' => '125,210 ratings',
        'duration'      => '48 Hours',
        'level'         => 'All Levels',
        'price'         => '669.00',
        'original_price'=> '4,649.00',
        'discount'      => '85% OFF',
        'image'         => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'         => 'ADCA: Advanced Diploma in Computer Applications (Govt. Regd)',
        'category'      => 'Govt. Diploma',
        'theme'         => 'theme-blue',
        'author'        => 'MG Education National Skill Faculty',
        'badge'         => 'Govt. Approved',
        'badge_type'    => 'cyan',
        'rating'        => '4.9',
        'ratings_count' => '48,920 ratings',
        'duration'      => '1 Year Diploma',
        'level'         => 'Govt. Certified',
        'price'         => '499.00',
        'original_price'=> '2,999.00',
        'discount'      => '83% OFF',
        'image'         => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=640&q=80'
    ],
    [
        'title'         => 'Full Stack Web Development Bootcamp: PHP, MySQL, JS & React',
        'category'      => 'Web Development',
        'theme'         => 'theme-violet',
        'author'        => 'CodeCraft Studios · 120k+ Graduates',
        'badge'         => 'Highest Rated',
        'badge_type'    => 'purple',
        'rating'        => '4.9',
        'ratings_count' => '88,430 ratings',
        'duration'      => '64 Hours',
        'level'         => 'Beginner to Advanced',
        'price'         => '549.00',
        'original_price'=> '3,899.00',
        'discount'      => '86% OFF',
        'image'         => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=640&q=80'
    ]
];
?>

<!-- Course Component Stylesheet -->
<link rel="stylesheet" href="assets/css/courses.css">

<!-- TRENDING COURSES SECTION -->
<section class="mg-courses-section" aria-label="Trending Courses">
    <div class="mg-courses-container">
        
        <!-- Section Title Header -->
        <div class="mg-courses-header">
            <div class="mg-courses-title-wrap">
                <span class="mg-courses-subtitle">Skill Up Today</span>
                <h2 class="mg-courses-title">Trending Courses</h2>
            </div>
            <a href="#all-courses" class="mg-courses-explore-link">
                <span>View All Courses</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <!-- Carousel Wrapper with Left & Right Floating Arrows -->
        <div class="mg-carousel-wrapper">
            <!-- Left Arrow -->
            <button type="button" class="mg-course-arrow prev" aria-label="Previous courses">
                <i class="fa-solid fa-chevron-left"></i>
            </button>

            <!-- Scrollable Track of Course Cards -->
            <div class="mg-courses-track" role="region" aria-label="Courses Carousel" tabindex="0">
                <?php foreach ($trending_courses as $course): ?>
                    <article class="mg-course-card <?php echo $course['theme']; ?>">
                        
                        <!-- Course Image Thumbnail Area -->
                        <div class="mg-course-thumb-wrap">
                            <img 
                                src="<?php echo htmlspecialchars($course['image']); ?>" 
                                alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                class="mg-course-thumb"
                                loading="lazy"
                            >
                        </div>

                        <!-- Course Details Body -->
                        <div class="mg-course-body">
                            <!-- Duration & Level Specs -->
                            <div class="mg-course-specs">
                                <span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                                <span>•</span>
                                <span><i class="fa-solid fa-chart-simple"></i> <?php echo htmlspecialchars($course['level']); ?></span>
                            </div>

                            <!-- Course Title (Clamped to 2 lines) -->
                            <h3 class="mg-course-title" title="<?php echo htmlspecialchars($course['title']); ?>">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </h3>

                            <!-- Course Instructor with Verified Badge -->
                            <span class="mg-course-author" title="<?php echo htmlspecialchars($course['author']); ?>">
                                <span><?php echo htmlspecialchars($course['author']); ?></span>
                                <i class="fa-solid fa-circle-check" title="Verified Instructor"></i>
                            </span>

                            <!-- Badges & Ratings Meta -->
                            <div class="mg-course-meta">
                                <?php if (!empty($course['badge'])): ?>
                                    <span class="mg-badge-pill <?php echo $course['badge_type']; ?>">
                                        <?php echo htmlspecialchars($course['badge']); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="mg-course-rating">
                                    <i class="fa-solid fa-star"></i>
                                    <span><?php echo htmlspecialchars($course['rating']); ?></span>
                                </span>

                                <span class="mg-course-ratings-count">
                                    (<?php echo htmlspecialchars($course['ratings_count']); ?>)
                                </span>
                            </div>

                            <!-- Pricing & Add to Cart Footer -->
                            <div class="mg-course-footer">
                                <div class="mg-course-pricing-col">
                                    <div class="mg-course-pricing">
                                        <span class="mg-price-current">₹<?php echo htmlspecialchars($course['price']); ?></span>
                                        <?php if (!empty($course['original_price'])): ?>
                                            <span class="mg-price-original">₹<?php echo htmlspecialchars($course['original_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($course['discount'])): ?>
                                        <span class="mg-discount-tag"><?php echo htmlspecialchars($course['discount']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <button type="button" class="mg-btn-add-cart" aria-label="Add <?php echo htmlspecialchars($course['title']); ?> to cart">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                    <span>Add to cart</span>
                                </button>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Right Arrow -->
            <button type="button" class="mg-course-arrow next" aria-label="Next courses">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

    </div>
</section>

<!-- Course Component JavaScript -->
<script src="assets/js/courses.js"></script>
