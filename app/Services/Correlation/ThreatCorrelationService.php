<?php
/**
 * AegisZ Sentinel - Threat Correlation Service (v0.4.0)
 * Correlates IOCs → Threats → Assets → Alerts → Incidents.
 * Produces explainable correlation records. Does NOT auto-generate alerts.
 *
 * Architecture: Service → Business Logic only.
 * No HTTP. No SQL here — delegates to repositories.
 */

namespace App\Services\Correlation;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class ThreatCorrelationService
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Run full correlation pipeline.
     * Returns array of correlation records ready to persist.
     */
    public function correlate(): array
    {
        $this->logger->info('[ThreatCorrelation] Starting correlation run');

        $correlations = [];

        // --- 1. IOC × Asset correlation (IP/domain match against asset inventory) ---
        $iocAssetMatches = $this->correlateIOCsWithAssets();
        foreach ($iocAssetMatches as $match) {
            $correlations[] = $match;
        }

        // --- 2. High-confidence IOC × open Alert correlation ---
        $iocAlertMatches = $this->correlateIOCsWithAlerts();
        foreach ($iocAlertMatches as $match) {
            $correlations[] = $match;
        }

        // --- 3. Critical threat × open Incident correlation ---
        $threatIncidentMatches = $this->correlateThreatsWithIncidents();
        foreach ($threatIncidentMatches as $match) {
            $correlations[] = $match;
        }

        $this->logger->info('[ThreatCorrelation] Correlation run completed', [
            'total_correlations' => count($correlations),
        ]);

        return $correlations;
    }

    /**
     * Correlate IOC values against asset IP addresses and hostnames.
     */
    private function correlateIOCsWithAssets(): array
    {
        $results = [];

        // Fetch high-confidence IP and domain IOCs
        $stmt = $this->db->prepare(
            "SELECT i.id, i.type, i.value, i.source, i.confidence_score
             FROM iocs i
             WHERE i.type IN ('ip', 'domain')
               AND i.confidence_score >= 60
             ORDER BY i.confidence_score DESC
             LIMIT 200"
        );
        $stmt->execute();
        $iocs = $stmt->fetchAll();

        if (empty($iocs)) {
            return [];
        }

        foreach ($iocs as $ioc) {
            // Match IOC value against asset ip_address or hostname
            $assetStmt = $this->db->prepare(
                "SELECT id, name, ip_address, hostname, criticality, department
                 FROM assets
                 WHERE (ip_address = :val OR hostname = :val2)
                   AND status = 'active'
                 LIMIT 5"
            );
            $assetStmt->execute(['val' => $ioc['value'], 'val2' => $ioc['value']]);
            $assets = $assetStmt->fetchAll();

            foreach ($assets as $asset) {
                $severity  = $this->deriveSeverityFromCriticality($asset['criticality']);
                $confidence = (int) $ioc['confidence_score'];

                $explanation = sprintf(
                    "IOC '%s' (type: %s, source: %s, confidence: %d%%) matched asset '%s' (IP: %s, hostname: %s, criticality: %s).",
                    $ioc['value'],
                    $ioc['type'],
                    $ioc['source'] ?? 'unknown',
                    $confidence,
                    $asset['name'],
                    $asset['ip_address'] ?? 'N/A',
                    $asset['hostname'] ?? 'N/A',
                    $asset['criticality']
                );

                $results[] = [
                    'correlation_type' => 'ioc_asset',
                    'ioc_id'           => $ioc['id'],
                    'ioc_value'        => $ioc['value'],
                    'ioc_type'         => $ioc['type'],
                    'source_feed'      => $ioc['source'] ?? 'unknown',
                    'asset_id'         => $asset['id'],
                    'asset_name'       => $asset['name'],
                    'alert_id'         => null,
                    'incident_id'      => null,
                    'confidence'       => $confidence,
                    'severity'         => $severity,
                    'explanation'      => $explanation,
                ];
            }
        }

        return $results;
    }

    /**
     * Correlate high-confidence IOCs against open alerts (via linked_ioc_id).
     */
    private function correlateIOCsWithAlerts(): array
    {
        $results = [];

        $stmt = $this->db->prepare(
            "SELECT a.id AS alert_id, a.title AS alert_title, a.severity AS alert_severity,
                    i.id AS ioc_id, i.value AS ioc_value, i.type AS ioc_type,
                    i.source, i.confidence_score
             FROM alerts a
             INNER JOIN iocs i ON a.linked_ioc_id = i.id
             WHERE a.status = 'open'
               AND i.confidence_score >= 70
             ORDER BY i.confidence_score DESC
             LIMIT 100"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $explanation = sprintf(
                "Open alert '%s' (severity: %s) is linked to IOC '%s' (type: %s, source: %s, confidence: %d%%). High-confidence active indicator with unresolved alert requires investigation.",
                $row['alert_title'],
                $row['alert_severity'],
                $row['ioc_value'],
                $row['ioc_type'],
                $row['source'] ?? 'unknown',
                $row['confidence_score']
            );

            $results[] = [
                'correlation_type' => 'ioc_alert',
                'ioc_id'           => $row['ioc_id'],
                'ioc_value'        => $row['ioc_value'],
                'ioc_type'         => $row['ioc_type'],
                'source_feed'      => $row['source'] ?? 'unknown',
                'asset_id'         => null,
                'asset_name'       => null,
                'alert_id'         => $row['alert_id'],
                'incident_id'      => null,
                'confidence'       => (int) $row['confidence_score'],
                'severity'         => $row['alert_severity'],
                'explanation'      => $explanation,
            ];
        }

        return $results;
    }

    /**
     * Correlate critical threats against open/investigating incidents.
     */
    private function correlateThreatsWithIncidents(): array
    {
        $results = [];

        $stmt = $this->db->prepare(
            "SELECT t.id AS threat_id, t.title AS threat_title, t.severity AS threat_severity,
                    t.source_feed, t.mitre_technique,
                    inc.id AS incident_id, inc.title AS incident_title, inc.status AS incident_status
             FROM threats t
             CROSS JOIN incidents inc
             WHERE t.severity IN ('critical', 'high')
               AND inc.status IN ('open', 'investigating')
               AND t.severity = inc.severity
             ORDER BY t.created_at DESC
             LIMIT 50"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $mitreNote = $row['mitre_technique']
                ? " MITRE technique: {$row['mitre_technique']}."
                : '';

            $explanation = sprintf(
                "Critical/high-severity threat '%s' (source: %s%s) severity-matches open incident '%s' (status: %s). Shared severity level indicates potential operational impact alignment.",
                $row['threat_title'],
                $row['source_feed'] ?? 'unknown',
                $mitreNote,
                $row['incident_title'],
                $row['incident_status']
            );

            $results[] = [
                'correlation_type' => 'threat_incident',
                'ioc_id'           => null,
                'ioc_value'        => null,
                'ioc_type'         => null,
                'source_feed'      => $row['source_feed'] ?? 'unknown',
                'asset_id'         => null,
                'asset_name'       => null,
                'alert_id'         => null,
                'incident_id'      => $row['incident_id'],
                'confidence'       => 65, // structural match, not value-match, lower confidence
                'severity'         => $row['threat_severity'],
                'explanation'      => $explanation,
            ];
        }

        return $results;
    }

    /**
     * Map asset criticality to severity string.
     */
    private function deriveSeverityFromCriticality(string $criticality): string
    {
        $map = [
            'critical' => 'critical',
            'high'     => 'high',
            'medium'   => 'medium',
            'low'      => 'low',
        ];
        return $map[strtolower($criticality)] ?? 'medium';
    }

    /**
     * Persist correlations to the database (upsert by hash).
     * Returns ['inserted' => int, 'skipped' => int]
     */
    public function persist(array $correlations): array
    {
        $inserted = 0;
        $skipped  = 0;

        foreach ($correlations as $c) {
            // Deduplicate by a hash of the key fields
            $hash = md5(
                ($c['correlation_type'] ?? '') .
                ($c['ioc_id'] ?? '') .
                ($c['asset_id'] ?? '') .
                ($c['alert_id'] ?? '') .
                ($c['incident_id'] ?? '')
            );

            // Check if this correlation was already recorded today
            $checkStmt = $this->db->prepare(
                "SELECT id FROM correlations
                 WHERE correlation_hash = :hash
                   AND DATE(created_at) = CURDATE()
                 LIMIT 1"
            );
            $checkStmt->execute(['hash' => $hash]);
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO correlations
                 (correlation_type, ioc_id, ioc_value, ioc_type, source_feed,
                  asset_id, asset_name, alert_id, incident_id,
                  confidence, severity, explanation, correlation_hash)
                 VALUES
                 (:correlation_type, :ioc_id, :ioc_value, :ioc_type, :source_feed,
                  :asset_id, :asset_name, :alert_id, :incident_id,
                  :confidence, :severity, :explanation, :correlation_hash)"
            );

            $stmt->execute([
                'correlation_type' => $c['correlation_type'],
                'ioc_id'           => $c['ioc_id'],
                'ioc_value'        => $c['ioc_value'],
                'ioc_type'         => $c['ioc_type'],
                'source_feed'      => $c['source_feed'],
                'asset_id'         => $c['asset_id'],
                'asset_name'       => $c['asset_name'],
                'alert_id'         => $c['alert_id'],
                'incident_id'      => $c['incident_id'],
                'confidence'       => $c['confidence'],
                'severity'         => $c['severity'],
                'explanation'      => $c['explanation'],
                'correlation_hash' => $hash,
            ]);

            $inserted++;
        }

        $this->logger->info('[ThreatCorrelation] Persisted correlations', [
            'inserted' => $inserted,
            'skipped'  => $skipped,
        ]);

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    /**
     * Fetch recent correlations for dashboard display.
     */
    public function getRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM correlations ORDER BY created_at DESC LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count correlations by severity.
     */
    public function countBySeverity(): array
    {
        $stmt = $this->db->query(
            "SELECT severity, COUNT(*) AS count FROM correlations GROUP BY severity"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
