<?php
/**
 * AegisZ Sentinel - Session Manager (v0.5.0)
 * Wraps PHP native sessions. Provides a clean API for auth session management.
 * Handles session start, ID regeneration (fixation prevention), and destruction.
 */

namespace App\Core;

class Session
{
    /**
     * Start the session if not already started.
     * Applies secure session configuration.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $sessionName = $config['security']['session_name'] ?? 'AEGISZ_SESSION';

        // Harden session cookie
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false, // Set true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        ini_set('session.gc_maxlifetime', 3600);
        ini_set('session.use_strict_mode', '1');

        session_start();
    }

    /**
     * Get a value from the session.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a value in the session.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a key exists in the session.
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate the session ID — call on login to prevent session fixation.
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Destroy the session completely — call on logout.
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Store a one-time flash message (shown once, then cleared).
     */
    public static function flash(string $key, string $message): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $message;
    }

    /**
     * Read and clear a flash message.
     */
    public static function getFlash(string $key): ?string
    {
        self::start();
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    /**
     * Store the authenticated user in session.
     */
    public static function setUser(array $user): void
    {
        self::start();
        self::regenerate();
        $_SESSION['auth_user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get the authenticated user array, or null if not logged in.
     */
    public static function getUser(): ?array
    {
        self::start();
        return $_SESSION['auth_user'] ?? null;
    }

    /**
     * Check if a user is currently authenticated.
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return isset($_SESSION['auth_user']['id']);
    }

    /**
     * Clear the authenticated user from session (without full destroy).
     */
    public static function clearUser(): void
    {
        self::start();
        unset($_SESSION['auth_user']);
    }
}
