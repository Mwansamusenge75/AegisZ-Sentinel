<?php
/**
 * AegisZ Sentinel - User Repository (v0.5.0)
 * PDO implementation. All SQL lives here — nowhere else.
 */

namespace App\Domain\User;

use App\Core\Database;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?UserEntity
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? UserEntity::fromArray($data) : null;
    }

    public function findByUsername(string $username): ?UserEntity
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch();
        return $data ? UserEntity::fromArray($data) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();
        return $data ? UserEntity::fromArray($data) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM users ORDER BY created_at DESC"
        );
        return array_map(
            fn($row) => UserEntity::fromArray($row),
            $stmt->fetchAll()
        );
    }

    public function create(UserEntity $user): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users
             (username, email, password_hash, role, status, full_name)
             VALUES (:username, :email, :password_hash, :role, :status, :full_name)"
        );
        $stmt->execute([
            'username'      => $user->username,
            'email'         => $user->email,
            'password_hash' => $user->passwordHash,
            'role'          => $user->role,
            'status'        => $user->status,
            'full_name'     => $user->fullName,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(UserEntity $user): bool
    {
        if ($user->id === null) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE users SET
             username  = :username,
             email     = :email,
             role      = :role,
             status    = :status,
             full_name = :full_name
             WHERE id  = :id"
        );
        return $stmt->execute([
            'id'        => $user->id,
            'username'  => $user->username,
            'email'     => $user->email,
            'role'      => $user->role,
            'status'    => $user->status,
            'full_name' => $user->fullName,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = :hash WHERE id = :id"
        );
        return $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = :status WHERE id = :id"
        );
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    public function countByRole(): array
    {
        $stmt = $this->db->query(
            "SELECT role, COUNT(*) AS count FROM users GROUP BY role"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
