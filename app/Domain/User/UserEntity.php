<?php
/**
 * AegisZ Sentinel - User Entity (v0.5.0)
 * Value object representing a platform user.
 * Password hash is NEVER stored in plain text anywhere in the application.
 */

namespace App\Domain\User;

class UserEntity
{
    public ?int    $id           = null;
    public string  $username     = '';
    public string  $email        = '';
    public string  $passwordHash = '';
    public string  $role         = 'viewer';
    public string  $status       = 'active';
    public ?string $fullName     = null;
    public ?string $lastLoginAt  = null;
    public ?string $createdAt    = null;
    public ?string $updatedAt    = null;

    public static function fromArray(array $data): self
    {
        $entity               = new self();
        $entity->id           = isset($data['id']) ? (int) $data['id'] : null;
        $entity->username     = $data['username'] ?? '';
        $entity->email        = $data['email'] ?? '';
        $entity->passwordHash = $data['password_hash'] ?? '';
        $entity->role         = $data['role'] ?? 'viewer';
        $entity->status       = $data['status'] ?? 'active';
        $entity->fullName     = $data['full_name'] ?? null;
        $entity->lastLoginAt  = $data['last_login_at'] ?? null;
        $entity->createdAt    = $data['created_at'] ?? null;
        $entity->updatedAt    = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'email'         => $this->email,
            'password_hash' => $this->passwordHash,
            'role'          => $this->role,
            'status'        => $this->status,
            'full_name'     => $this->fullName,
            'last_login_at' => $this->lastLoginAt,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];
    }

    /**
     * Safe public representation — never includes password hash.
     */
    public function toPublicArray(): array
    {
        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'email'         => $this->email,
            'role'          => $this->role,
            'status'        => $this->status,
            'full_name'     => $this->fullName,
            'last_login_at' => $this->lastLoginAt,
            'created_at'    => $this->createdAt,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->username))) {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $this->username)) {
            $errors[] = 'Username must be 3–50 characters and contain only letters, numbers, and underscores.';
        }

        if (empty(trim($this->email))) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (!in_array($this->role, ['admin', 'analyst', 'viewer'])) {
            $errors[] = 'Invalid role. Must be admin, analyst, or viewer.';
        }

        if (!in_array($this->status, ['active', 'inactive', 'locked'])) {
            $errors[] = 'Invalid status.';
        }

        return $errors;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
