<?php
/**
 * AegisZ Sentinel - Feed Service
 * Orchestrates the ingestion flow for all external data sources.
 * Shared logic for fetch → validate → normalize → deduplicate → store → log.
 */

namespace App\Services\Feeds;

use App\Core\Logger;

class FeedService
{
    protected Logger $logger;
    protected string $workerName;
    protected int $timeout;
    protected IngestionStatusService $statusService;

    public function __construct(string $workerName, int $timeout = 30)
    {
        $this->logger = new Logger();
        $this->workerName = $workerName;
        $this->timeout = $timeout;
        $this->statusService = new IngestionStatusService();
    }

    /**
     * Fetch data from a URL with retry logic.
     */
    public function fetch(string $url): ?array
    {
        $this->log("Starting fetch: {$url}");

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'user_agent' => 'AegisZ-Sentinel/0.3.0 (Cyber-Intel-Ingestion)',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $this->log("Fetch failed (attempt 1): {$url}", 'warning');
            // Retry once
            sleep(2);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $this->log("Fetch failed permanently: {$url}", 'error');
                $this->statusService->updateStatus($this->workerName, 'failed', 0, 0, 0, 0, 'API fetch failed after retry');
                return null;
            }
        }

        $data = json_decode($response, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Invalid JSON response from: {$url}", 'error');
            $this->statusService->updateStatus($this->workerName, 'failed', 0, 0, 0, 0, 'Invalid JSON response');
            return null;
        }

        $this->log("Fetch successful: {$url}");
        return $data;
    }

    /**
     * Log with worker name prefix.
     */
    public function log(string $message, string $level = 'info', array $context = []): void
    {
        $prefixed = "[{$this->workerName}] {$message}";
        $this->logger->log($level, $prefixed, $context);
    }

    /**
     * Record final worker status.
     */
    public function recordStatus(string $status, int $fetched = 0, int $inserted = 0, int $updated = 0, int $skipped = 0, ?string $error = null): void
    {
        $this->statusService->updateStatus($this->workerName, $status, $fetched, $inserted, $updated, $skipped, $error);
    }

    /**
     * Validate that required fields exist in normalized data.
     */
    public function validateRequired(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sanitize string input to prevent injection.
     */
    public function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Map feed severity to standard severity.
     */
    public function mapSeverity(string $rawSeverity): string
    {
        $raw = strtolower(trim($rawSeverity));
        if (in_array($raw, ['critical', 'severe'])) {
            return 'critical';
        }
        if (in_array($raw, ['high', 'important'])) {
            return 'high';
        }
        if (in_array($raw, ['medium', 'moderate', 'warning'])) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Estimate confidence score based on source reliability.
     */
    public function estimateConfidence(string $source): int
    {
        $scores = [
            'urlhaus' => 75,
            'otx' => 70,
            'nvd' => 90,
            'cisa' => 95,
        ];
        return $scores[$source] ?? 50;
    }
}
