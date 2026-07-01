<?php
/**
 * AegisZ Sentinel - Incident API (Placeholder)
 * Future REST endpoint for incident management.
 */

namespace App\Api;

use App\Domain\Incident\IncidentService;

class IncidentApiController extends ApiBaseController
{
    private IncidentService $service;

    public function __construct()
    {
        $this->service = new IncidentService();
    }

    public function index(): void
    {
        $incidents = $this->service->findAll();
        $this->success([
            'incidents' => array_map(fn($i) => $i->toArray(), $incidents),
            'counts' => $this->service->getCounts(),
        ], 'Incident API placeholder - v0.2.0');
    }

    public function show(int $id): void
    {
        $incident = $this->service->findById($id);
        if (!$incident) {
            $this->error('Incident not found.', 404);
            return;
        }
        $this->success(['incident' => $incident->toArray()]);
    }
}
