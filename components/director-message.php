<?php
/**
 * MG Education & Social Development Organization
 * Director's Message Component (components/director-message.php)
 * 
 * Enhanced Executive Leadership Design with 3D Framing & Vision Pillars
 */

$director_data = [
    'kicker'        => 'Executive Leadership Desk',
    'title_pre'     => 'Welcome to',
    'title_main'    => 'MG Education & Social Development Organization',
    'quote_callout' => '“Empowering every student with certified skills, modern technical education, and social consciousness is our foundational mission.”',
    'photo'         => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=640&q=80',
    'photo_alt'     => 'Dr. Meenakshi Sharma - Director & Chairperson, MG Education',
    'experience'    => '20+ Years',
    'exp_label'     => 'Academic Leadership',
    'message_intro' => 'Welcome to MG Education & Social Development Organization, an autonomous national educational and social welfare trust registered under the Government of India with the mission to democratize quality education, digital literacy, and skill empowerment across India. Empowered by our foundational commitment to accessible learning, the Organization holds a unique mandate to provide inclusive, industry-aligned vocational, technical, and computer training across urban and rural communities nationwide.',
    'message_more'  => ' Through our standardized curriculum, national examination framework, and online certificate verification portals, we ensure that every learner receives recognized credentials that open doors to meaningful employment, entrepreneurship, and community leadership.',
    'name'          => 'Dr. Meenakshi Sharma',
    'designation'   => 'Director & Founder Chairperson, MG Education Trust',
    'pillars'       => [
        [
            'icon'  => 'fa-solid fa-user-graduate',
            'color' => 'purple',
            'num'   => '50,000+',
            'lbl'   => 'Students Empowered'
        ],
        [
            'icon'  => 'fa-solid fa-building-columns',
            'color' => 'amber',
            'num'   => '150+',
            'lbl'   => 'Authorized Centers'
        ],
        [
            'icon'  => 'fa-solid fa-award',
            'color' => 'cyan',
            'num'   => 'ISO 9001',
            'lbl'   => '& Govt. Registered'
        ]
    ]
];
?>

<!-- Director's Message Component Stylesheet -->
<link rel="stylesheet" href="assets/css/director-message.css">

<!-- DIRECTOR'S MESSAGE SECTION -->
<section class="mg-director-section" aria-label="Director's Welcome Message">
    <div class="mg-director-container">

        <!-- 2-Column Grid -->
        <div class="mg-director-grid">
            
            <!-- Left Column: Director Photo with 3D Geometric Accents & Floating Badge -->
            <div class="mg-director-photo-col">
                <div class="mg-director-frame">
                    
                    <div class="mg-director-img-wrap">
                        <img 
                            src="<?php echo htmlspecialchars($director_data['photo']); ?>" 
                            alt="<?php echo htmlspecialchars($director_data['photo_alt']); ?>" 
                            class="mg-director-photo"
                            loading="lazy"
                        >
                    </div>

                    <!-- Floating Executive Experience Badge -->
                    <div class="mg-director-badge-float">
                        <div class="mg-badge-float-icon">
                            <i class="fa-solid fa-medal"></i>
                        </div>
                        <div class="mg-badge-float-text">
                            <span class="mg-badge-float-num"><?php echo htmlspecialchars($director_data['experience']); ?></span>
                            <span class="mg-badge-float-sub"><?php echo htmlspecialchars($director_data['exp_label']); ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Column: Executive Message & Vision Pillars -->
            <div class="mg-director-content-col">
                
                <!-- Watermark Quote Icon in Background -->
                <i class="fa-solid fa-quote-right mg-quote-watermark" aria-hidden="true"></i>

                <!-- Kicker Pill -->
                <span class="mg-director-kicker">
                    <i class="fa-solid fa-feather-pointed"></i>
                    <?php echo htmlspecialchars($director_data['kicker']); ?>
                </span>

                <h2 class="mg-director-title-pre"><?php echo htmlspecialchars($director_data['title_pre']); ?></h2>
                <h3 class="mg-director-title-main">
                    <span>MG EDUCATION</span> & SOCIAL DEVELOPMENT ORG.
                </h3>

                <!-- Inspiring Quote Box -->
                <div class="mg-director-quote-box">
                    <p class="mg-director-quote-text">
                        <?php echo htmlspecialchars($director_data['quote_callout']); ?>
                    </p>
                </div>

                <!-- Main Message Paragraph -->
                <p class="mg-director-text">
                    <?php echo htmlspecialchars($director_data['message_intro']); ?>
                    <span class="mg-director-more-text" id="mgDirectorMoreText">
                        <?php echo htmlspecialchars($director_data['message_more']); ?>
                    </span>
                    <button type="button" class="mg-btn-readmore" id="mgDirectorReadMore">
                        <span>Read More</span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                </p>

                <!-- 3 Vision Pillar Cards (Micro-Metrics) -->
                <div class="mg-director-pillars">
                    <?php foreach ($director_data['pillars'] as $pillar): ?>
                        <div class="mg-pillar-card">
                            <i class="<?php echo $pillar['icon']; ?> mg-pillar-icon <?php echo $pillar['color']; ?>"></i>
                            <div class="mg-pillar-info">
                                <span class="mg-pillar-num"><?php echo htmlspecialchars($pillar['num']); ?></span>
                                <span class="mg-pillar-lbl"><?php echo htmlspecialchars($pillar['lbl']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Director Sign-off & Contact Button -->
                <div class="mg-director-signoff">
                    <div class="mg-director-identity">
                        <h4 class="mg-director-name"><?php echo htmlspecialchars($director_data['name']); ?></h4>
                        <p class="mg-director-designation"><?php echo htmlspecialchars($director_data['designation']); ?></p>
                    </div>

                    <a href="#contact" class="mg-btn-director-contact">
                        <i class="fa-regular fa-envelope"></i>
                        <span>Write to Director</span>
                    </a>
                </div>

            </div>

        </div>

    </div>
</section>

<!-- Interactive Read More Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mgDirectorReadMore');
    const moreText = document.getElementById('mgDirectorMoreText');

    if (btn && moreText) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const isShown = moreText.classList.toggle('show');
            const span = btn.querySelector('span');
            const icon = btn.querySelector('i');
            
            if (span) span.textContent = isShown ? 'Read Less' : 'Read More';
            if (icon) icon.className = isShown ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
        });
    }
});
</script>
