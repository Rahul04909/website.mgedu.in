<?php
/**
 * MG Education & Social Development Organization
 * Database Connection Module (PDO)
 * Powered by Environment Variables (.env)
 */

require_once __DIR__ . '/config.php';

if (!function_exists('getDB')) {
    /**
     * Returns a singleton PDO database connection instance.
     *
     * @return PDO|null
     */
    function getDB(): ?PDO
    {
        static $pdoInstance = null;

        if ($pdoInstance !== null) {
            return $pdoInstance;
        }

        $host     = env('DB_HOST', '127.0.0.1');
        $port     = env('DB_PORT', '3306');
        $database = env('DB_DATABASE', 'mgedu_db');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $charset  = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdoInstance = new PDO($dsn, $username, $password, $options);
            return $pdoInstance;
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            if (env('APP_DEBUG', true)) {
                // If in local/debug mode, don't crash the entire page if DB doesn't exist yet
                // Return null so frontend can still display with static/fallback data
                return null;
            }
            return null;
        }
    }
}

