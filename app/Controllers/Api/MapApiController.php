<?php
/**
 * AegisZ Sentinel - Map API Controller (v0.7.0)
 * JSON-only endpoints powering the Operational Map.
 * HTTP only. No SQL. No business logic — delegates entirely to MapService.
 */

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Security;
use App\Services\Map\MapService;

class MapApiController extends BaseController
{
    private MapService $mapService;

    public function __construct()
    {
        parent::__construct();
        $this->mapService = new MapService();
    }

    /** GET /api/map/assets */
    public function assets(): void
    {
        $filters = [
            'criticality' => Security::sanitize($_GET['criticality'] ?? ''),
            'asset_type'  => Security::sanitize($_GET['asset_type'] ?? ''),
            'status'      => Security::sanitize($_GET['status'] ?? ''),
            'province'    => Security::sanitize($_GET['province'] ?? ''),
        ];
        $this->json(['success' => true, 'data' => $this->mapService->getAssetMarkers(array_filter($filters))]);
    }

    /** GET /api/map/assets/detail?id=N — single asset popup payload */
    public function assetDetail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $detail = $this->mapService->getAssetDetail($id);
        if (!$detail) {
            $this->json(['success' => false, 'error' => 'Asset not found or has no map location.'], 404);
            return;
        }
        $this->json(['success' => true, 'data' => $detail]);
    }

    /** GET /api/map/incidents */
    public function incidents(): void
    {
        $filters = [
            'severity' => Security::sanitize($_GET['severity'] ?? ''),
            'status'   => Security::sanitize($_GET['status'] ?? ''),
        ];
        $this->json(['success' => true, 'data' => $this->mapService->getIncidentMarkers(array_filter($filters))]);
    }

    /** GET /api/map/alerts */
    public function alerts(): void
    {
        $filters = ['severity' => Security::sanitize($_GET['severity'] ?? '')];
        $this->json(['success' => true, 'data' => $this->mapService->getAlertMarkers(array_filter($filters))]);
    }

    /** GET /api/map/threats — origin points only (nullable, never fabricated) */
    public function threats(): void
    {
        $this->json(['success' => true, 'data' => $this->mapService->getThreatOrigins()]);
    }

    /** GET /api/map/heatmap */
    public function heatmap(): void
    {
        $this->json(['success' => true, 'data' => $this->mapService->getHeatmapPoints()]);
    }

    /** GET /api/map/province/{name} — passed as ?name= */
    public function province(): void
    {
        $name = Security::sanitize($_GET['name'] ?? '');
        if (!$name) {
            $this->json(['success' => false, 'error' => 'Province name is required.'], 400);
            return;
        }
        $this->json(['success' => true, 'data' => $this->mapService->getProvinceIntelligence($name)]);
    }

    /** GET /api/map/overview — National Overview Panel */
    public function overview(): void
    {
        $this->json(['success' => true, 'data' => $this->mapService->getNationalOverview()]);
    }
}
