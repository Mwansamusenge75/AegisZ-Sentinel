<?php
/**
 * AegisZ Sentinel - IOC Ingestion Service
 * Validates and stores IOC data from external feeds.
 * No AI. No correlation. Just transform and persist.
 */

namespace App\Services\Feeds;

use App\Domain\IOC\IOCService;
use App\Domain\IOC\IOCRepository;

class IOCIngestionService
{
    private IOCService $iocService;
    private IOCRepository $iocRepository;
    private FeedService $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->iocService = new IOCService();
        $this->iocRepository = new IOCRepository();
        $this->feedService = $feedService;
    }

    /**
     * Ingest a single IOC. Check duplicates before insert.
     * @return array ['inserted' => bool, 'updated' => bool, 'skipped' => bool]
     */
    public function ingest(array $data): array
    {
        // Validate required fields
        if (!$this->feedService->validateRequired($data, ['type', 'value'])) {
            $this->feedService->log("IOC validation failed: missing required fields", 'warning');
            return ['inserted' => false, 'updated' => false, 'skipped' => true];
        }

        // Sanitize
        $data['value'] = $this->feedService->sanitize($data['value']);
        $data['source'] = isset($data['source']) ? $this->feedService->sanitize($data['source']) : 'unknown';

        // Validate IOC type
        if (!in_array($data['type'], ['ip', 'domain', 'url', 'hash'])) {
            $this->feedService->log("Invalid IOC type: {$data['type']}", 'warning');
            return ['inserted' => false, 'updated' => false, 'skipped' => true];
        }

        // Check for duplicate by value
        $existing = $this->iocRepository->findByValue($data['value']);
        if ($existing) {
            // Update last_seen only
            $this->iocService->update($existing->id, [
                'last_seen' => date('Y-m-d H:i:s'),
            ]);
            $this->feedService->log("Duplicate IOC skipped, updated last_seen: {$data['value']}");
            return ['inserted' => false, 'updated' => true, 'skipped' => false];
        }

        // Set defaults
        if (!isset($data['confidence_score'])) {
            $data['confidence_score'] = $this->feedService->estimateConfidence($data['source']);
        }
        if (!isset($data['first_seen'])) {
            $data['first_seen'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['last_seen'])) {
            $data['last_seen'] = date('Y-m-d H:i:s');
        }

        // Store
        $result = $this->iocService->create($data);
        if ($result['success']) {
            $this->feedService->log("IOC inserted: {$data['value']} (type: {$data['type']})");
            return ['inserted' => true, 'updated' => false, 'skipped' => false];
        }

        $this->feedService->log("IOC insert failed: {$data['value']}", 'error', $result['errors'] ?? []);
        return ['inserted' => false, 'updated' => false, 'skipped' => true];
    }
}
