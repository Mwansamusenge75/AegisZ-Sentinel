<?php
/**
 * AegisZ Sentinel - Alert API (Placeholder)
 * Future REST endpoint for alert management.
 */

namespace App\Api;

use App\Domain\Alert\AlertService;

class AlertApiController extends ApiBaseController
{
    private AlertService $service;

    public function __construct()
    {
        $this->service = new AlertService();
    }

    public function index(): void
    {
        $alerts = $this->service->findAll();
        $this->success([
            'alerts' => array_map(fn($a) => $a->toArray(), $alerts),
            'counts' => $this->service->getCounts(),
        ], 'Alert API placeholder - v0.2.0');
    }

    public function show(int $id): void
    {
        $alert = $this->service->findById($id);
        if (!$alert) {
            $this->error('Alert not found.', 404);
            return;
        }
        $this->success(['alert' => $alert->toArray()]);
    }
}
