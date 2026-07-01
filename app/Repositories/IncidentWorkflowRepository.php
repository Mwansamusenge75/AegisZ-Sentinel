<?php
/**
 * AegisZ Sentinel - Incident Workflow Repository (v0.5.0)
 * Handles SQL for incident status transitions, notes, and workflow log.
 */

namespace App\Repositories;

use App\Core\Database;
use PDO;

class IncidentWorkflowRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Update incident status and optional assigned analyst.
     */
    public function updateStatus(int $incidentId, string $newStatus, ?int $assignedTo = null): bool
    {
        $sql    = "UPDATE incidents SET status = :status";
        $params = ['status' => $newStatus, 'id' => $incidentId];

        if ($assignedTo !== null) {
            $sql .= ", assigned_to = :assigned_to";
            $params['assigned_to'] = $assignedTo;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Log a workflow transition.
     */
    public function logTransition(int $incidentId, int $userId, string $fromStatus, string $toStatus, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO incident_workflow_log
             (incident_id, user_id, from_status, to_status, note)
             VALUES (:incident_id, :user_id, :from_status, :to_status, :note)"
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'user_id'     => $userId,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'note'        => $note,
        ]);
    }

    /**
     * Add an analyst note to an incident.
     */
    public function addNote(int $incidentId, int $userId, string $note): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO incident_notes (incident_id, user_id, note)
             VALUES (:incident_id, :user_id, :note)"
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'user_id'     => $userId,
            'note'        => $note,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Fetch all notes for an incident (newest first).
     */
    public function getNotesForIncident(int $incidentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, u.username
             FROM incident_notes n
             LEFT JOIN users u ON n.user_id = u.id
             WHERE n.incident_id = :incident_id
             ORDER BY n.created_at DESC"
        );
        $stmt->execute(['incident_id' => $incidentId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch workflow log for a specific incident.
     */
    public function getLogForIncident(int $incidentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT iwl.*, u.username
             FROM incident_workflow_log iwl
             LEFT JOIN users u ON iwl.user_id = u.id
             WHERE iwl.incident_id = :incident_id
             ORDER BY iwl.created_at DESC"
        );
        $stmt->execute(['incident_id' => $incidentId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all incidents with assigned analyst username joined.
     */
    public function findAllWithAnalyst(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT i.*, u.username AS assigned_username
                FROM incidents i
                LEFT JOIN users u ON i.assigned_to = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $sql .= " AND i.severity = :severity";
            $params['severity'] = $filters['severity'];
        }

        $sql .= " ORDER BY i.created_at DESC LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single incident with analyst username.
     */
    public function findByIdWithAnalyst(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, u.username AS assigned_username
             FROM incidents i
             LEFT JOIN users u ON i.assigned_to = u.id
             WHERE i.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
