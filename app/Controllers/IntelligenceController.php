<?php
/**
 * AegisZ Sentinel - Intelligence Controller (v0.4.0)
 * Serves the dedicated Intelligence page with full correlation list,
 * risk score breakdown, MITRE registry, and score history.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Services\Correlation\ThreatCorrelationService;
use App\Services\Scoring\RiskScoringService;
use App\Services\ThreatIntel\MitreMappingService;

class IntelligenceController extends BaseController
{
    private ThreatCorrelationService $correlationService;
    private RiskScoringService $scoringService;
    private MitreMappingService $mitreService;

    public function __construct()
    {
        parent::__construct();
        $this->correlationService = new ThreatCorrelationService();
        $this->scoringService     = new RiskScoringService();
        $this->mitreService       = new MitreMappingService();
    }

    public function index(): void
    {
        $this->logger->info('Intelligence page visited', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

        $data = [
            'title'              => 'Intelligence | AegisZ Sentinel',
            'appName'            => 'AegisZ Sentinel',
            'version'            => '0.4.0',
            'csrfToken'          => Security::generateCsrfToken(),
            'latestScore'        => $this->scoringService->getLatest(),
            'scoreHistory'       => $this->scoringService->getHistory(14),
            'recentCorrelations' => $this->correlationService->getRecent(25),
            'correlationsBySeverity' => $this->correlationService->countBySeverity(),
            'mitreDistribution'  => $this->mitreService->getTechniqueDistribution(),
            'mitreRegistry'      => $this->mitreService->getTechniqueRegistry(),
        ];

        $this->render('intelligence/index', $data);
    }
}
