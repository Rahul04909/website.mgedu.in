<?php
/**
 * MG Education & Social Development Organization
 * Executive Admin Login Portal (admin/login.php)
 * 
 * Styled after Reference: Floating White Login Card + Eye Show/Hide Toggle + Vibrant Blue Aesthetic
 * Protected with: Firebase PHP-JWT, CSRF Shield, Rate Limiting, Brute Force Defense, Timing-Attack Safe Verification
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in with valid JWT, redirect straight to dashboard
if (getAuthenticatedAdmin()) {
    header("Location: index.php");
    exit;
}

$errorMessage = "";
$successMessage = "";
$loginInput = "";

// Handle Logout feedback
if (isset($_GET['logout'])) {
    $successMessage = "You have been successfully logged out.";
}

// Process POST Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken  = $_POST['csrf_token'] ?? '';
    $loginInput = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);
    $userIp     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // 1. Verify CSRF Token
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = "Security validation failed. Please refresh the page and try again.";
    } elseif (empty($loginInput) || empty($password)) {
        $errorMessage = "Please enter both your username/email and password.";
    } elseif (isLoginLockedOut($loginInput, $userIp)) {
        // 2. Brute-Force Rate Limiting Lockout
        $errorMessage = "Too many failed login attempts. This account is temporarily locked for 15 minutes for security.";
    } else {
        // 3. Authenticate with Database
        $pdo = getDB();
        if (!$pdo) {
            $errorMessage = "Database connection error. Please ensure MySQL is running.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE (username = :u OR email = :e) LIMIT 1");
            $stmt->execute([':u' => $loginInput, ':e' => $loginInput]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Check if account status is active
                if ($admin['status'] !== 'active') {
                    $errorMessage = "Your admin account is marked as {$admin['status']}. Please contact the system administrator.";
                } else {
                    // Reset failed attempt counters and log IP
                    resetFailedLogin((int)$admin['id'], $userIp);

                    // Issue Firebase JWT in HttpOnly Cookie & Session
                    issueAdminJWT($admin, $rememberMe);

                    // Redirect to intended page or index.php
                    $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                    header("Location: " . $redirect);
                    exit;
                }
            } else {
                // Record failed login attempt for rate-limiting
                $attempts = recordFailedLogin($loginInput);
                $remaining = max(0, 5 - $attempts);

                if ($attempts >= 5) {
                    $errorMessage = "Too many failed attempts. Account locked for 15 minutes.";
                } else {
                    $errorMessage = "Invalid username or password. ({$remaining} attempts remaining before temporary lockout)";
                }
            }
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login &bull; MG Education</title>
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Mukta:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --mg-blue-main: #0b63b6;
            --mg-blue-dark: #074682;
            --mg-blue-light: #257cd4;
            --mg-navy: #071d33;
            --mg-gold: #ffb703;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0b63b6 0%, #074682 50%, #04274c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Modern Background Geometric Accents matching reference aesthetic */
        .bg-shape-1 {
            position: absolute;
            top: -10%;
            right: -5%;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .bg-shape-2 {
            position: absolute;
            bottom: -15%;
            left: -10%;
            width: 650px;
            height: 650px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, rgba(56, 189, 248, 0) 70%);
            pointer-events: none;
        }

        /* Dual-Column Master Wrapper */
        .login-master-container {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 1040px;
            width: 100%;
            position: relative;
            z-index: 10;
            gap: 40px;
        }

        /* 1. Left White Floating Form Card (Exact match of reference image) */
        .login-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            padding: 38px 34px;
            position: relative;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Logo Badge on Top */
        .login-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .login-logo-badge {
            background: #ffffff;
            padding: 8px 18px;
            border-radius: 12px;
            border: 2px solid var(--mg-gold);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .login-logo-badge img {
            max-height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .login-card-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .login-card-subtitle {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
            font-weight: 500;
        }

        /* Form Labels & Inputs */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
            display: block;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control-custom {
            width: 100%;
            height: 48px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: 14.5px;
            font-weight: 600;
            color: #0f172a;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control-custom:focus {
            border-color: var(--mg-blue-main);
            box-shadow: 0 0 0 3.5px rgba(11, 99, 182, 0.15);
        }

        .input-icon-btn {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 6px;
            font-size: 15px;
            transition: color 0.15s ease;
        }

        .input-icon-btn:hover {
            color: var(--mg-blue-main);
        }

        /* Remember Me & Options */
        .login-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            margin-bottom: 22px;
            font-size: 13px;
        }

        .form-check-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            user-select: none;
            cursor: pointer;
        }

        .form-check-input {
            width: 17px;
            height: 17px;
            margin-top: 1px;
            cursor: pointer;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
        }

        .form-check-input:checked {
            background-color: var(--mg-blue-main);
            border-color: var(--mg-blue-main);
        }

        /* Submit Button matching reference */
        .btn-login-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #0b63b6 0%, #084e96 100%);
            color: #ffffff;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.22s ease;
            box-shadow: 0 4px 14px rgba(11, 99, 182, 0.35);
            letter-spacing: 0.2px;
        }

        .btn-login-submit:hover {
            background: linear-gradient(135deg, #084e96 0%, #053b74 100%);
            box-shadow: 0 6px 20px rgba(11, 99, 182, 0.45);
            transform: translateY(-1px);
        }

        .btn-login-submit:active {
            transform: scale(0.99);
        }

        /* Security Badges Footer under Form */
        .login-footer-links {
            margin-top: 24px;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
            padding-top: 16px;
        }

        .back-to-site-link {
            color: #0b63b6;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.18s ease;
        }

        .back-to-site-link:hover {
            color: #074682;
            text-decoration: underline;
        }

        /* 2. Right Side Executive Brand Showcase */
        .login-brand-panel {
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 500px;
        }

        .brand-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            color: var(--mg-gold);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            width: fit-content;
            margin-bottom: 18px;
            backdrop-filter: blur(8px);
        }

        .brand-main-heading {
            font-size: 34px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .brand-main-heading span {
            color: #38bdf8;
        }

        .brand-description {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .security-feature-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .security-feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
        }

        .security-icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            border: 1.5px solid var(--mg-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
            color: #ffffff;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .login-master-container {
                flex-direction: column;
            }
            .login-brand-panel {
                display: none; /* Mobile focuses directly on crisp login card */
            }
        }
    </style>
