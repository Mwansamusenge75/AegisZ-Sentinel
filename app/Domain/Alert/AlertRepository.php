<?php
/**
 * AegisZ Sentinel - Alert Repository (PDO Implementation)
 */

namespace App\Domain\Alert;

use App\Core\Database;
use PDO;

class AlertRepository implements AlertRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(AlertEntity $alert): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alerts (title, severity, status, linked_ioc_id, linked_asset_id)
             VALUES (:title, :severity, :status, :linked_ioc_id, :linked_asset_id)"
        );
        $stmt->execute([
            'title' => $alert->title,
            'severity' => $alert->severity,
            'status' => $alert->status,
            'linked_ioc_id' => $alert->linkedIocId,
            'linked_asset_id' => $alert->linkedAssetId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?AlertEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM alerts WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? AlertEntity::fromArray($data) : null;
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT * FROM alerts WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND severity = :severity";
            $params['severity'] = $filters['severity'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        return array_map(fn($row) => AlertEntity::fromArray($row), $results);
    }

    public function update(AlertEntity $alert): bool
    {
        if ($alert->id === null) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE alerts SET title = :title, severity = :severity, status = :status,
             linked_ioc_id = :linked_ioc_id, linked_asset_id = :linked_asset_id WHERE id = :id"
        );
        return $stmt->execute([
            'id' => $alert->id,
            'title' => $alert->title,
            'severity' => $alert->severity,
            'status' => $alert->status,
            'linked_ioc_id' => $alert->linkedIocId,
            'linked_asset_id' => $alert->linkedAssetId,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alerts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM alerts");
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM alerts GROUP BY status");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
