<?php
/**
 * AegisZ Sentinel - Role Middleware (v0.5.0)
 * Enforces role-based access control after authentication is confirmed.
 *
 * Role hierarchy (highest to lowest):
 *   admin > analyst > viewer
 *
 * Usage in controllers:
 *   RoleMiddleware::requireRole('admin');
 *   RoleMiddleware::requireRole('analyst'); // allows analyst AND admin
 */

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;
use App\Core\Config;

class RoleMiddleware extends BaseMiddleware
{
    /**
     * Role hierarchy — roles at lower indices have MORE privileges.
     * A user with role[0] can access anything requiring role[1] or role[2].
     */
    private const ROLE_HIERARCHY = ['admin', 'analyst', 'viewer'];

    private string $requiredRole;

    public function __construct(string $requiredRole = 'viewer')
    {
        $this->requiredRole = $requiredRole;
    }

    public function handle(): void
    {
        $user = Session::getUser();

        if (!$user) {
            $this->deny(401, 'Authentication required.');
            return;
        }

        if (!$this->hasRole($user['role'], $this->requiredRole)) {
            $this->deny(403, 'You do not have permission to access this page.');
            return;
        }
    }

    /**
     * Static convenience method.
     * Checks if the current session user meets the required role.
     * Sends 403 and exits if not.
     */
    public static function requireRole(string $requiredRole): void
    {
        $instance = new self($requiredRole);
        $instance->handle();
    }

    /**
     * Return true if $userRole meets or exceeds $requiredRole in the hierarchy.
     */
    public static function hasRole(string $userRole, string $requiredRole): bool
    {
        $hierarchy = self::ROLE_HIERARCHY;
        $userIndex     = array_search($userRole, $hierarchy);
        $requiredIndex = array_search($requiredRole, $hierarchy);

        if ($userIndex === false || $requiredIndex === false) {
            return false;
        }

        // Lower index = more privileged
        return $userIndex <= $requiredIndex;
    }

    /**
     * Check role without enforcing (for view-layer conditionals).
     * Usage in views: RoleMiddleware::currentUserHasRole('admin')
     */
    public static function currentUserHasRole(string $requiredRole): bool
    {
        $user = Session::getUser();
        if (!$user) {
            return false;
        }
        return self::hasRole($user['role'], $requiredRole);
    }

    /**
     * Send an access denied response.
     */
    private function deny(int $code, string $message): void
    {
        http_response_code($code);
        $baseUrl = Config::get('app.base_url', '/aegisz-sentinel');
        $roleLabel = ucfirst($this->requiredRole);
        Session::flash('error', "{$message} This action requires {$roleLabel} role or higher. Your current role does not have access to create, edit, or delete here — viewing is still available.");
        Response::redirect($baseUrl . '/');
        exit;
    }
}
