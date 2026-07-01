<?php
/**
 * AegisZ Sentinel - Log Controller
 * System logs viewer page.
 */

namespace App\Controllers;

use App\Repositories\AuditLogRepository;

class LogController extends BaseController
{
    private AuditLogRepository $auditLogRepo;

    public function __construct()
    {
        parent::__construct();
        $this->auditLogRepo = new AuditLogRepository();
    }

    public function index(): void
    {
        $this->logger->info('Logs page visited', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

        $data = [
            'title'      => 'System Logs | AegisZ Sentinel',
            'appName'    => 'AegisZ Sentinel',
            'logs'       => $this->auditLogRepo->getRecent(50),
            'totalLogs'  => $this->auditLogRepo->count(),
        ];

        $this->render('logs/index', $data);
    }
}
