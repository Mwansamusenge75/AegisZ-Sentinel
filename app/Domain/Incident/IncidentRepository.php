<?php
/**
 * AegisZ Sentinel - Incident Repository (PDO Implementation)
 */

namespace App\Domain\Incident;

use App\Core\Database;
use PDO;

class IncidentRepository implements IncidentRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(IncidentEntity $incident): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO incidents (title, status, severity, linked_alert_id, linked_asset_id, timeline)
             VALUES (:title, :status, :severity, :linked_alert_id, :linked_asset_id, :timeline)"
        );
        $stmt->execute([
            'title' => $incident->title,
            'status' => $incident->status,
            'severity' => $incident->severity,
            'linked_alert_id' => $incident->linkedAlertId,
            'linked_asset_id' => $incident->linkedAssetId,
            'timeline' => $incident->timeline ? json_encode($incident->timeline) : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?IncidentEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM incidents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? IncidentEntity::fromArray($data) : null;
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT * FROM incidents WHERE 1=1";
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

        return array_map(fn($row) => IncidentEntity::fromArray($row), $results);
    }

    public function update(IncidentEntity $incident): bool
    {
        if ($incident->id === null) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE incidents SET title = :title, status = :status, severity = :severity,
             linked_alert_id = :linked_alert_id, linked_asset_id = :linked_asset_id,
             timeline = :timeline WHERE id = :id"
        );
        return $stmt->execute([
            'id' => $incident->id,
            'title' => $incident->title,
            'status' => $incident->status,
            'severity' => $incident->severity,
            'linked_alert_id' => $incident->linkedAlertId,
            'linked_asset_id' => $incident->linkedAssetId,
            'timeline' => $incident->timeline ? json_encode($incident->timeline) : null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM incidents WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM incidents");
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM incidents GROUP BY status");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
