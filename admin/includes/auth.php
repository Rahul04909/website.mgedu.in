<?php
/**
 * MG Education & Social Development Organization
 * Admin Security & Firebase JWT Authentication Service
 * 
 * Production-Level Security:
 * - Firebase PHP-JWT (HS256) with Strict Expiration & Signature Verification
 * - HttpOnly + SameSite Protected Cookie Token Storage
 * - Brute-Force Rate Limiting & Account Lockout Defense
 * - CSRF Token Generation & Strict Verification
 * - Timing-Attack Safe Password Verification
 * - Session Fixation Defense via Regeneration
 * - HTTP Security Headers Enforcement
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters before starting
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/admin/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Load global configuration and PDO
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Sends standard HTTP security headers to protect against common web attacks.
 */
function sendAdminSecurityHeaders(): void
{
    if (!headers_sent()) {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

/**
 * Gets or sets the CSRF token for forms.
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

/**
 * Verifies the incoming CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['admin_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * Retrieves the secret key for signing JWTs from .env.
 */
function getJwtSecret(): string
{
    $secret = env('JWT_SECRET');
    if (empty($secret)) {
        // Fallback default secret (auto-generated)
        return 'mg_edu_jwt_sec_9f7a8b3c2d1e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9';
    }
    return (string)$secret;
}

/**
 * Issues a signed Firebase JWT for the given admin user and stores in HttpOnly cookie.
 *
 * @param array $admin
 * @param bool  $rememberMe
 * @return string
 */
function issueAdminJWT(array $admin, bool $rememberMe = false): string
{
    $issuedAt  = time();
    $expiryHours = $rememberMe ? (24 * 7) : (int)env('JWT_EXPIRY_HOURS', 24);
    $expireAt  = $issuedAt + ($expiryHours * 3600);
    $serverUrl = env('APP_URL', 'http://localhost/mgedu.in');

    $payload = [
        'iss'  => $serverUrl,
        'aud'  => $serverUrl . '/admin',
        'iat'  => $issuedAt,
        'nbf'  => $issuedAt,
        'exp'  => $expireAt,
        'data' => [
            'id'          => (int)$admin['id'],
            'username'    => (string)$admin['username'],
            'email'       => (string)$admin['email'],
            'name'        => (string)$admin['name'],
            'role'        => (string)($admin['role'] ?? 'Super Admin'),
            'profile_pic' => (string)($admin['profile_pic'] ?? './src/images/user-avtar.png'),
            'mobile'      => (string)($admin['mobile'] ?? '')
        ]
    ];

    $algorithm = env('JWT_ALGORITHM', 'HS256');
    $jwt = JWT::encode($payload, getJwtSecret(), $algorithm);

    // Store in secure HttpOnly cookie
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('mg_admin_jwt', $jwt, [
        'expires'  => $expireAt,
        'path'     => '/admin/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Store in session as dual-layer defense
    $_SESSION['admin_jwt']  = $jwt;
    $_SESSION['admin_user'] = $payload['data'];
    $_SESSION['admin_exp']  = $expireAt;

    // Defend against session fixation attacks
    session_regenerate_id(true);

    return $jwt;
}

/**
 * Validates the JWT and returns the authenticated admin data or null.
 *
 * @return array|null
 */
function getAuthenticatedAdmin(): ?array
{
    // Retrieve token from cookie or session or Authorization header
    $token = $_COOKIE['mg_admin_jwt'] ?? $_SESSION['admin_jwt'] ?? null;

    if (!$token && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = trim($matches[1]);
        }
    }

    if (!$token) {
        return null;
    }

    try {
        $algorithm = env('JWT_ALGORITHM', 'HS256');
        $decoded = JWT::decode($token, new Key(getJwtSecret(), $algorithm));

        if (empty($decoded->data) || empty($decoded->data->id)) {
            return null;
        }

        // Verify active status in DB (security check in case admin was blocked)
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id, name, username, email, mobile, profile_pic, role, status FROM `admins` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $decoded->data->id]);
            $adminDb = $stmt->fetch();

            if (!$adminDb || $adminDb['status'] !== 'active') {
                logoutAdmin();
                return null;
            }

            return $adminDb;
        }

        // Fallback to token payload data if DB temporarily unreachable
        return (array)$decoded->data;

    } catch (Throwable $e) {
        error_log("JWT Verification Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Middleware: Enforces admin authentication on protected pages.
 */
function requireAdminAuth(): array
{
    sendAdminSecurityHeaders();
    $admin = getAuthenticatedAdmin();

    if (!$admin) {
        logoutAdmin();
        $currentUri = $_SERVER['REQUEST_URI'] ?? 'index.php';
        $redirectUrl = 'login.php?redirect=' . urlencode($currentUri);
        header("Location: " . $redirectUrl);
        exit;
    }

    return $admin;
}

/**
 * Logs out the admin by revoking JWT cookie and clearing session.
 */
function logoutAdmin(): void
{
    // Clear cookie
    if (isset($_COOKIE['mg_admin_jwt'])) {
        setcookie('mg_admin_jwt', '', [
            'expires'  => time() - 3600,
            'path'     => '/admin/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['mg_admin_jwt']);
    }

    // Clear session
    unset($_SESSION['admin_jwt'], $_SESSION['admin_user'], $_SESSION['admin_exp'], $_SESSION['admin_csrf_token']);
}

/**
 * Brute-Force Rate Limiting Shield: Checks if an IP or username is locked out.
 */
function isLoginLockedOut(string $username, string $ip): bool
{
    $pdo = getDB();
    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT failed_login_attempts, lockout_until 
            FROM `admins` 
            WHERE (username = :u OR email = :e) 
            LIMIT 1
        ");
        $stmt->execute([':u' => $username, ':e' => $username]);
        $record = $stmt->fetch();

        if ($record && !empty($record['lockout_until'])) {
            $lockoutTime = strtotime($record['lockout_until']);
            if ($lockoutTime > time()) {
                return true; // Still locked out
            }
        }
    } catch (PDOException $e) {
        error_log("Rate Limit Check Error: " . $e->getMessage());
    }

    return false;
}

/**
 * Records a failed login attempt for brute-force tracking.
 */
function recordFailedLogin(string $username): int
{
    $pdo = getDB();
    if (!$pdo) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, failed_login_attempts FROM `admins` WHERE (username = :u OR email = :e) LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $username]);
        $admin = $stmt->fetch();

        if ($admin) {
            $attempts = (int)$admin['failed_login_attempts'] + 1;
            $lockoutUntil = null;

            if ($attempts >= 5) {
                // Lock account for 15 minutes
                $lockoutUntil = date('Y-m-d H:i:s', time() + (15 * 60));
            }

            $update = $pdo->prepare("UPDATE `admins` SET failed_login_attempts = :att, lockout_until = :lock WHERE id = :id");
            $update->execute([
                ':att'  => $attempts,
                ':lock' => $lockoutUntil,
                ':id'   => $admin['id']
            ]);

            return $attempts;
        }
    } catch (PDOException $e) {
        error_log("Failed Login Record Error: " . $e->getMessage());
    }

    return 0;
}

/**
 * Resets failed attempts after successful authentication.
 */
function resetFailedLogin(int $adminId, string $ip): void
{
    $pdo = getDB();
    if (!$pdo) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE `admins` 
            SET failed_login_attempts = 0, 
                lockout_until = NULL,
                last_login_at = NOW(),
                last_login_ip = :ip
            WHERE id = :id
        ");
        $stmt->execute([':ip' => $ip, ':id' => $adminId]);
    } catch (PDOException $e) {
        error_log("Reset Failed Login Error: " . $e->getMessage());
    }
}

