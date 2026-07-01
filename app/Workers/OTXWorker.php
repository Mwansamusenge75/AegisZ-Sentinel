<?php
/**
 * AegisZ Sentinel - OTX Worker
 * CLI-only script. Fetches IOC data from AlienVault OTX pulses.
 * Run via: php app/Workers/OTXWorker.php
 */

require __DIR__ . '/../../cli_bootstrap.php';

use App\Services\Feeds\FeedService;
use App\Services\Feeds\IOCIngestionService;
use App\Services\Feeds\ThreatIngestionService;

class OTXWorker
{
    private FeedService $feedService;
    private IOCIngestionService $iocIngestion;
    private ThreatIngestionService $threatIngestion;
    private string $apiUrl = 'https://otx.alienvault.com/api/v1/pulses/subscribed';

    public function __construct()
    {
        $this->feedService = new FeedService('OTXWorker', 45);
        $this->iocIngestion = new IOCIngestionService($this->feedService);
        $this->threatIngestion = new ThreatIngestionService($this->feedService);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $this->feedService->log("Worker started");
        $this->feedService->recordStatus('running');

        $data = $this->feedService->fetch($this->apiUrl);
        if (!$data || !isset($data['results'])) {
            $this->feedService->log("No data received from OTX", 'error');
            $this->feedService->recordStatus('failed', 0, 0, 0, 0, 'No data received');
            return;
        }

        $pulses = $data['results'];
        $iocInserted = 0;
        $iocUpdated = 0;
        $iocSkipped = 0;
        $threatInserted = 0;
        $threatSkipped = 0;
        $totalPulses = count($pulses);

        $this->feedService->log("Fetched {$totalPulses} pulses from OTX");

        foreach ($pulses as $pulse) {
            // Ingest threat (pulse itself)
            $threatData = $this->normalizeThreat($pulse);
            $tResult = $this->threatIngestion->ingest($threatData);
            if ($tResult['inserted']) $threatInserted++;
            if ($tResult['skipped']) $threatSkipped++;

            // Ingest IOCs from pulse
            if (isset($pulse['indicators']) && is_array($pulse['indicators'])) {
                foreach ($pulse['indicators'] as $indicator) {
                    $iocData = $this->normalizeIOC($indicator);
                    $result = $this->iocIngestion->ingest($iocData);
                    if ($result['inserted']) $iocInserted++;
                    if ($result['updated']) $iocUpdated++;
                    if ($result['skipped']) $iocSkipped++;
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->feedService->log(
            "Worker completed in {$duration}s | Threats: {$threatInserted} inserted, {$threatSkipped} skipped | IOCs: {$iocInserted} inserted, {$iocUpdated} updated, {$iocSkipped} skipped"
        );
        $this->feedService->recordStatus('success', $totalPulses, $threatInserted + $iocInserted, $iocUpdated, $threatSkipped + $iocSkipped);
    }

    private function normalizeThreat(array $pulse): array
    {
        return [
            'title' => $pulse['name'] ?? 'OTX Pulse',
            'description' => $pulse['description'] ?? 'No description available',
            'source_feed' => 'otx',
            'severity' => $pulse['TLP'] ?? 'medium',
            'raw_data' => json_encode($pulse),
        ];
    }

    private function normalizeIOC(array $indicator): array
    {
        $typeMap = [
            'IPv4' => 'ip',
            'domain' => 'domain',
            'URL' => 'url',
            'FileHash-SHA256' => 'hash',
            'FileHash-MD5' => 'hash',
            'FileHash-SHA1' => 'hash',
        ];

        $type = $typeMap[$indicator['type'] ?? ''] ?? 'url';

        return [
            'type' => $type,
            'value' => $indicator['indicator'] ?? '',
            'source' => 'otx',
            'confidence_score' => 70,
            'first_seen' => $indicator['created'] ?? date('Y-m-d H:i:s'),
            'last_seen' => $indicator['modified'] ?? date('Y-m-d H:i:s'),
            'raw_data' => json_encode($indicator),
        ];
    }
}

// CLI execution only
if (php_sapi_name() === 'cli') {
    $worker = new OTXWorker();
    $worker->run();
} else {
    echo "This script must be run from the command line.
";
    exit(1);
}
