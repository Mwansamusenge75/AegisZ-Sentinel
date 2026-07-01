<?php
/**
 * AegisZ Sentinel - Threat API (Placeholder)
 * Future REST endpoint for threat management.
 */

namespace App\Api;

use App\Domain\Threat\ThreatService;

class ThreatApiController extends ApiBaseController
{
    private ThreatService $service;

    public function __construct()
    {
        $this->service = new ThreatService();
    }

    public function index(): void
    {
        $threats = $this->service->findAll();
        $this->success([
            'threats' => array_map(fn($t) => $t->toArray(), $threats),
            'counts' => $this->service->getCounts(),
        ], 'Threat API placeholder - v0.2.0');
    }

    public function show(int $id): void
    {
        $threat = $this->service->findById($id);
        if (!$threat) {
            $this->error('Threat not found.', 404);
            return;
        }
        $this->success(['threat' => $threat->toArray()]);
    }
}
