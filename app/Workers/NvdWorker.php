<?php
/**
 * AegisZ Sentinel - NVD Worker
 * CLI-only script. Fetches CVE data from NIST NVD API.
 * Run via: php app/Workers/NvdWorker.php
 */

require __DIR__ . '/../../cli_bootstrap.php';

use App\Services\Feeds\FeedService;
use App\Services\Feeds\ThreatIngestionService;

class NvdWorker
{
    private FeedService $feedService;
    private ThreatIngestionService $threatIngestion;
    private string $apiUrl = 'https://services.nvd.nist.gov/rest/json/cves/2.0?resultsPerPage=20';

    public function __construct()
    {
        $this->feedService = new FeedService('NvdWorker', 60);
        $this->threatIngestion = new ThreatIngestionService($this->feedService);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $this->feedService->log("Worker started");
        $this->feedService->recordStatus('running');

        $data = $this->feedService->fetch($this->apiUrl);
        if (!$data || !isset($data['vulnerabilities'])) {
            $this->feedService->log("No data received from NVD", 'error');
            $this->feedService->recordStatus('failed', 0, 0, 0, 0, 'No data received');
            return;
        }

        $vulns = $data['vulnerabilities'];
        $inserted = 0;
        $skipped = 0;

        $this->feedService->log("Fetched " . count($vulns) . " CVEs from NVD");

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
        $cve = $entry['cve'] ?? null;
        if (!$cve) {
            return null;
        }

        $id = $cve['id'] ?? 'UNKNOWN';
        $desc = '';
        if (isset($cve['descriptions']) && is_array($cve['descriptions'])) {
            foreach ($cve['descriptions'] as $d) {
                if (($d['lang'] ?? '') === 'en') {
                    $desc = $d['value'] ?? '';
                    break;
                }
            }
        }

        // Extract CVSS severity
        $severity = 'medium';
        if (isset($cve['metrics']['cvssMetricV31'][0]['cvssData']['baseSeverity'])) {
            $severity = $cve['metrics']['cvssMetricV31'][0]['cvssData']['baseSeverity'];
        } elseif (isset($cve['metrics']['cvssMetricV30'][0]['cvssData']['baseSeverity'])) {
            $severity = $cve['metrics']['cvssMetricV30'][0]['cvssData']['baseSeverity'];
        } elseif (isset($cve['metrics']['cvssMetricV2'][0]['baseSeverity'])) {
            $severity = $cve['metrics']['cvssMetricV2'][0]['baseSeverity'];
        }

        return [
            'title' => "CVE: {$id}",
            'description' => $desc ?: 'No description available',
            'source_feed' => 'nvd',
            'severity' => $severity,
            'raw_data' => json_encode($entry),
        ];
    }
}

// CLI execution only
if (php_sapi_name() === 'cli') {
    $worker = new NvdWorker();
    $worker->run();
} else {
    echo "This script must be run from the command line.
";
    exit(1);
}
