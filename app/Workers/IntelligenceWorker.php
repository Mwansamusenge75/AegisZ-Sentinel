<?php

/**
 * AegisZ Sentinel v0.4.0 Intelligence Worker
 * CLI-only. Runs AFTER all ingestion workers.
 */

require_once __DIR__ . '/../../cli_bootstrap.php';

use App\Services\Correlation\ThreatCorrelationService;
use App\Services\Scoring\RiskScoringService;
use App\Services\ThreatIntel\MitreMappingService;
use App\Core\Database;
use App\Core\Logger;

$logger = new Logger();
$logger->info('INTEL_WORKER: Starting intelligence pipeline');

$startTime = microtime(true);

try {
    $logger->info('INTEL_WORKER: Step 1/4 - Threat Correlation');
    $correlationService = new ThreatCorrelationService();
    $correlationResults = $correlationService->runCorrelation();
    $totalCorrelations = array_sum(array_map('count', $correlationResults));

    $logger->info('INTEL_WORKER: Step 2/4 - Risk Scoring');
    $riskService = new RiskScoringService();
    $riskResult = $riskService->calculateScore();

    $logger->info('INTEL_WORKER: Step 3/4 - MITRE Mapping');
    $mitreService = new MitreMappingService();
    $mitreResults = $mitreService->runMapping();
    $totalMappings = count($mitreResults['threats']) + count($mitreResults['iocs']);

    $logger->info('INTEL_WORKER: Step 4/4 - Cache Dashboard Data');
    cacheDashboardData($correlationService, $riskService, $mitreService);

    $duration = round((microtime(true) - $startTime) * 1000, 2);

    $logger->info("INTEL_WORKER: Pipeline complete. Duration: {$duration}ms");
    $logger->info("INTEL_WORKER: Correlations: {$totalCorrelations}, Risk Score: {$riskResult['score']}, MITRE Mappings: {$totalMappings}");

    echo "Intelligence Worker Complete\n";
    echo "-----------------------------\n";
    echo "Correlations found: {$totalCorrelations}\n";
    echo "Risk Score: {$riskResult['score']}/100 (Grade: {$riskResult['grade']})\n";
    echo "MITRE Mappings: {$totalMappings}\n";
    echo "Duration: {$duration}ms\n";

    exit(0);

} catch (\Throwable $e) {
    $logger->error('INTEL_WORKER: Pipeline failed: ' . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

function cacheDashboardData(
    ThreatCorrelationService $correlationService,
    RiskScoringService $riskService,
    MitreMappingService $mitreService
): void {
    $db = Database::getInstance();

    $data = [
        'correlation_summary' => $correlationService->getCorrelationSummary(),
        'recent_correlations' => $correlationService->getRecentCorrelations(5),
        'latest_score' => $riskService->getLatestScore(),
        'score_history' => $riskService->getScoreHistory(24),
        'technique_distribution' => $mitreService->getTechniqueDistribution(),
        'recent_mitre' => $mitreService->getRecentMappings(5),
        'cached_at' => date('Y-m-d H:i:s')
    ];

    $stmt = $db->prepare(
        "INSERT INTO intelligence_dashboard_cache (cache_key, cache_value, expires_at)
         VALUES (:key, :value, DATE_ADD(NOW(), INTERVAL 1 HOUR))
         ON DUPLICATE KEY UPDATE 
         cache_value = VALUES(cache_value),
         expires_at = VALUES(expires_at),
         updated_at = NOW()"
    );
    $stmt->execute([
        ':key' => 'intelligence_overview',
        ':value' => json_encode($data)
    ]);
}