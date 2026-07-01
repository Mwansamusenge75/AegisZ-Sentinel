<?php
/**
 * AegisZ Sentinel - Map Service (v0.7.0)
 * Business logic layer for the National Cyber Situational Awareness Map.
 * Assembles map-ready data structures from domain repositories.
 * No SQL here — delegates to repositories. No HTTP — delegates to controllers.
 */

namespace App\Services\Map;

use App\Domain\Asset\AssetRepository;
use App\Repositories\CorrelationRepository;
use App\Domain\Alert\AlertRepository;
use App\Domain\Incident\IncidentRepository;
use App\Domain\Threat\ThreatRepository;
use App\Domain\IOC\IOCRepository;
use App\Services\Scoring\RiskScoringService;
use App\Core\Database;

class MapService
{
    private AssetRepository       $assetRepo;
    private CorrelationRepository $corrRepo;
    private AlertRepository       $alertRepo;
    private IncidentRepository    $incidentRepo;
    private ThreatRepository      $threatRepo;
    private IOCRepository         $iocRepo;
    private RiskScoringService    $scoringService;

    private const SEVERITY_COLOR = [
        'critical' => '#ef4444',
        'high'     => '#f97316',
        'medium'   => '#eab308',
        'low'      => '#3b82f6',
    ];

    public function __construct()
    {
        $this->assetRepo     = new AssetRepository();
        $this->corrRepo      = new CorrelationRepository();
        $this->alertRepo     = new AlertRepository();
        $this->incidentRepo  = new IncidentRepository();
        $this->threatRepo    = new ThreatRepository();
        $this->iocRepo       = new IOCRepository();
        $this->scoringService = new RiskScoringService();
    }

    /**
     * GET /api/map/assets — all geo-located assets as map markers.
     */
    public function getAssetMarkers(array $filters = []): array
    {
        $assets = $this->assetRepo->findAllWithLocation($filters);
        return array_map(function ($asset) {
            return [
                'id'            => $asset->id,
                'name'          => $asset->name,
                'lat'           => $asset->latitude,
                'lng'           => $asset->longitude,
                'criticality'   => $asset->criticality,
                'status'        => $asset->status,
                'color'         => self::SEVERITY_COLOR[$asset->criticality] ?? '#6b7280',
                'operational'   => $asset->status === 'active',
                'department'    => $asset->department,
                'province'      => $asset->province,
                'district'      => $asset->district,
                'location_name' => $asset->locationName,
            ];
        }, $assets);
    }

    /**
     * Full detail payload for a single asset marker popup, including
     * linked intelligence — used by the map's asset selection panel.
     */
    public function getAssetDetail(int $assetId): ?array
    {
        $asset = $this->assetRepo->findById($assetId);
        if (!$asset || !$asset->hasGeoLocation()) {
            return null;
        }

        $latestScore = $this->scoringService->getLatest();

        return [
            'id'              => $asset->id,
            'name'            => $asset->name,
            'department'      => $asset->department,
            'criticality'     => $asset->criticality,
            'status'          => $asset->status,
            'risk_score'      => $latestScore['score'] ?? null,
            'linked_threats'  => $this->assetRepo->getLinkedThreats($assetId, 5),
            'open_alerts'     => array_filter($this->assetRepo->getLinkedAlerts($assetId, 10), fn($a) => !in_array($a['status'], ['resolved','closed'])),
            'open_incidents'  => array_filter($this->assetRepo->getLinkedIncidents($assetId, 10), fn($i) => !in_array($i['status'], ['resolved','closed'])),
            'latest_ioc'      => $this->getLatestLinkedIOC($assetId),
            'last_seen'       => $asset->updatedAt,
            'detail_url'      => '/assets/detail?id=' . $asset->id,
        ];
    }

    private function getLatestLinkedIOC(int $assetId): ?array
    {
        $correlations = $this->assetRepo->getLinkedCorrelations($assetId, 1);
        if (empty($correlations) || empty($correlations[0]['ioc_id'])) {
            return null;
        }
        $ioc = $this->iocRepo->findById((int) $correlations[0]['ioc_id']);
        return $ioc ? ['value' => $ioc->value, 'type' => $ioc->type, 'confidence' => $ioc->confidenceScore] : null;
    }

