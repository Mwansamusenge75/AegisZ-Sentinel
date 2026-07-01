<?php
/**
 * AegisZ Sentinel - Intelligence Bus Service (v0.7.0)
 * Centralized event bus connecting platform components without tight coupling.
 *
 * Pipeline per spec:
 *   Workers → Correlation → Risk Engine → MITRE Mapping → Map Events →
 *   News Intelligence → Intelligence Bus → OpenRouter → AI Assessment →
 *   Dashboard + Operational Map
 *
 * Implementation note: this is shared-hosting compatible — no message
 * broker, no persistent daemon. It's a database-backed event log table.
 * Producers call publish() at the point an event occurs (e.g. end of
 * Intelligence Worker run). Consumers call consume()/getUnprocessed() to
 * pick up events, typically also from a CLI worker context. This keeps the
 * "bus" model intact (decoupled publish/subscribe) within a cron-driven,
 * daemon-free PHP environment.
 */

namespace App\Services\IntelligenceBus;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class IntelligenceBusService
{
    private PDO    $db;
    private Logger $logger;

    public const EVENT_THREAT_INGESTED      = 'threat_ingested';
    public const EVENT_IOC_INGESTED         = 'ioc_ingested';
    public const EVENT_CORRELATION_GENERATED = 'correlation_generated';
    public const EVENT_RISK_SCORE_UPDATED   = 'risk_score_updated';
    public const EVENT_MITRE_MAPPED         = 'mitre_mapped';
    public const EVENT_MAP_REFRESH          = 'map_refresh';
    public const EVENT_INTELLIGENCE_WORKER_COMPLETE = 'intelligence_worker_complete';
    public const EVENT_NEWS_INGESTED        = 'news_ingested'; // reserved for future use

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Publish an event onto the bus.
     */
    public function publish(string $eventType, array $payload = [], ?string $sourceComponent = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO intelligence_bus_events (event_type, source_component, payload_json, processed)
             VALUES (:type, :source, :payload, 0)"
        );
        $stmt->execute([
            'type'    => $eventType,
            'source'  => $sourceComponent,
            'payload' => json_encode($payload),
        ]);
        $id = (int) $this->db->lastInsertId();
        $this->logger->info("[IntelligenceBus] Event published: {$eventType}", ['id' => $id, 'source' => $sourceComponent]);
        return $id;
    }

    /**
     * Fetch unprocessed events, optionally filtered by type.
     */
    public function getUnprocessed(?string $eventType = null, int $limit = 50): array
    {
        $sql    = "SELECT * FROM intelligence_bus_events WHERE processed = 0";
        $params = [];
        if ($eventType) {
            $sql .= " AND event_type = :type";
            $params['type'] = $eventType;
        }
        $sql .= " ORDER BY created_at ASC LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['payload'] = json_decode($row['payload_json'], true);
        }
        return $rows;
    }

    /**
     * Mark an event as processed/consumed.
     */
    public function markProcessed(int $eventId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE intelligence_bus_events SET processed = 1, processed_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $eventId]);
    }

    /**
     * Fetch recent events for the dashboard/map timeline component,
     * regardless of processed status.
     */
    public function getRecentEvents(int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM intelligence_bus_events ORDER BY created_at DESC LIMIT " . (int) $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['payload'] = json_decode($row['payload_json'], true);
        }
        return $rows;
    }
}
