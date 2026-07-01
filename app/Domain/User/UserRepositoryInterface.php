<?php
/**
 * AegisZ Sentinel - User Repository Interface (v0.5.0)
 */

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?UserEntity;
    public function findByUsername(string $username): ?UserEntity;
    public function findByEmail(string $email): ?UserEntity;
    public function findAll(): array;
    public function create(UserEntity $user): int;
    public function update(UserEntity $user): bool;
    public function updatePassword(int $id, string $passwordHash): bool;
    public function updateLastLogin(int $id): bool;
    public function updateStatus(int $id, string $status): bool;
    public function delete(int $id): bool;
    public function count(): int;
}