    /**
     * GET /api/map/incidents — incidents with linked asset geo-location.
     * Only incidents whose linked asset has a known location can be plotted.
     */
    public function getIncidentMarkers(array $filters = []): array
    {
        $db = Database::getInstance();
        $sql = "SELECT i.*, a.latitude, a.longitude, a.name AS asset_name
                FROM incidents i
                INNER JOIN assets a ON i.linked_asset_id = a.id
                WHERE a.latitude IS NOT NULL AND a.longitude IS NOT NULL";
        $params = [];
        if (!empty($filters['severity'])) {
            $sql .= " AND i.severity = :severity";
            $params['severity'] = $filters['severity'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $filters['status'];
        }
        $stmt = $db->prepare($sql . " ORDER BY i.created_at DESC LIMIT 200");
        $stmt->execute($params);

        return array_map(function ($row) {
            $closed = in_array($row['status'], ['resolved', 'closed']);
            return [
                'id'        => (int) $row['id'],
                'title'     => $row['title'],
                'lat'       => (float) $row['latitude'],
                'lng'       => (float) $row['longitude'],
                'severity'  => $row['severity'],
                'status'    => $row['status'],
                'color'     => self::SEVERITY_COLOR[$row['severity']] ?? '#6b7280',
                'pulsing'   => $row['severity'] === 'critical' && !$closed,
                'faded'     => $closed,
                'asset_name'=> $row['asset_name'],
                'created_at'=> $row['created_at'],
            ];
        }, $stmt->fetchAll());
    }

    /**
     * GET /api/map/alerts — active alerts with linked asset geo-location.
     */
    public function getAlertMarkers(array $filters = []): array
    {
        $db = Database::getInstance();
        $sql = "SELECT al.*, a.latitude, a.longitude, a.name AS asset_name, u.username AS assigned_username
                FROM alerts al
                INNER JOIN assets a ON al.linked_asset_id = a.id
                LEFT JOIN users u ON al.assigned_to = u.id
                WHERE a.latitude IS NOT NULL AND a.longitude IS NOT NULL
                  AND al.status NOT IN ('resolved','closed')";
        $params = [];
        if (!empty($filters['severity'])) {
            $sql .= " AND al.severity = :severity";
            $params['severity'] = $filters['severity'];
        }
        $stmt = $db->prepare($sql . " ORDER BY al.created_at DESC LIMIT 200");
        $stmt->execute($params);

        return array_map(function ($row) {
            return [
                'id'         => (int) $row['id'],
                'title'      => $row['title'],
                'lat'        => (float) $row['latitude'],
                'lng'        => (float) $row['longitude'],
                'severity'   => $row['severity'],
                'status'     => $row['status'],
                'color'      => self::SEVERITY_COLOR[$row['severity']] ?? '#6b7280',
                'source'     => $row['source'] ?? null,
                'assigned'   => $row['assigned_username'],
                'created_at' => $row['created_at'],
                'asset_name' => $row['asset_name'],
            ];
        }, $stmt->fetchAll());
    }

    /**
     * GET /api/map/threats — threats with known origin (nullable; only
     * populated once a future geolocation enrichment exists). We never
     * fabricate origin coordinates.
     */
    public function getThreatOrigins(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, title, severity, source_feed, origin_lat, origin_lng, origin_country, created_at
             FROM threats
             WHERE origin_lat IS NOT NULL AND origin_lng IS NOT NULL
             ORDER BY created_at DESC LIMIT 100"
        );
        return $stmt->fetchAll();
    }

    /**
     * GET /api/map/heatmap — point intensity data from alerts, incidents,
     * high-confidence IOCs (via correlated assets), and correlations.
     * Returns [[lat, lng, intensity], ...] — the Leaflet.heat format.
     */
    public function getHeatmapPoints(): array
    {
        $db = Database::getInstance();
        $points = [];

        // Alerts at asset locations
        $stmt = $db->query(
            "SELECT a.latitude, a.longitude, COUNT(*) as cnt
             FROM alerts al
             INNER JOIN assets a ON al.linked_asset_id = a.id
             WHERE a.latitude IS NOT NULL AND al.status NOT IN ('resolved','closed')
             GROUP BY a.id"
        );
        foreach ($stmt->fetchAll() as $row) {
            $points[] = [(float) $row['latitude'], (float) $row['longitude'], min(1.0, 0.3 + $row['cnt'] * 0.15)];
        }

        // Incidents at asset locations (heavier weight)
        $stmt = $db->query(
            "SELECT a.latitude, a.longitude, COUNT(*) as cnt
             FROM incidents i
             INNER JOIN assets a ON i.linked_asset_id = a.id
             WHERE a.latitude IS NOT NULL AND i.status NOT IN ('resolved','closed')
             GROUP BY a.id"
        );
        foreach ($stmt->fetchAll() as $row) {
            $points[] = [(float) $row['latitude'], (float) $row['longitude'], min(1.0, 0.5 + $row['cnt'] * 0.2)];
        }

        // Correlations at asset locations
        $stmt = $db->query(
            "SELECT a.latitude, a.longitude, COUNT(*) as cnt
             FROM correlations c
             INNER JOIN assets a ON c.asset_id = a.id
             WHERE a.latitude IS NOT NULL
             GROUP BY a.id"
        );
        foreach ($stmt->fetchAll() as $row) {
            $points[] = [(float) $row['latitude'], (float) $row['longitude'], min(1.0, 0.2 + $row['cnt'] * 0.1)];
        }

        return $points;
    }

