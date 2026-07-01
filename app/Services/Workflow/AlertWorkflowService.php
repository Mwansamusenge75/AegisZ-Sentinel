<?php
/**
 * AegisZ Sentinel - Alert Workflow Service (v0.5.0)
 * Alert lifecycle management business logic.
 * Enforces valid status transitions. Delegates SQL to repository.
 *
 * Alert lifecycle:
 *   open → acknowledged → assigned → escalated → resolved → closed
 */

namespace App\Services\Workflow;

use App\Core\Logger;
use App\Domain\Alert\AlertRepository;
use App\Repositories\AlertWorkflowRepository;
use App\Repositories\AuditLogRepository;

class AlertWorkflowService
{
    /**
     * Valid status transitions map.
     * key = current status, value = allowed next statuses
     */
    private const TRANSITIONS = [
        'open'         => ['acknowledged', 'closed'],
        'acknowledged' => ['assigned', 'resolved', 'closed'],
        'assigned'     => ['escalated', 'resolved', 'closed'],
        'escalated'    => ['resolved', 'closed'],
        'resolved'     => ['closed'],
        'closed'       => [],
    ];

    private AlertRepository $alertRepo;
    private AlertWorkflowRepository $workflowRepo;
    private AuditLogRepository $auditLog;
    private Logger $logger;

    public function __construct()
    {
        $this->alertRepo    = new AlertRepository();
        $this->workflowRepo = new AlertWorkflowRepository();
        $this->auditLog     = new AuditLogRepository();
        $this->logger       = new Logger();
    }

    /**
     * Transition an alert to a new status.
     * Returns ['success' => bool, 'error' => string|null]
     */
    public function transition(int $alertId, string $newStatus, int $userId, ?string $note = null, ?int $assignedTo = null): array
    {
        $alert = $this->alertRepo->findById($alertId);
        if (!$alert) {
            return ['success' => false, 'error' => 'Alert not found.'];
        }

        $currentStatus = $alert->status;

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            return [
                'success' => false,
                'error'   => "Cannot transition alert from '{$currentStatus}' to '{$newStatus}'.",
            ];
        }

        // Update alert status (and optional assignment)
        $this->workflowRepo->updateStatus($alertId, $newStatus, $assignedTo);

        // Log the transition
        $this->workflowRepo->logTransition($alertId, $userId, $currentStatus, $newStatus, $note);

        // Audit log
        $this->auditLog->create([
            'level'   => 'INFO',
            'source'  => 'AlertWorkflow',
            'message' => "Alert #{$alertId} '{$alert->title}': {$currentStatus} → {$newStatus} by user ID {$userId}" . ($note ? " | Note: {$note}" : ''),
        ]);

        $this->logger->info("[AlertWorkflow] Alert #{$alertId}: {$currentStatus} → {$newStatus}", ['user' => $userId]);

        return ['success' => true, 'error' => null];
    }

    /**
     * Check if a status transition is valid.
     */
    public function isValidTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? []);
    }

    /**
     * Get allowed next statuses for a given current status.
     */
    public function getAllowedTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Fetch all alerts with analyst usernames.
     */
    public function findAllWithAnalyst(array $filters = [], int $limit = 100): array
    {
        return $this->workflowRepo->findAllWithAnalyst($filters, $limit);
    }

    /**
     * Fetch workflow history for an alert.
     */
    public function getWorkflowLog(int $alertId): array
    {
        return $this->workflowRepo->getLogForAlert($alertId);
    }
}
