<?php
/**
 * AegisZ Sentinel - AI API Controller (v0.7.0)
 * JSON endpoints for the AI Intelligence Layer.
 * Advisory only — no write access to operational data is possible through
 * this controller. All methods return read-only AI-generated analysis.
 */

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Security;
use App\Services\AI\IntelligenceAnalysisService;
use App\Middleware\RoleMiddleware;

class AIApiController extends BaseController
{
    private IntelligenceAnalysisService $aiService;

    public function __construct()
    {
        parent::__construct();
        $this->aiService = new IntelligenceAnalysisService();
    }

    /**
     * GET /api/ai/assessment
     * Returns cached national assessment (generates fresh if stale).
     */
    public function assessment(): void
    {
        if (!$this->aiService->isEnabled()) {
            $this->json([
                'success'   => false,
                'ai_enabled'=> false,
                'message'   => 'AI Intelligence Layer not configured. Add OPENROUTER_API_KEY to your .env file.',
            ]);
            return;
        }

        $result = $this->aiService->getNationalAssessment(forceRefresh: false);

        if ($result === null) {
            $this->json(['success' => false, 'message' => 'Assessment unavailable — check ai.log for details.'], 503);
            return;
        }

        $this->json(['success' => true, 'ai_enabled' => true, 'data' => $result]);
    }

    /**
     * GET /api/ai/explain?type=threat&id=5
     * Returns an AI explanation for a single intelligence object.
     * Supported types: threat, ioc, alert, incident, correlation
     */
    public function explain(): void
    {
        if (!$this->aiService->isEnabled()) {
            $this->json(['success' => false, 'ai_enabled' => false, 'message' => 'AI not configured.']);
            return;
        }

        $objectType = Security::sanitize($_GET['type'] ?? '');
        $objectId   = (int) ($_GET['id'] ?? 0);

        $allowedTypes = ['threat', 'ioc', 'alert', 'incident', 'correlation'];
        if (!in_array($objectType, $allowedTypes) || $objectId <= 0) {
            $this->json(['success' => false, 'message' => 'Valid type and id are required.'], 400);
            return;
        }

        // Fetch the raw object data from the appropriate domain repo.
        // All reads — no writes possible.
        $objectData = $this->fetchObjectData($objectType, $objectId);
        if ($objectData === null) {
            $this->json(['success' => false, 'message' => "{$objectType} #{$objectId} not found."], 404);
            return;
        }

        $result = $this->aiService->explainObject($objectType, $objectId, $objectData);
        if ($result === null) {
            $this->json(['success' => false, 'message' => 'AI explanation unavailable — check ai.log.'], 503);
            return;
        }

        $this->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/ai/refresh — force-refresh the national assessment.
     * Analyst role required (prevents viewer-triggered API cost).
     */
    public function refresh(): void
    {
        RoleMiddleware::requireRole('analyst');

        if (!$this->aiService->isEnabled()) {
            $this->json(['success' => false, 'message' => 'AI not configured.']);
            return;
        }

        $result = $this->aiService->getNationalAssessment(forceRefresh: true);
        if ($result === null) {
            $this->json(['success' => false, 'message' => 'Refresh failed — check ai.log.'], 503);
            return;
        }

        $this->json(['success' => true, 'data' => $result, 'message' => 'Assessment refreshed.']);
    }

    // ----------------------------------------------------------
    // Private: fetch object data for explanation prompts (read-only)
    // ----------------------------------------------------------

    private function fetchObjectData(string $type, int $id): ?array
    {
        return match ($type) {
            'threat'      => $this->fetchThreat($id),
            'ioc'         => $this->fetchIoc($id),
            'alert'       => $this->fetchAlert($id),
            'incident'    => $this->fetchIncident($id),
            'correlation' => $this->fetchCorrelation($id),
            default       => null,
        };
    }

    private function fetchThreat(int $id): ?array
    {
        $repo = new \App\Domain\Threat\ThreatRepository();
        $t    = $repo->findById($id);
        return $t ? $t->toArray() : null;
    }

    private function fetchIoc(int $id): ?array
    {
        $repo = new \App\Domain\IOC\IOCRepository();
        $i    = $repo->findById($id);
        return $i ? $i->toArray() : null;
    }

    private function fetchAlert(int $id): ?array
    {
        $repo = new \App\Domain\Alert\AlertRepository();
        $a    = $repo->findById($id);
        return $a ? $a->toArray() : null;
    }

    private function fetchIncident(int $id): ?array
    {
        $repo = new \App\Domain\Incident\IncidentRepository();
        $i    = $repo->findById($id);
        return $i ? $i->toArray() : null;
    }

    private function fetchCorrelation(int $id): ?array
    {
        $repo = new \App\Repositories\CorrelationRepository();
        return $repo->findById($id);
    }
}
