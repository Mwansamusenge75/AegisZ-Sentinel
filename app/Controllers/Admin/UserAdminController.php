<?php
/**
 * AegisZ Sentinel - User Admin Controller (v0.5.0)
 * User management — list, create, edit, delete. Admin role only.
 * HTTP only. No SQL. No business logic.
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Security;
use App\Core\Session;
use App\Domain\User\UserService;
use App\Middleware\RoleMiddleware;

class UserAdminController extends BaseController
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        RoleMiddleware::requireRole('admin');
        $this->userService = new UserService();
    }

    /**
     * GET /admin/users — User list
     */
    public function index(): void
    {
        $users = $this->userService->findAll();

        $this->render('admin/users/index', [
            'title'   => 'User Management | AegisZ Sentinel',
            'appName' => 'AegisZ Sentinel',
            'version' => '0.5.0',
            'users'   => $users,
        ]);
    }

    /**
     * GET /admin/users/create — Create user form
     */
    public function create(): void
    {
        $this->render('admin/users/create', [
            'title'     => 'Create User | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.5.0',
            'csrfToken' => Security::generateCsrfToken(),
            'errors'    => [],
            'old'       => [],
        ]);
    }

    /**
     * POST /admin/users/store — Save new user
     */
    public function store(): void
    {
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/admin/users/create');
            return;
        }

        $adminId = (int) ($this->currentUser['id'] ?? 0);
        $result  = $this->userService->createUser($_POST, $adminId);

        if (!$result['success']) {
            $this->render('admin/users/create', [
                'title'     => 'Create User | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.5.0',
                'csrfToken' => Security::generateCsrfToken(),
                'errors'    => $result['errors'],
                'old'       => $_POST,
            ]);
            return;
        }

        Session::flash('success', 'User created successfully.');
        $this->redirect($this->baseUrl . '/admin/users');
    }

    /**
     * GET /admin/users/edit?id=N — Edit user form
     */
    public function edit(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userService->findById($id);

        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirect($this->baseUrl . '/admin/users');
            return;
        }

        $this->render('admin/users/edit', [
            'title'     => 'Edit User | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.5.0',
            'user'      => $user,
            'csrfToken' => Security::generateCsrfToken(),
            'errors'    => [],
        ]);
    }

    /**
     * POST /admin/users/update — Save user changes
     */
    public function update(): void
    {
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/admin/users');
            return;
        }

        $id      = (int) ($_POST['user_id'] ?? 0);
        $adminId = (int) ($this->currentUser['id'] ?? 0);
        $result  = $this->userService->updateUser($id, $_POST, $adminId);

        if (!$result['success']) {
            $user = $this->userService->findById($id);
            $this->render('admin/users/edit', [
                'title'     => 'Edit User | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.5.0',
                'user'      => $user,
                'csrfToken' => Security::generateCsrfToken(),
                'errors'    => $result['errors'],
            ]);
            return;
        }

        Session::flash('success', 'User updated successfully.');
        $this->redirect($this->baseUrl . '/admin/users');
    }

    /**
     * POST /admin/users/delete — Delete a user
     */
    public function delete(): void
    {
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/admin/users');
            return;
        }

        $id      = (int) ($_POST['user_id'] ?? 0);
        $adminId = (int) ($this->currentUser['id'] ?? 0);
        $result  = $this->userService->deleteUser($id, $adminId);

        if ($result['success']) {
            Session::flash('success', 'User deleted successfully.');
        } else {
            Session::flash('error', implode(' ', $result['errors']));
        }

        $this->redirect($this->baseUrl . '/admin/users');
    }

    /**
     * POST /admin/users/password — Admin password reset for any user
     */
    public function resetPassword(): void
    {
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/admin/users');
            return;
        }

        $userId  = (int) ($_POST['user_id'] ?? 0);
        $adminId = (int) ($this->currentUser['id'] ?? 0);

        $result = $this->userService->changePassword(
            $userId,
            $_POST['password'] ?? '',
            $_POST['password_confirm'] ?? '',
            true  // admin override — skips current password check
        );

        if ($result['success']) {
            Session::flash('success', 'Password reset successfully.');
        } else {
            Session::flash('error', implode(' ', $result['errors']));
        }

        $this->redirect($this->baseUrl . '/admin/users/edit?id=' . $userId);
    }
}
