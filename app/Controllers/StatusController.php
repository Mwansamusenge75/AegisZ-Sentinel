<?php
/**
 * AegisZ Sentinel - Status Controller
 * System health and status page.
 */

namespace App\Controllers;

use App\Services\SystemService;

class StatusController extends BaseController
{
    private SystemService $systemService;

    public function __construct()
    {
        parent::__construct();
        $this->systemService = new SystemService();
    }

    public function index(): void
    {
        $this->logger->info('Status page visited', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

        $data = [
            'title'        => 'System Status | AegisZ Sentinel',
            'appName'      => 'AegisZ Sentinel',
            'health'       => $this->systemService->getHealthStatus(),
            'components'   => $this->systemService->getComponentStatus(),
            'timestamp'    => date('Y-m-d H:i:s T'),
        ];

        $this->render('status/index', $data);
    }
}
