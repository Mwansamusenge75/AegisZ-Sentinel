<?php
/**
 * AegisZ Sentinel - URLHaus Worker
 * CLI-only script. Fetches malicious URL data from URLHaus API.
 * Run via: php app/Workers/UrlHausWorker.php
 */

require __DIR__ . '/../../cli_bootstrap.php';

use App\Services\Feeds\FeedService;
use App\Services\Feeds\IOCIngestionService;

class UrlHausWorker
{
    private FeedService $feedService;
    private IOCIngestionService $iocIngestion;
    private string $apiUrl = 'https://urlhaus-api.abuse.ch/v1/urls/recent/';

    public function __construct()
    {
        $this->feedService = new FeedService('UrlHausWorker', 30);
        $this->iocIngestion = new IOCIngestionService($this->feedService);
    }

    public function run(): void
    {
        $startTime = microtime(true);
        $this->feedService->log("Worker started");
        $this->feedService->recordStatus('running');

        $data = $this->feedService->fetch($this->apiUrl);
        if (!$data || !isset($data['urls'])) {
            $this->feedService->log("No data received from URLHaus", 'error');
            $this->feedService->recordStatus('failed', 0, 0, 0, 0, 'No data received');
            return;
        }

        $urls = $data['urls'];
        $total = count($urls);
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $this->feedService->log("Fetched {$total} URLs from URLHaus");

        foreach ($urls as $entry) {
            $iocData = $this->normalize($entry);
            $result = $this->iocIngestion->ingest($iocData);

            if ($result['inserted']) $inserted++;
            if ($result['updated']) $updated++;
            if ($result['skipped']) $skipped++;
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->feedService->log(
            "Worker completed in {$duration}s | Total: {$total} | Inserted: {$inserted} | Updated: {$updated} | Skipped: {$skipped}"
        );
        $this->feedService->recordStatus('success', $total, $inserted, $updated, $skipped);
    }

    private function normalize(array $entry): array
    {
        return [
            'type' => 'url',
            'value' => $entry['url'] ?? '',
            'source' => 'urlhaus',
            'confidence_score' => 75,
            'first_seen' => $entry['date_added'] ?? date('Y-m-d H:i:s'),
            'last_seen' => $entry['date_added'] ?? date('Y-m-d H:i:s'),
            'raw_data' => json_encode($entry),
        ];
    }
}

// CLI execution only
if (php_sapi_name() === 'cli') {
    $worker = new UrlHausWorker();
    $worker->run();
} else {
    echo "This script must be run from the command line.
";
    exit(1);
}
