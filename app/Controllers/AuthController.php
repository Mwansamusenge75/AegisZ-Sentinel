<?php
/**
 * AegisZ Sentinel - Auth Controller (v0.5.0)
 * Handles GET /login, POST /login, POST /logout.
 * Extends PublicBaseController — no auth check on login route.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Domain\User\UserService;

class AuthController extends PublicBaseController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    /**
     * GET /login
     */
    public function loginForm(): void
    {
        // Already logged in — redirect to dashboard
        if (Session::isAuthenticated()) {
            $this->redirect($this->baseUrl . '/');
            return;
        }

        $this->render('auth/login', [
            'title'     => 'Login | AegisZ Sentinel',
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /**
     * POST /login
     */
    public function loginSubmit(): void
    {
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request. Please try again.');
            $this->redirect($this->baseUrl . '/login');
            return;
        }

        $username = Security::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->userService->authenticate($username, $password);

        if (!$result['success']) {
            Session::flash('error', $result['error']);
            $this->redirect($this->baseUrl . '/login');
            return;
        }

        // Store user in session
        Session::setUser($result['user']);

        // Redirect to originally requested URL or dashboard
        $redirect = Session::get('redirect_after_login', $this->baseUrl . '/');
        Session::remove('redirect_after_login');

        $this->redirect($redirect);
    }

    /**
     * POST /logout
     */
    public function logout(): void
    {
        $user = Session::getUser();
        if ($user) {
            $this->logger->info('[Auth] Logout: ' . $user['username']);
        }

        Session::destroy();
        $this->redirect($this->baseUrl . '/login');
    }
}
