<?php
/**
 * AegisZ Sentinel - Incident Workflow Service (v0.5.0)
 * Incident lifecycle management business logic.
 * Enforces valid status transitions. Notes can be added at any status.
 *
 * Incident lifecycle:
 *   open → investigating → contained → resolved → closed
 */

namespace App\Services\Workflow;

use App\Core\Logger;
use App\Domain\Incident\IncidentRepository;
use App\Repositories\IncidentWorkflowRepository;
use App\Repositories\AuditLogRepository;

class IncidentWorkflowService
{
    private const TRANSITIONS = [
        'open'          => ['investigating', 'closed'],
        'investigating' => ['contained', 'resolved', 'closed'],
        'contained'     => ['resolved', 'closed'],
        'resolved'      => ['closed'],
        'closed'        => [],
    ];

    private IncidentRepository $incidentRepo;
    private IncidentWorkflowRepository $workflowRepo;
    private AuditLogRepository $auditLog;
    private Logger $logger;

    public function __construct()
    {
        $this->incidentRepo = new IncidentRepository();
        $this->workflowRepo = new IncidentWorkflowRepository();
        $this->auditLog     = new AuditLogRepository();
        $this->logger       = new Logger();
    }

    /**
     * Transition an incident to a new status.
     */
    public function transition(int $incidentId, string $newStatus, int $userId, ?string $note = null, ?int $assignedTo = null): array
    {
        $incident = $this->incidentRepo->findById($incidentId);
        if (!$incident) {
            return ['success' => false, 'error' => 'Incident not found.'];
        }

        $currentStatus = $incident->status;

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            return [
                'success' => false,
                'error'   => "Cannot transition incident from '{$currentStatus}' to '{$newStatus}'.",
            ];
        }

        $this->workflowRepo->updateStatus($incidentId, $newStatus, $assignedTo);
        $this->workflowRepo->logTransition($incidentId, $userId, $currentStatus, $newStatus, $note);

        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'IncidentWorkflow',
            'message' => "Incident #{$incidentId} '{$incident->title}': {$currentStatus} → {$newStatus} by user ID {$userId}" . ($note ? " | Note: {$note}" : ''),
        ]);

        $this->logger->info("[IncidentWorkflow] Incident #{$incidentId}: {$currentStatus} → {$newStatus}", ['user' => $userId]);

        return ['success' => true, 'error' => null];
    }

    /**
     * Add an analyst note to an incident.
     */
    public function addNote(int $incidentId, int $userId, string $note): array
    {
        $note = trim($note);
        if (empty($note)) {
            return ['success' => false, 'error' => 'Note cannot be empty.'];
        }
        if (strlen($note) > 5000) {
            return ['success' => false, 'error' => 'Note must not exceed 5000 characters.'];
        }

        $incident = $this->incidentRepo->findById($incidentId);
        if (!$incident) {
            return ['success' => false, 'error' => 'Incident not found.'];
        }

        $noteId = $this->workflowRepo->addNote($incidentId, $userId, $note);

        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'IncidentWorkflow',
            'message' => "Note added to Incident #{$incidentId} by user ID {$userId}",
        ]);

        return ['success' => true, 'note_id' => $noteId];
    }

    public function isValidTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? []);
    }

    public function getAllowedTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    public function findAllWithAnalyst(array $filters = [], int $limit = 100): array
    {
        return $this->workflowRepo->findAllWithAnalyst($filters, $limit);
    }

    public function findByIdWithAnalyst(int $id): ?array
    {
        return $this->workflowRepo->findByIdWithAnalyst($id);
    }

    public function getNotes(int $incidentId): array
    {
        return $this->workflowRepo->getNotesForIncident($incidentId);
    }

    public function getWorkflowLog(int $incidentId): array
    {
        return $this->workflowRepo->getLogForIncident($incidentId);
    }
}
