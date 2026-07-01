<?php
/**
 * AegisZ Sentinel - Incident Workflow Controller (v0.5.0)
 * Handles incident queue, detail view, status transitions, and notes.
 * HTTP only. No SQL. No business logic.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Services\Workflow\IncidentWorkflowService;
use App\Middleware\RoleMiddleware;

class IncidentWorkflowController extends BaseController
{
    private IncidentWorkflowService $workflowService;

    public function __construct()
    {
        parent::__construct();
        $this->workflowService = new IncidentWorkflowService();
    }

    /**
     * GET /incidents — Incident queue
     */
    public function index(): void
    {
        $filters = [
            'status'   => Security::sanitize($_GET['status'] ?? ''),
            'severity' => Security::sanitize($_GET['severity'] ?? ''),
        ];

        $incidents = $this->workflowService->findAllWithAnalyst($filters, 100);

        $this->render('incidents/index', [
            'title'     => 'Incidents | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.5.0',
            'incidents' => $incidents,
            'filters'   => $filters,
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /**
     * GET /incidents/detail?id=N — Single incident with notes timeline
     */
    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            Session::flash('error', 'Incident ID is required.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $incident = $this->workflowService->findByIdWithAnalyst($id);
        if (!$incident) {
            Session::flash('error', 'Incident not found.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $notes       = $this->workflowService->getNotes($id);
        $workflowLog = $this->workflowService->getWorkflowLog($id);
        $allowedNext = $this->workflowService->getAllowedTransitions($incident['status']);

        $this->render('incidents/detail', [
            'title'       => "Incident #{$id} | AegisZ Sentinel",
            'appName'     => 'AegisZ Sentinel',
            'version'     => '0.5.0',
            'incident'    => $incident,
            'notes'       => $notes,
            'workflowLog' => $workflowLog,
            'allowedNext' => $allowedNext,
            'csrfToken'   => Security::generateCsrfToken(),
        ]);
    }

    /**
     * POST /incidents/transition — Status transition
     */
    public function transition(): void
    {
        RoleMiddleware::requireRole('analyst');

        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token. Please try again.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $incidentId = (int) ($_POST['incident_id'] ?? 0);
        $newStatus  = Security::sanitize($_POST['new_status'] ?? '');
        $note       = Security::sanitize($_POST['note'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : null;

        if (!$incidentId || !$newStatus) {
            Session::flash('error', 'Missing required fields.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $userId = (int) ($this->currentUser['id'] ?? 0);
        $result = $this->workflowService->transition(
            $incidentId,
            $newStatus,
            $userId,
            $note ?: null,
            $assignedTo
        );

        if ($result['success']) {
            Session::flash('success', "Incident #{$incidentId} transitioned to " . ucfirst($newStatus) . '.');
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect($this->baseUrl . '/incidents/detail?id=' . $incidentId);
    }

    /**
     * POST /incidents/note — Add analyst note
     */
    public function addNote(): void
    {
        RoleMiddleware::requireRole('analyst');

        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $incidentId = (int) ($_POST['incident_id'] ?? 0);
        $note       = Security::sanitize($_POST['note'] ?? '');

        if (!$incidentId) {
            Session::flash('error', 'Incident ID is required.');
            $this->redirect($this->baseUrl . '/incidents');
            return;
        }

        $userId = (int) ($this->currentUser['id'] ?? 0);
        $result = $this->workflowService->addNote($incidentId, $userId, $note);

        if ($result['success']) {
            Session::flash('success', 'Note added successfully.');
        } else {
            Session::flash('error', $result['error']);
        }

        $this->redirect($this->baseUrl . '/incidents/detail?id=' . $incidentId);
    }
}