</head>
<body>

<div class="bg-shape-1"></div>
<div class="bg-shape-2"></div>

<div class="login-master-container">
    
    <!-- Left: Floating White Login Card (Exact match of user reference) -->
    <div class="login-card">
        <!-- Logo on Top -->
        <div class="login-logo-wrap">
            <div class="login-logo-badge">
                <img src="../assets/images/logo.jpg" alt="MG Education Logo">
            </div>
        </div>

        <h2 class="login-card-title">Admin Control Portal</h2>
        <p class="login-card-subtitle">Enter your credentials to access system console</p>

        <!-- Alerts -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size: 13px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle text-danger fs-5 flex-shrink-0"></i>
                <div><?= htmlspecialchars($errorMessage) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size: 13px; border-radius: 8px;">
                <i class="fas fa-check-circle text-success fs-5 flex-shrink-0"></i>
                <div><?= htmlspecialchars($successMessage) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" autocomplete="off">
            <!-- CSRF Shield -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <!-- Username or Email Address -->
            <div class="form-group">
                <label for="username" class="form-label">Username or Email Address</label>
                <div class="input-group-custom">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control-custom" 
                           placeholder="e.g. admin or admin@mgedu.in" 
                           value="<?= htmlspecialchars($loginInput) ?>" 
                           required 
                           autofocus>
                </div>
            </div>

            <!-- Password with Show/Hide Eye Toggle -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group-custom">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control-custom" 
                           placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" 
                           required>
                    <button type="button" class="input-icon-btn" id="togglePasswordBtn" title="Toggle password visibility">
                        <i class="far fa-eye" id="passwordEyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Options Row: Remember Me -->
            <div class="login-options-row">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" value="1">
                    <label class="form-check-label" for="remember_me">Remember Me</label>
                </div>
                <a href="javascript:void(0);" onclick="alert('Please contact the IT Helpdesk (+91-8059982049) to reset administrative access credentials.');" class="text-decoration-none" style="font-size: 12.5px; font-weight: 700; color: #0b63b6;">Lost Password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login-submit">
                <span>Log In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="login-footer-links">
            <a href="../" class="back-to-site-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Official Website</span>
            </a>
        </div>
    </div>

    <!-- Right: Executive Brand Panel (Desktop) -->
    <div class="login-brand-panel">
        <div class="brand-badge-pill">
            <i class="fas fa-certificate"></i>
            <span>ISO 9001:2015 &bull; NITI Aayog Darpan</span>
        </div>
        
        <h1 class="brand-main-heading">
            MG Education &amp; <span>Social Development</span> Organization
        </h1>
        
        <p class="brand-description">
            Secure administrative control portal for pan-India study centers, student admission verification, marksheet issuance, and institutional management.
        </p>

        <div class="security-feature-list">
            <div class="security-feature-item">
                <div class="security-icon-circle">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <span>Firebase JWT (HS256) Token Verification</span>
            </div>
            <div class="security-feature-item">
                <div class="security-icon-circle">
                    <i class="fas fa-lock"></i>
                </div>
                <span>Bcrypt Password Encryption (Cost 12)</span>
            </div>
            <div class="security-feature-item">
                <div class="security-icon-circle">
                    <i class="fas fa-user-shield"></i>
                </div>
                <span>Automated Brute-Force Rate Limiting &amp; Lockout</span>
            </div>
            <div class="security-feature-item">
                <div class="security-icon-circle">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <span>HttpOnly &amp; SameSite Secure Session Shield</span>
            </div>
        </div>
    </div>

</div>

<!-- Interactive Show/Hide Password Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const eyeIcon = document.getElementById('passwordEyeIcon');

        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    });
</script>

</body>
</html>

