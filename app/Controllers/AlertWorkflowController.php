<?php
/**
 * AegisZ Sentinel - Alert Workflow Controller (v0.5.0)
 * Handles alert queue listing and lifecycle transitions.
 * HTTP only. No SQL. No business logic.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Services\Workflow\AlertWorkflowService;
use App\Middleware\RoleMiddleware;

class AlertWorkflowController extends BaseController
{
    private AlertWorkflowService $workflowService;

    public function __construct()
    {
        parent::__construct();
        $this->workflowService = new AlertWorkflowService();
    }

    /**
     * GET /alerts — Alert queue
     */
    public function index(): void
    {
        $filters = [
            'status'   => Security::sanitize($_GET['status'] ?? ''),
            'severity' => Security::sanitize($_GET['severity'] ?? ''),
        ];

        $alerts = $this->workflowService->findAllWithAnalyst($filters, 100);

        $this->render('alerts/index', [
            'title'     => 'Alert Queue | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.5.0',
            'alerts'    => $alerts,
            'filters'   => $filters,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /**
     * POST /alerts/transition — Transition alert status
     * Required POST params: alert_id, new_status, _csrf
     * Optional: note, assigned_to
     */
    public function transition(): void
    {
        // Analysts and admins can transition
        RoleMiddleware::requireRole('analyst');

        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token. Please try again.');
            $this->redirect($this->baseUrl . '/alerts');
            return;
        }

        $alertId   = (int) ($_POST['alert_id'] ?? 0);
        $newStatus = Security::sanitize($_POST['new_status'] ?? '');
        $note      = Security::sanitize($_POST['note'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null;

        if (!$alertId || !$newStatus) {
            Session::flash('error', 'Missing required fields.');
            $this->redirect($this->baseUrl . '/alerts');
            return;
        }

        $userId = (int) ($this->currentUser['id'] ?? 0);
        $result = $this->workflowService->transition(
            $alertId,
            $newStatus,
            $userId,
            $note ?: null,
            $assignedTo
        );

        if ($result['success']) {
            Session::flash('success', "Alert #{$alertId} transitioned to " . ucfirst($newStatus) . '.');
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect($this->baseUrl . '/alerts');
    }
}
