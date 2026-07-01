<?php
/**
 * AegisZ Sentinel - Security Helpers
 * XSS escaping, input validation skeleton, CSRF structure.
 */

namespace App\Core;

class Security
{
    /**
     * Escape HTML entities to prevent XSS.
     */
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Basic input sanitization.
     */
    public static function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Validate email format.
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * CSRF token generation structure.
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION[Config::get('security.csrf_token_name', 'aegisz_csrf_token')] = $token;
        return $token;
    }

    /**
     * CSRF token validation structure.
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionToken = $_SESSION[Config::get('security.csrf_token_name', 'aegisz_csrf_token')] ?? null;
        return $sessionToken !== null && hash_equals($sessionToken, $token ?? '');
    }

    /**
     * Basic session manager structure.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            session_name(Config::get('security.session_name', 'AEGISZ_SESSION'));
            session_start();
        }
    }
}
