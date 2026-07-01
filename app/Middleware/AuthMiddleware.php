<?php
/**
 * AegisZ Sentinel - Auth Middleware (v0.5.0)
 * Enforces authentication on all protected routes.
 * Called from BaseController constructor — every controller that extends
 * BaseController is automatically protected.
 *
 * Login page uses PublicBaseController which skips this check.
 */

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;
use App\Core\Config;

class AuthMiddleware extends BaseMiddleware
{
    public function handle(): void
    {
        Session::start();

        if (!Session::isAuthenticated()) {
            $baseUrl = Config::get('app.base_url', '/aegisz-sentinel');

            // Store the originally requested URL so we can redirect back after login
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? $baseUrl . '/');

            Response::redirect($baseUrl . '/login');
            exit;
        }

        // Check session has not expired (belt-and-suspenders beyond gc_maxlifetime)
        $user = Session::getUser();
        if (!empty($user['login_at'])) {
            $loginTime = strtotime($user['login_at']);
            if ((time() - $loginTime) > 3600) {
                Session::destroy();
                $baseUrl = Config::get('app.base_url', '/aegisz-sentinel');
                Session::flash('error', 'Your session has expired. Please log in again.');
                Response::redirect($baseUrl . '/login');
                exit;
            }
        }
    }

    /**
     * Static convenience method — call directly without instantiation.
     * Used in BaseController constructor.
     */
    public static function requireAuth(): void
    {
        $instance = new self();
        $instance->handle();
    }
}
