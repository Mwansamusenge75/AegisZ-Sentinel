<?php
/**
 * AegisZ Sentinel - User Service (v0.5.0)
 * All authentication and user management business logic.
 * No SQL. No HTTP. Pure business rules.
 */

namespace App\Domain\User;

use App\Core\Logger;
use App\Repositories\AuditLogRepository;

class UserService
{
    private UserRepositoryInterface $repository;
    private Logger $logger;
    private AuditLogRepository $auditLog;

    public function __construct()
    {
        $this->repository = new UserRepository();
        $this->logger     = new Logger();
        $this->auditLog   = new AuditLogRepository();
    }

    // =========================================================
    // Authentication
    // =========================================================

    /**
     * Authenticate a user by username and password.
     * Returns user array on success, or error string on failure.
     */
    public function authenticate(string $username, string $password): array
    {
        $username = trim($username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required.'];
        }

        $user = $this->repository->findByUsername($username);

        if (!$user) {
            $this->logFailedLogin($username, 'User not found');
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        if (!$user->isActive()) {
            $this->logFailedLogin($username, "Account status: {$user->status}");
            return ['success' => false, 'error' => 'Your account is inactive or locked. Contact an administrator.'];
        }

        if (!password_verify($password, $user->passwordHash)) {
            $this->logFailedLogin($username, 'Wrong password');
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        // Update last login timestamp
        $this->repository->updateLastLogin($user->id);

        // Audit log
        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'Auth',
            'message' => "Login successful: {$username} (role: {$user->role})",
        ]);

        $this->logger->info("[Auth] Login: {$username}", ['role' => $user->role]);

        return ['success' => true, 'user' => $user->toPublicArray()];
    }

    /**
     * Log a failed login attempt for audit trail.
     */
    private function logFailedLogin(string $username, string $reason): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->auditLog->create([
            'level'   => 'WARNING',
            'source'  => 'Auth',
            'message' => "Failed login attempt for username '{$username}' from {$ip}. Reason: {$reason}",
        ]);
        $this->logger->warning("[Auth] Failed login for: {$username}", ['reason' => $reason, 'ip' => $ip]);
    }

    // =========================================================
    // User Management
    // =========================================================

    /**
     * Create a new user. Admin only.
     * Returns ['success' => bool, 'id' => int|null, 'errors' => array]
     */
    public function createUser(array $data, int $adminUserId): array
    {
        $errors = [];

        // Password validation
        $password = $data['password'] ?? '';
        $passwordErrors = $this->validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $errors[] = 'Passwords do not match.';
        }

        // Build entity for validation
        $entity               = new UserEntity();
        $entity->username     = trim($data['username'] ?? '');
        $entity->email        = trim($data['email'] ?? '');
        $entity->role         = $data['role'] ?? 'viewer';
        $entity->status       = $data['status'] ?? 'active';
        $entity->fullName     = trim($data['full_name'] ?? '') ?: null;
        $entity->passwordHash = ''; // placeholder — validated separately

        $entityErrors = $entity->validate();
        $errors = array_merge($errors, $entityErrors);

        // Check uniqueness
        if (!empty($entity->username) && $this->repository->findByUsername($entity->username)) {
            $errors[] = 'Username is already taken.';
        }
        if (!empty($entity->email) && $this->repository->findByEmail($entity->email)) {
            $errors[] = 'Email address is already registered.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $entity->passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $id = $this->repository->create($entity);

        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'UserAdmin',
            'message' => "User created: '{$entity->username}' (role: {$entity->role}) by admin user ID {$adminUserId}",
        ]);

        $this->logger->info("[UserAdmin] User created: {$entity->username}", ['id' => $id, 'by' => $adminUserId]);

        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    /**
     * Update an existing user's profile. Admin only.
     */
    public function updateUser(int $id, array $data, int $adminUserId): array
    {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['User not found.']];
        }

        $errors = [];

        $existing->username = trim($data['username'] ?? $existing->username);
        $existing->email    = trim($data['email'] ?? $existing->email);
        $existing->role     = $data['role'] ?? $existing->role;
        $existing->status   = $data['status'] ?? $existing->status;
        $existing->fullName = trim($data['full_name'] ?? '') ?: $existing->fullName;

        // Check uniqueness (exclude self)
        $byUsername = $this->repository->findByUsername($existing->username);
        if ($byUsername && $byUsername->id !== $id) {
            $errors[] = 'Username is already taken.';
        }
        $byEmail = $this->repository->findByEmail($existing->email);
        if ($byEmail && $byEmail->id !== $id) {
            $errors[] = 'Email address is already registered.';
        }

        $entityErrors = $existing->validate();
        $errors = array_merge($errors, $entityErrors);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($existing);

        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'UserAdmin',
            'message' => "User updated: '{$existing->username}' (ID: {$id}) by admin user ID {$adminUserId}",
        ]);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Change a user's password.
     * If $adminOverride is false, requires current password verification.
     */
    public function changePassword(int $userId, string $newPassword, string $confirmPassword, bool $adminOverride = false, ?string $currentPassword = null): array
    {
        $errors = $this->validatePasswordStrength($newPassword);

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$adminOverride && $currentPassword !== null) {
            $user = $this->repository->findById($userId);
            if (!$user || !password_verify($currentPassword, $user->passwordHash)) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->repository->updatePassword($userId, $hash);

        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'Auth',
            'message' => "Password changed for user ID {$userId}" . ($adminOverride ? ' (admin override)' : ''),
        ]);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Delete a user. Cannot delete yourself.
     */
    public function deleteUser(int $targetId, int $adminUserId): array
    {
        if ($targetId === $adminUserId) {
            return ['success' => false, 'errors' => ['You cannot delete your own account.']];
        }

        $user = $this->repository->findById($targetId);
        if (!$user) {
            return ['success' => false, 'errors' => ['User not found.']];
        }

        $this->repository->delete($targetId);

        $this->auditLog->create([
            'level'   => 'WARNING',
            'source'  => 'UserAdmin',
            'message' => "User deleted: '{$user->username}' (ID: {$targetId}) by admin user ID {$adminUserId}",
        ]);

        return ['success' => true, 'errors' => []];
    }

    public function findById(int $id): ?UserEntity
    {
        return $this->repository->findById($id);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    // =========================================================
    // Password Policy
    // =========================================================

    /**
     * Enforce password policy:
     * - Minimum 10 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     * - At least one special character
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        return $errors;
    }
}
