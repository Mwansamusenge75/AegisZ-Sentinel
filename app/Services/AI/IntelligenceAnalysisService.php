<?php
/**
 * AegisZ Sentinel - Intelligence Analysis Service (v0.7.0)
 * Orchestrates AI-powered analysis: gathers READ-ONLY intelligence data,
 * builds prompts, calls OpenRouter, validates output, caches results.
 *
 * SECURITY BOUNDARY: this service only ever calls *Repository::find*() /
 * *::get*() / *::count*() read methods on operational domains. It has no
 * import of any write method (create/update/delete/transition) from any
 * Alert, Incident, Correlation, Threat, or RiskScore class. This is by
 * design — the AI cannot become a vector for unauthorized data changes.
 */

namespace App\Services\AI;

use App\Core\Logger;
use App\Repositories\AIAssessmentRepository;
use App\Domain\Threat\ThreatRepository;
use App\Domain\IOC\IOCRepository;
use App\Domain\Alert\AlertRepository;
use App\Domain\Incident\IncidentRepository;
use App\Services\Correlation\ThreatCorrelationService;
use App\Services\Scoring\RiskScoringService;
use App\Services\ThreatIntel\MitreMappingService;

class IntelligenceAnalysisService
{
    private OpenRouterClient        $client;
    private PromptBuilder           $promptBuilder;
    private OutputValidator         $validator;
    private AIAssessmentRepository  $cacheRepo;
    private Logger                  $logger;
    private array                   $config;

    public function __construct()
    {
        $this->client        = new OpenRouterClient();
        $this->promptBuilder = new PromptBuilder();
        $this->validator     = new OutputValidator();
        $this->cacheRepo     = new AIAssessmentRepository();
        $this->logger        = new Logger();
        $this->config        = require dirname(__DIR__, 3) . '/config/openrouter.php';
    }

    public function isEnabled(): bool
    {
        return $this->client->isEnabled();
    }

    /**
     * Get the current National Assessment, using cache unless stale or
     * $forceRefresh is true (Intelligence Worker passes true after a run).
     */
    public function getNationalAssessment(bool $forceRefresh = false): ?array
    {
        $maxAge = (int) ($this->config['assessment_cache_minutes'] ?? 20);

        if (!$forceRefresh && !$this->cacheRepo->isStale($maxAge)) {
            $cached = $this->cacheRepo->getLatestAssessment();
            if ($cached) {
                return $cached['payload'];
            }
        }

        if (!$this->isEnabled()) {
            return null; // AI not configured — caller shows graceful empty state
        }

        $snapshot = $this->buildIntelligenceSnapshot();
        $system   = $this->promptBuilder->systemPrompt();
        $prompt   = $this->promptBuilder->nationalAssessmentPrompt($snapshot);

        $raw    = $this->client->complete($system, $prompt);
        $result = $this->validator->validateAssessment($raw);

        if ($result === null) {
            $this->logger->warning('[AI] National assessment failed validation or generation');
            // Fall back to last good cached assessment rather than nothing
            $cached = $this->cacheRepo->getLatestAssessment();
            return $cached['payload'] ?? null;
        }

        $this->cacheRepo->saveAssessment($result, $this->config['model']);
        $this->logger->info('[AI] National assessment refreshed', ['threat_level' => $result['threat_level']]);

        return $result;
    }

    /**
     * Get an AI explanation for a single intelligence object. Cached
     * permanently per object (explanations of historical records don't
     * need to regenerate — the underlying record doesn't change once
     * ingested, except correlations/risk scores which are point-in-time
     * snapshots anyway).
     */
    public function explainObject(string $objectType, int $objectId, array $objectData): ?array
    {
        $cached = $this->cacheRepo->getCachedExplanation($objectType, $objectId);
        if ($cached) {
            return $cached['payload'];
        }

        if (!$this->isEnabled()) {
            return null;
        }

        $system = $this->promptBuilder->systemPrompt();
        $prompt = $this->promptBuilder->explanationPrompt($objectType, $objectData);

        $raw    = $this->client->complete($system, $prompt);
        $result = $this->validator->validateExplanation($raw);

        if ($result === null) {
            $this->logger->warning("[AI] Explanation failed for {$objectType}#{$objectId}");
            return null;
        }

        $this->cacheRepo->saveExplanation($objectType, $objectId, $result, $this->config['model']);
        return $result;
    }

    /**
     * Gather a read-only snapshot of current platform intelligence for the
     * national assessment prompt. Every call here is a *find/get/count*
     * read method — no writes are possible through this path.
     */
    private function buildIntelligenceSnapshot(): array
    {
        $threatRepo   = new ThreatRepository();
        $iocRepo      = new IOCRepository();
        $alertRepo    = new AlertRepository();
        $incidentRepo = new IncidentRepository();
        $corrService  = new ThreatCorrelationService();
        $scoreService = new RiskScoringService();
        $mitreService = new MitreMappingService();

        $latestScore = $scoreService->getLatest();

        return [
            'generated_at'            => date('c'),
            'security_posture_score'  => $latestScore['score']  ?? null,
            'security_posture_rating' => $latestScore['rating'] ?? null,
            'threats_by_severity'     => $threatRepo->countBySeverity(),
            'iocs_by_type'            => $iocRepo->countByType(),
            'alerts_by_status'        => $this->summarizeAlerts($alertRepo),
            'incidents_by_status'     => $this->summarizeIncidents($incidentRepo),
            'recent_correlations'     => array_map(
                fn($c) => [
                    'type'        => $c['correlation_type'] ?? null,
                    'severity'    => $c['severity'] ?? null,
                    'confidence'  => $c['confidence'] ?? null,
                    'explanation' => $c['explanation'] ?? null,
                ],
                $corrService->getRecent(10)
            ),
            'mitre_distribution' => $mitreService->getTechniqueDistribution(),
            'latest_threats'      => array_map(
                fn($t) => ['title' => $t['title'] ?? null, 'severity' => $t['severity'] ?? null, 'source' => $t['source_feed'] ?? null],
                $threatRepo->getLatest(10)
            ),
        ];
    }

    private function summarizeAlerts(AlertRepository $repo): array
    {
        $counts = $repo->getCounts();
        return $counts;
    }

    private function summarizeIncidents(IncidentRepository $repo): array
    {
        $counts = $repo->getCounts();
        return $counts;
    }
}
