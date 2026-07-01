<?php
/**
 * AegisZ Sentinel - Operations Controller (v0.7.0)
 * Serves the National Cyber Situational Awareness Map page.
 * The page itself loads data client-side via the /api/map/* endpoints —
 * this controller just renders the shell and passes initial config.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Services\Map\MapService;

class OperationsController extends BaseController
{
    private MapService $mapService;

    public function __construct()
    {
        parent::__construct();
        $this->mapService = new MapService();
    }

    /** GET /operations/map */
    public function map(): void
    {
        $this->render('operations/map', [
            'title'    => 'Operational Map | AegisZ Sentinel',
            'appName'  => 'AegisZ Sentinel',
            'version'  => '0.7.0',
            'overview' => $this->mapService->getNationalOverview(),
        ]);
    }
}
