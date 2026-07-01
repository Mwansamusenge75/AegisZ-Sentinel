<?php
/**
 * AegisZ Sentinel - Database Connection (PDO Singleton)
 * Secure, reusable database connection with prepared statement support.
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $db = $config['database'];

            $dsn = "mysql:host={$db['host']};dbname={$db['database']};charset={$db['charset']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE   => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ];

            try {
                self::$instance = new PDO($dsn, $db['username'], $db['password'], $options);
            } catch (PDOException $e) {
                error_log("[AEGISZ DB ERROR] " . $e->getMessage());
                throw new \Exception("Database connection failed. Check configuration.");
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
