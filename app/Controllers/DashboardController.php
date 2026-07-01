<?php
/**
 * AegisZ Sentinel - Dashboard Controller (v0.6.0)
 * Role-aware dashboard: admin / analyst / viewer each get targeted data.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Repositories\AuditLogRepository;
use App\Repositories\CorrelationRepository;
use App\Services\SystemService;
use App\Domain\Asset\AssetRepository;
use App\Domain\IOC\IOCRepository;
use App\Domain\Threat\ThreatRepository;
use App\Domain\Alert\AlertRepository;
use App\Domain\Incident\IncidentRepository;
use App\Services\Feeds\IngestionStatusService;
use App\Services\Correlation\ThreatCorrelationService;
use App\Services\Scoring\RiskScoringService;
use App\Services\ThreatIntel\MitreMappingService;
use App\Domain\Asset\AssetService;
use App\Domain\IOC\IOCService;
use App\Domain\Threat\ThreatService;
use App\Domain\Alert\AlertService;
use App\Domain\Incident\IncidentService;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $role = $this->currentUser['role'] ?? 'viewer';
        $this->logger->info("Dashboard visited [{$role}]", ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        match ($role) {
            'admin'   => $this->adminDashboard(),
            'analyst' => $this->analystDashboard(),
            default   => $this->viewerDashboard(),
        };
    }

    private function adminDashboard(): void
    {
        $systemService    = new SystemService();
        $auditLogRepo     = new AuditLogRepository();
        $assetService     = new AssetService();
        $iocService       = new IOCService();
        $threatService    = new ThreatService();
        $alertService     = new AlertService();
        $incidentService  = new IncidentService();
        $ingestionStatus  = new IngestionStatusService();
        $corrService      = new ThreatCorrelationService();
        $scoringService   = new RiskScoringService();
        $mitreService     = new MitreMappingService();

        $this->render('dashboard/admin', [
            'title'                  => 'Admin Dashboard | AegisZ Sentinel',
            'appName'                => 'AegisZ Sentinel',
            'version'                => '0.6.0',
            'systemStatus'           => $systemService->getStatus(),
            'logEventsCount'         => $auditLogRepo->count(),
            'recentActivity'         => $auditLogRepo->getRecent(8),
            'assetCounts'            => $assetService->getCounts(),
            'iocCounts'              => $iocService->getCounts(),
            'threatCounts'           => $threatService->getCounts(),
            'alertCounts'            => $alertService->getCounts(),
            'incidentCounts'         => $incidentService->getCounts(),
            'ingestionStatus'        => $ingestionStatus->getAll(),
            'latestScore'            => $scoringService->getLatest(),
            'recentCorrelations'     => $corrService->getRecent(6),
            'correlationsBySeverity' => $corrService->countBySeverity(),
            'mitreDistribution'      => $mitreService->getTechniqueDistribution(),
            'csrfToken'              => Security::generateCsrfToken(),
        ]);
    }

    private function analystDashboard(): void
    {
        $userId          = (int) ($this->currentUser['id'] ?? 0);
        $alertRepo       = new AlertRepository();
        $incidentRepo    = new IncidentRepository();
        $assetRepo       = new AssetRepository();
        $threatRepo      = new ThreatRepository();
        $corrRepo        = new CorrelationRepository();
        $scoringService  = new RiskScoringService();
        $iocRepo         = new IOCRepository();

        // Assigned to this analyst, or all open as fallback
        $assignedAlerts    = $this->getAssignedOrOpen($alertRepo, $userId, 'alerts');
        $assignedIncidents = $this->getAssignedOrOpen($incidentRepo, $userId, 'incidents');

        $this->render('dashboard/analyst', [
            'title'              => 'Analyst Dashboard | AegisZ Sentinel',
            'appName'            => 'AegisZ Sentinel',
            'version'            => '0.6.0',
            'assignedAlerts'     => $assignedAlerts,
            'assignedIncidents'  => $assignedIncidents,
            'highRiskAssets'     => $assetRepo->getCriticalWithCorrelations(5),
            'latestThreats'      => $threatRepo->getLatest(8),
            'recentCorrelations' => $corrRepo->getRecent(6),
            'latestScore'        => $scoringService->getLatest(),
            'recentIOCs'         => $iocRepo->findAll([], 8),
            'csrfToken'          => Security::generateCsrfToken(),
        ]);
    }

    private function viewerDashboard(): void
    {
        $threatRepo     = new ThreatRepository();
        $corrRepo       = new CorrelationRepository();
        $scoringService = new RiskScoringService();
        $mitreService   = new MitreMappingService();
        $assetService   = new AssetService();
        $iocService     = new IOCService();
        $threatService  = new ThreatService();

        $this->render('dashboard/viewer', [
            'title'                  => 'Dashboard | AegisZ Sentinel',
            'appName'                => 'AegisZ Sentinel',
            'version'                => '0.6.0',
            'latestScore'            => $scoringService->getLatest(),
            'mitreDistribution'      => $mitreService->getTechniqueDistribution(),
            'recentCorrelations'     => $corrRepo->getRecent(6),
            'correlationsBySeverity' => $corrRepo->countBySeverity(),
            'latestThreats'          => $threatRepo->getLatest(6),
            'assetCounts'            => $assetService->getCounts(),
            'iocCounts'              => $iocService->getCounts(),
            'threatCounts'           => $threatService->getCounts(),
        ]);
    }

    /**
     * Get alerts/incidents assigned to this user, or all open ones if none assigned.
     */
    private function getAssignedOrOpen(object $repo, int $userId, string $type): array
    {
        $db = \App\Core\Database::getInstance();

        if ($type === 'alerts') {
            $stmt = $db->prepare(
                "SELECT * FROM alerts WHERE assigned_to = :uid AND status NOT IN ('closed','resolved')
                 ORDER BY created_at DESC LIMIT 10"
            );
        } else {
            $stmt = $db->prepare(
                "SELECT * FROM incidents WHERE assigned_to = :uid AND status NOT IN ('closed','resolved')
                 ORDER BY created_at DESC LIMIT 10"
            );
        }
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        if (!empty($rows)) return $rows;

        // Fallback: all open
        if ($type === 'alerts') {
            $stmt2 = $db->prepare(
                "SELECT * FROM alerts WHERE status = 'open' ORDER BY created_at DESC LIMIT 10"
            );
        } else {
            $stmt2 = $db->prepare(
                "SELECT * FROM incidents WHERE status = 'open' ORDER BY created_at DESC LIMIT 10"
            );
        }
        $stmt2->execute();
        return $stmt2->fetchAll();
    }
}
