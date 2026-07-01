<?php
/**
 * AegisZ Sentinel - Threat Controller (v0.6.0)
 * Read-only Threat Intelligence Explorer. Threats are ingested by workers only.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\SearchQuery;
use App\Domain\Threat\ThreatRepository;
use App\Services\ThreatIntel\MitreMappingService;

class ThreatController extends BaseController
{
    private ThreatRepository  $threatRepo;
    private MitreMappingService $mitreService;

    public function __construct()
    {
        parent::__construct();
        $this->threatRepo   = new ThreatRepository();
        $this->mitreService = new MitreMappingService();
    }

    /** GET /threats */
    public function index(): void
    {
        $q      = SearchQuery::fromRequest($_GET, ['created_at','title','severity','source_feed']);
        $result = $this->threatRepo->search($q);

        $this->render('threats/index', [
            'title'      => 'Threat Intelligence Explorer | AegisZ Sentinel',
            'appName'    => 'AegisZ Sentinel',
            'version'    => '0.6.0',
            'threats'    => $result['data'],
            'paginator'  => $result['paginator'],
            'q'          => $q,
            'sources'    => $this->threatRepo->getDistinctSources(),
            'techniques' => $this->threatRepo->getDistinctMitreTechniques(),
            'registry'   => $this->mitreService->getTechniqueRegistry(),
        ]);
    }

    /** GET /threats/detail?id=N */
    public function detail(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $threat = $this->threatRepo->findById($id);
        if (!$threat) {
            Session::flash('error', 'Threat not found.');
            $this->redirect($this->baseUrl . '/threats');
            return;
        }

        $mitreInfo = $threat->mitreTechnique
            ? $this->mitreService->resolveId($threat->mitreTechnique)
            : null;

        $this->render('threats/detail', [
            'title'        => "Threat: {$threat->title} | AegisZ Sentinel",
            'appName'      => 'AegisZ Sentinel',
            'version'      => '0.6.0',
            'threat'       => $threat,
            'mitreInfo'    => $mitreInfo,
            'linkedIOCs'   => $this->threatRepo->getLinkedIOCs($id),
            'correlations' => $this->threatRepo->getLinkedCorrelations($id),
        ]);
    }
}
