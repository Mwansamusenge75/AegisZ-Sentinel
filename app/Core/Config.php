<?php
/**
 * AegisZ Sentinel - Config Loader
 * Simple wrapper around the configuration array.
 */

namespace App\Core;

class Config
{
    private static array $config = [];

    public static function load(): void
    {
        self::$config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
