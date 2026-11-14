<?php
/**
 * MG Education & Social Development Organization
 * Global Configuration & Environment Loader
 * 
 * Uses vlucas/phpdotenv to load .env variables into $_ENV, $_SERVER, and getenv()
 * Provides a universal env($key, $default) helper function.
 */

// Prevent multiple inclusions
if (defined('MG_CONFIG_LOADED')) {
    return;
}
define('MG_CONFIG_LOADED', true);

// 1. Root Directory Path
define('ROOT_PATH', dirname(__DIR__));

// 2. Load Composer Autoloader
$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 3. Initialize vlucas/phpdotenv if installed
if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->safeLoad();
    } catch (Throwable $e) {
        error_log("Failed to load .env: " . $e->getMessage());
    }
}

// 4. Universal env() Helper Function
if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable with type-casting and default fallback.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        switch (strtolower(trim($value))) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        // Remove surrounding quotes if present
        if (strlen($value) > 1 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

// 5. Configure Timezone
$appTimezone = env('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set($appTimezone);

// 6. Configure Error Reporting based on APP_DEBUG
if (env('APP_DEBUG', true)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