    /**
     * GET /api/map/province/{name} — province intelligence panel data.
     */
    public function getProvinceIntelligence(string $province): array
    {
        $db = Database::getInstance();

        $assetStmt = $db->prepare("SELECT * FROM assets WHERE province = :p");
        $assetStmt->execute(['p' => $province]);
        $assets = $assetStmt->fetchAll();
        $assetIds = array_column($assets, 'id');

        $criticalCount = count(array_filter($assets, fn($a) => $a['criticality'] === 'critical'));

        $openAlerts = 0;
        $openIncidents = 0;
        if (!empty($assetIds)) {
            $placeholders = implode(',', array_fill(0, count($assetIds), '?'));

            $alertStmt = $db->prepare(
                "SELECT COUNT(*) FROM alerts WHERE linked_asset_id IN ({$placeholders}) AND status NOT IN ('resolved','closed')"
            );
            $alertStmt->execute($assetIds);
            $openAlerts = (int) $alertStmt->fetchColumn();

            $incidentStmt = $db->prepare(
                "SELECT COUNT(*) FROM incidents WHERE linked_asset_id IN ({$placeholders}) AND status NOT IN ('resolved','closed')"
            );
            $incidentStmt->execute($assetIds);
            $openIncidents = (int) $incidentStmt->fetchColumn();
        }

        // Simple province risk score: weighted by critical assets + open incidents/alerts
        $provinceRisk = max(0, 100 - ($criticalCount * 5) - ($openIncidents * 8) - ($openAlerts * 3));

        return [
            'province'         => $province,
            'asset_count'      => count($assets),
            'critical_assets'  => $criticalCount,
            'open_alerts'      => $openAlerts,
            'open_incidents'   => $openIncidents,
            'province_risk'    => $provinceRisk,
            'last_update'      => date('c'),
        ];
    }

    /**
     * National Overview Panel data.
     */
    public function getNationalOverview(): array
    {
        $db = Database::getInstance();
        $latestScore = $this->scoringService->getLatest();

        $provinceStmt = $db->query(
            "SELECT province, COUNT(*) as critical_count
             FROM assets WHERE criticality = 'critical' AND province IS NOT NULL
             GROUP BY province ORDER BY critical_count DESC LIMIT 1"
        );
        $highestRiskProvince = $provinceStmt->fetch();

        $alertCount = (int) $db->query(
            "SELECT COUNT(*) FROM alerts WHERE status NOT IN ('resolved','closed')"
        )->fetchColumn();

        $incidentCount = (int) $db->query(
            "SELECT COUNT(*) FROM incidents WHERE status NOT IN ('resolved','closed')"
        )->fetchColumn();

        $criticalAssets = (int) $db->query(
            "SELECT COUNT(*) FROM assets WHERE criticality = 'critical'"
        )->fetchColumn();

        $onlineAssets = (int) $db->query(
            "SELECT COUNT(*) FROM assets WHERE status = 'active'"
        )->fetchColumn();

        $offlineAssets = (int) $db->query(
            "SELECT COUNT(*) FROM assets WHERE status != 'active'"
        )->fetchColumn();

        return [
            'security_posture_score' => $latestScore['score']  ?? null,
            'security_posture_rating'=> $latestScore['rating'] ?? null,
            'highest_risk_province'  => $highestRiskProvince['province'] ?? null,
            'total_active_alerts'    => $alertCount,
            'total_open_incidents'   => $incidentCount,
            'critical_assets'        => $criticalAssets,
            'assets_online'          => $onlineAssets,
            'assets_offline'         => $offlineAssets,
            'last_update'            => $latestScore['created_at'] ?? null,
        ];
    }
}
