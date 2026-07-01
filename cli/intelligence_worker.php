<?php
/**
 * AegisZ Sentinel - Intelligence Worker (v0.4.0)
 * CLI-only. Must run AFTER all ingestion workers complete.
 *
 * Pipeline position:
 *   UrlHausWorker → OTXWorker → NvdWorker → CisaWorker → IntelligenceWorker
 *
 * Responsibilities:
 *   1. Map new threats to MITRE ATT&CK
 *   2. Correlate IOCs × Assets × Alerts × Incidents
 *   3. Calculate Security Posture Score
 *   4. Persist all results
 *   5. Log execution
 *
 * Run via: php cli/intelligence_worker.php
 * This script MUST NOT load the HTTP router.
 */

require __DIR__ . '/../cli_bootstrap.php';

use App\Services\ThreatIntel\MitreMappingService;
use App\Services\Correlation\ThreatCorrelationService;
use App\Services\Scoring\RiskScoringService;
use App\Services\IntelligenceBus\IntelligenceBusService;
use App\Services\AI\IntelligenceAnalysisService;
use App\Core\Logger;

class IntelligenceWorker
{
    private Logger $logger;
    private MitreMappingService $mitreService;
    private ThreatCorrelationService $correlationService;
    private RiskScoringService $scoringService;
    private IntelligenceBusService $bus;
    private IntelligenceAnalysisService $aiService;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->mitreService       = new MitreMappingService();
        $this->correlationService = new ThreatCorrelationService();
        $this->scoringService     = new RiskScoringService();
        $this->bus       = new IntelligenceBusService();
        $this->aiService = new IntelligenceAnalysisService();
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $this->log("===== Intelligence Worker Started =====");

        // --- Stage 1: MITRE ATT&CK Mapping ---
        $this->log("Stage 1/4: Running MITRE ATT&CK mapping...");
        $mitreResult = $this->runMitreMapping();

        // --- Stage 2: Threat Correlation ---
        $this->log("Stage 2/4: Running threat correlation engine...");
        $correlationResult = $this->runCorrelation();

        // --- Stage 3: Risk Scoring ---
        $this->log("Stage 3/4: Calculating Security Posture Score...");
        $scoreResult = $this->runRiskScoring();

        // --- Stage 4: Intelligence Bus + AI Assessment (v0.7.0) ---
        $this->log("Stage 4/4: Publishing to Intelligence Bus and refreshing AI assessment...");
        $this->publishBusEvents($mitreResult, $correlationResult, $scoreResult);
        $this->refreshAiAssessment();

        // --- Summary ---
        $duration = round(microtime(true) - $startTime, 2);
        $this->log("===== Intelligence Worker Completed in {$duration}s =====");
        $this->log("Summary:");
        $this->log("  MITRE: {$mitreResult['mapped']} techniques mapped, {$mitreResult['skipped']} skipped");
        $this->log("  Correlations: {$correlationResult['found']} found, {$correlationResult['inserted']} new");
        $this->log("  Security Score: {$scoreResult['score']}/100 ({$scoreResult['rating']})");
    }

    private function publishBusEvents(array $mitreResult, array $correlationResult, array $scoreResult): void
    {
        try {
            if ($mitreResult['mapped'] > 0) {
                $this->bus->publish(IntelligenceBusService::EVENT_MITRE_MAPPED, $mitreResult, 'IntelligenceWorker');
            }
            if ($correlationResult['inserted'] > 0) {
                $this->bus->publish(IntelligenceBusService::EVENT_CORRELATION_GENERATED, $correlationResult, 'IntelligenceWorker');
            }
            $this->bus->publish(IntelligenceBusService::EVENT_RISK_SCORE_UPDATED, $scoreResult, 'IntelligenceWorker');
            $this->bus->publish(IntelligenceBusService::EVENT_INTELLIGENCE_WORKER_COMPLETE, [
                'mitre'       => $mitreResult,
                'correlation' => $correlationResult,
                'score'       => $scoreResult,
            ], 'IntelligenceWorker');
            $this->log("  Bus events published.");
        } catch (\Throwable $e) {
            $this->log("  Bus publish failed: " . $e->getMessage(), 'error');
        }
    }

    private function refreshAiAssessment(): void
    {
        try {
            if (!$this->aiService->isEnabled()) {
                $this->log("  AI Intelligence Layer not configured — skipping assessment refresh.");
                return;
            }
            $result = $this->aiService->getNationalAssessment(forceRefresh: true);
            if ($result) {
                $this->log("  AI National Assessment refreshed: {$result['threat_level']} (confidence {$result['confidence']}%)");
            } else {
                $this->log("  AI assessment refresh returned no result (see ai.log for details).", 'error');
            }
        } catch (\Throwable $e) {
            $this->log("  AI assessment refresh failed: " . $e->getMessage(), 'error');
        }
    }

    // =========================================================
    // Stage runners
    // =========================================================

    private function runMitreMapping(): array
    {
        try {
            $result = $this->mitreService->mapThreats();
            $this->log("  MITRE mapping complete: {$result['mapped']} mapped, {$result['skipped']} had no match");
            return $result;
        } catch (\Throwable $e) {
            $this->log("  MITRE mapping failed: " . $e->getMessage(), 'error');
            return ['mapped' => 0, 'skipped' => 0];
        }
    }

    private function runCorrelation(): array
    {
        try {
            $correlations = $this->correlationService->correlate();
            $found        = count($correlations);
            $this->log("  Correlation engine produced {$found} correlation(s)");

            $persistResult = $this->correlationService->persist($correlations);
            $this->log("  Persisted: {$persistResult['inserted']} new, {$persistResult['skipped']} already recorded today");

            return [
                'found'    => $found,
                'inserted' => $persistResult['inserted'],
                'skipped'  => $persistResult['skipped'],
            ];
        } catch (\Throwable $e) {
            $this->log("  Correlation failed: " . $e->getMessage(), 'error');
            return ['found' => 0, 'inserted' => 0, 'skipped' => 0];
        }
    }

    private function runRiskScoring(): array
    {
        try {
            $result = $this->scoringService->calculate();
            $this->scoringService->persist($result);
            $this->log("  Security Posture Score: {$result['score']}/100 — {$result['rating']}");

            foreach ($result['breakdown'] as $item) {
                $this->log("    [{$item['deduction']}] {$item['factor']}: {$item['value']}");
            }

            return $result;
        } catch (\Throwable $e) {
            $this->log("  Risk scoring failed: " . $e->getMessage(), 'error');
            return ['score' => 0, 'rating' => 'Unknown', 'breakdown' => []];
        }
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        $this->logger->log(strtoupper($level), "[IntelligenceWorker] {$message}");
    }
}

// CLI execution guard — this worker must never be invoked via HTTP
if (php_sapi_name() === 'cli') {
    $worker = new IntelligenceWorker();
    $worker->run();
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
