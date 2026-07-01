<?php
/**
 * AegisZ Sentinel - .env Loader (v0.7.0)
 * Minimal, dependency-free .env file parser.
 * Loads KEY=VALUE pairs from /.env into getenv()/$_ENV without external packages.
 * Required because v0.7.0 introduces the OpenRouter API key, which must
 * NEVER be committed to source control or hardcoded in config files.
 */

namespace App\Core;

class Env
{
    private static bool $loaded = false;

    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path ??= dirname(__DIR__, 2) . '/.env';

        if (!file_exists($path)) {
            self::$loaded = true;
            return; // No .env present — fall back to defaults in config files
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            // Strip surrounding quotes if present
            if (strlen($value) >= 2 && (
                ($value[0] === '"' && $value[-1] === '"') ||
                ($value[0] === "'" && $value[-1] === "'")
            )) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false && $value !== null ? $value : $default;
    }
}
