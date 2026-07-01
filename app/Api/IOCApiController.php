<?php
/**
 * AegisZ Sentinel - IOC API (Placeholder)
 * Future REST endpoint for IOC management.
 */

namespace App\Api;

use App\Domain\IOC\IOCService;

class IOCApiController extends ApiBaseController
{
    private IOCService $service;

    public function __construct()
    {
        $this->service = new IOCService();
    }

    public function index(): void
    {
        $iocs = $this->service->findAll();
        $this->success([
            'iocs' => array_map(fn($i) => $i->toArray(), $iocs),
            'counts' => $this->service->getCounts(),
        ], 'IOC API placeholder - v0.2.0');
    }

    public function show(int $id): void
    {
        $ioc = $this->service->findById($id);
        if (!$ioc) {
            $this->error('IOC not found.', 404);
            return;
        }
        $this->success(['ioc' => $ioc->toArray()]);
    }
}
