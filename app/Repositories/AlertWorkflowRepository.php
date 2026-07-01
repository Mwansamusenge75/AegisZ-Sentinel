<?php
/**
 * AegisZ Sentinel - Alert Workflow Repository (v0.5.0)
 * Handles SQL for alert status transitions and workflow log.
 * All SQL lives here. Controllers and Services never touch SQL.
 */

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AlertWorkflowRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Update alert status and assigned analyst.
     */
    public function updateStatus(int $alertId, string $newStatus, ?int $assignedTo = null): bool
    {
        $sql = "UPDATE alerts SET status = :status";
        $params = ['status' => $newStatus, 'id' => $alertId];

        if ($assignedTo !== null) {
            $sql .= ", assigned_to = :assigned_to";
            $params['assigned_to'] = $assignedTo;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Log a workflow transition to the audit table.
     */
    public function logTransition(int $alertId, int $userId, string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alert_workflow_log
             (alert_id, user_id, from_status, to_status, note)
             VALUES (:alert_id, :user_id, :from_status, :to_status, :note)"
        );
        $stmt->execute([
            'alert_id'    => $alertId,
            'user_id'     => $userId,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'note'        => $note,
        ]);
    }

    /**
     * Fetch workflow log for a specific alert.
     */
    public function getLogForAlert(int $alertId): array
    {
        $stmt = $this->db->prepare(
            "SELECT awl.*, u.username
             FROM alert_workflow_log awl
             LEFT JOIN users u ON awl.user_id = u.id
             WHERE awl.alert_id = :alert_id
             ORDER BY awl.created_at DESC"
        );
        $stmt->execute(['alert_id' => $alertId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all alerts with assigned analyst username joined.
     */
    public function findAllWithAnalyst(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT a.*, u.username AS assigned_username
                FROM alerts a
                LEFT JOIN users u ON a.assigned_to = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND a.severity = :severity";
            $params['severity'] = $filters['severity'];
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
