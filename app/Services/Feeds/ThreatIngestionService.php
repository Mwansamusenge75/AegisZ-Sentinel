<?php
/**
 * AegisZ Sentinel - Threat Ingestion Service
 * Validates and stores threat data from external feeds.
 * No AI. No correlation. Just transform and persist.
 */

namespace App\Services\Feeds;

use App\Domain\Threat\ThreatService;
use App\Domain\Threat\ThreatRepository;

class ThreatIngestionService
{
    private ThreatService $threatService;
    private ThreatRepository $threatRepository;
    private FeedService $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->threatService = new ThreatService();
        $this->threatRepository = new ThreatRepository();
        $this->feedService = $feedService;
    }

    /**
     * Ingest a single threat. Check duplicates by title + source before insert.
     * @return array ['inserted' => bool, 'updated' => bool, 'skipped' => bool]
     */
    public function ingest(array $data): array
    {
        // Validate required fields
        if (!$this->feedService->validateRequired($data, ['title', 'source_feed'])) {
            $this->feedService->log("Threat validation failed: missing required fields", 'warning');
            return ['inserted' => false, 'updated' => false, 'skipped' => true];
        }

        // Sanitize
        $data['title'] = $this->feedService->sanitize($data['title']);
        $data['source_feed'] = $this->feedService->sanitize($data['source_feed']);

        // Map severity
        if (isset($data['severity'])) {
            $data['severity'] = $this->feedService->mapSeverity($data['severity']);
        } else {
            $data['severity'] = 'medium';
        }

        // Check for duplicate by title + source
        $existing = $this->findByTitleAndSource($data['title'], $data['source_feed']);
        if ($existing) {
            $this->feedService->log("Duplicate threat skipped: {$data['title']} ({$data['source_feed']})");
            return ['inserted' => false, 'updated' => false, 'skipped' => true];
        }

        // Store
        $result = $this->threatService->create($data);
        if ($result['success']) {
            $this->feedService->log("Threat inserted: {$data['title']} (severity: {$data['severity']})");
            return ['inserted' => true, 'updated' => false, 'skipped' => false];
        }

        $this->feedService->log("Threat insert failed: {$data['title']}", 'error', $result['errors'] ?? []);
        return ['inserted' => false, 'updated' => false, 'skipped' => true];
    }

    /**
     * Find existing threat by title + source_feed combination.
     */
    private function findByTitleAndSource(string $title, string $source): ?\App\Domain\Threat\ThreatEntity
    {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM threats WHERE title = :title AND source_feed = :source LIMIT 1");
        $stmt->execute(['title' => $title, 'source' => $source]);
        $data = $stmt->fetch();
        return $data ? \App\Domain\Threat\ThreatEntity::fromArray($data) : null;
    }
}
