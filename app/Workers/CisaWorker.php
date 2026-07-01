<?php
/**
 * AegisZ Sentinel - CISA KEV Worker
 * CLI-only script. Fetches Known Exploited Vulnerabilities from CISA.
 * Run via: php app/Workers/CisaWorker.php
 */

require __DIR__ . '/../../cli_bootstrap.php';

use App\Services\Feeds\FeedService;
use App\Services\Feeds\ThreatIngestionService;

class CisaWorker
{
    private FeedService $feedService;
    private ThreatIngestionService $threatIngestion;
    private string $apiUrl = 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json';

    public function __construct()
    {
        $this->feedService = new FeedService('CisaWorker', 60);
        $this->threatIngestion = new ThreatIngestionService($this->feedService);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $this->feedService->log("Worker started");
        $this->feedService->recordStatus('running');

        $data = $this->feedService->fetch($this->apiUrl);
        if (!$data || !isset($data['vulnerabilities'])) {
            $this->feedService->log("No data received from CISA", 'error');
            $this->feedService->recordStatus('failed', 0, 0, 0, 0, 'No data received');
            return;
        }

        $vulns = $data['vulnerabilities'];
        $inserted = 0;
        $skipped = 0;

        $this->feedService->log("Fetched " . count($vulns) . " KEV entries from CISA");

        foreach ($vulns as $entry) {
            $threatData = $this->normalize($entry);
            if (!$threatData) {
                $skipped++;
                continue;
            }

            $result = $this->threatIngestion->ingest($threatData);
            if ($result['inserted']) $inserted++;
            if ($result['skipped']) $skipped++;
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->feedService->log(
            "Worker completed in {$duration}s | Inserted: {$inserted} | Skipped: {$skipped}"
        );
        $this->feedService->recordStatus('success', count($vulns), $inserted, 0, $skipped);
    }

    private function normalize(array $entry): ?array
    {
        $cveId = $entry['cveID'] ?? '';
        if (empty($cveId)) {
            return null;
        }

        $desc = $entry['shortDescription'] ?? $entry['vulnerabilityName'] ?? 'No description available';
        $severity = 'high'; // CISA KEV = known exploited = high by default

        return [
            'title' => "CISA KEV: {$cveId}",
            'description' => $desc,
            'source_feed' => 'cisa',
            'severity' => $severity,
            'raw_data' => json_encode($entry),
        ];
    }
}

// CLI execution only
if (php_sapi_name() === 'cli') {
    $worker = new CisaWorker();
    $worker->run();
} else {
    echo "This script must be run from the command line.
";
    exit(1);
}
