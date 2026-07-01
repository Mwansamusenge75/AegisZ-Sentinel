<?php
/**
 * AegisZ Sentinel - Asset API (Placeholder)
 * Future REST endpoint for asset management.
 */

namespace App\Api;

use App\Domain\Asset\AssetService;

class AssetApiController extends ApiBaseController
{
    private AssetService $service;

    public function __construct()
    {
        $this->service = new AssetService();
    }

    public function index(): void
    {
        $assets = $this->service->findAll();
        $this->success([
            'assets' => array_map(fn($a) => $a->toArray(), $assets),
            'counts' => $this->service->getCounts(),
        ], 'Asset API placeholder - v0.2.0');
    }

    public function show(int $id): void
    {
        $asset = $this->service->findById($id);
        if (!$asset) {
            $this->error('Asset not found.', 404);
            return;
        }
        $this->success(['asset' => $asset->toArray()]);
    }
}
