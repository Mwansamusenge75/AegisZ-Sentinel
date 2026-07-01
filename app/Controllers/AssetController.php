<?php
/**
 * AegisZ Sentinel - Asset Controller (v0.6.0)
 * HTTP handler for Asset CRUD and detail view.
 * No SQL. No business logic. HTTP only.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\SearchQuery;
use App\Domain\Asset\AssetService;
use App\Domain\Asset\AssetEntity;
use App\Domain\Asset\AssetRepository;
use App\Middleware\RoleMiddleware;

class AssetController extends BaseController
{
    private AssetService    $assetService;
    private AssetRepository $assetRepo;

    public function __construct()
    {
        parent::__construct();
        $this->assetService = new AssetService();
        $this->assetRepo    = new AssetRepository();
    }

    /** GET /assets */
    public function index(): void
    {
        $q      = SearchQuery::fromRequest($_GET, ['name','created_at','criticality','ip_address','asset_type']);
        $result = $this->assetRepo->search($q);

        $this->render('assets/index', [
            'title'       => 'Assets | AegisZ Sentinel',
            'appName'     => 'AegisZ Sentinel',
            'version'     => '0.6.0',
            'assets'      => $result['data'],
            'paginator'   => $result['paginator'],
            'q'           => $q,
            'departments' => $this->assetRepo->getDistinctDepartments(),
            'locations'   => $this->assetRepo->getDistinctLocations(),
            'csrfToken'   => Security::generateCsrfToken(),
        ]);
    }

    /** GET /assets/detail?id=N */
    public function detail(): void
    {
        $id    = (int) ($_GET['id'] ?? 0);
        $asset = $this->assetRepo->findById($id);
        if (!$asset) {
            Session::flash('error', 'Asset not found.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }

        $this->render('assets/detail', [
            'title'        => "Asset: {$asset->name} | AegisZ Sentinel",
            'appName'      => 'AegisZ Sentinel',
            'version'      => '0.6.0',
            'asset'        => $asset,
            'alerts'       => $this->assetRepo->getLinkedAlerts($id),
            'incidents'    => $this->assetRepo->getLinkedIncidents($id),
            'correlations' => $this->assetRepo->getLinkedCorrelations($id),
            'threats'      => $this->assetRepo->getLinkedThreats($id),
            'notes'        => $this->assetRepo->getNotes($id),
            'csrfToken'    => Security::generateCsrfToken(),
        ]);
    }

    /** GET /assets/create */
    public function create(): void
    {
        RoleMiddleware::requireRole('analyst');
        $this->render('assets/create', [
            'title'     => 'Create Asset | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.6.0',
            'errors'    => [],
            'old'       => [],
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** POST /assets/store */
    public function store(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/assets/create');
            return;
        }

        $entity                  = new AssetEntity();
        $entity->name            = Security::sanitize($_POST['name'] ?? '');
        $entity->hostname        = Security::sanitize($_POST['hostname'] ?? '') ?: null;
        $entity->ipAddress       = Security::sanitize($_POST['ip_address'] ?? '') ?: null;
        $entity->assetType       = Security::sanitize($_POST['asset_type'] ?? 'server');
        $entity->department      = Security::sanitize($_POST['department'] ?? '') ?: null;
        $entity->owner           = Security::sanitize($_POST['owner'] ?? '') ?: null;
        $entity->location        = Security::sanitize($_POST['location'] ?? '') ?: null;
        $entity->criticality     = Security::sanitize($_POST['criticality'] ?? 'medium');
        $entity->status          = Security::sanitize($_POST['status'] ?? 'active');
        $entity->operatingSystem = Security::sanitize($_POST['operating_system'] ?? '') ?: null;
        $entity->networkSegment  = Security::sanitize($_POST['network_segment'] ?? '') ?: null;
        $entity->notes           = Security::sanitize($_POST['notes'] ?? '') ?: null;

        $errors = $entity->validate();
        if (!empty($errors)) {
            $this->render('assets/create', [
                'title'     => 'Create Asset | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.6.0',
                'errors'    => $errors,
                'old'       => $_POST,
                'csrfToken' => Security::generateCsrfToken(),
            ]);
            return;
        }

        $id = $this->assetRepo->create($entity);
        $this->logger->info("[AssetController] Asset created: {$entity->name}", ['id' => $id]);
        Session::flash('success', "Asset '{$entity->name}' created successfully.");
        $this->redirect($this->baseUrl . '/assets/detail?id=' . $id);
    }

    /** GET /assets/edit?id=N */
    public function edit(): void
    {
        RoleMiddleware::requireRole('analyst');
        $id    = (int) ($_GET['id'] ?? 0);
        $asset = $this->assetRepo->findById($id);
        if (!$asset) {
            Session::flash('error', 'Asset not found.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }
        $this->render('assets/edit', [
            'title'     => "Edit Asset | AegisZ Sentinel",
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.6.0',
            'asset'     => $asset,
            'errors'    => [],
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** POST /assets/update */
    public function update(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }

        $id    = (int) ($_POST['asset_id'] ?? 0);
        $asset = $this->assetRepo->findById($id);
        if (!$asset) {
            Session::flash('error', 'Asset not found.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }

        $asset->name            = Security::sanitize($_POST['name'] ?? '');
        $asset->hostname        = Security::sanitize($_POST['hostname'] ?? '') ?: null;
        $asset->ipAddress       = Security::sanitize($_POST['ip_address'] ?? '') ?: null;
        $asset->assetType       = Security::sanitize($_POST['asset_type'] ?? $asset->assetType);
        $asset->department      = Security::sanitize($_POST['department'] ?? '') ?: null;
        $asset->owner           = Security::sanitize($_POST['owner'] ?? '') ?: null;
        $asset->location        = Security::sanitize($_POST['location'] ?? '') ?: null;
        $asset->criticality     = Security::sanitize($_POST['criticality'] ?? $asset->criticality);
        $asset->status          = Security::sanitize($_POST['status'] ?? $asset->status);
        $asset->operatingSystem = Security::sanitize($_POST['operating_system'] ?? '') ?: null;
        $asset->networkSegment  = Security::sanitize($_POST['network_segment'] ?? '') ?: null;
        $asset->notes           = Security::sanitize($_POST['notes'] ?? '') ?: null;

        $errors = $asset->validate();
        if (!empty($errors)) {
            $this->render('assets/edit', [
                'title'     => 'Edit Asset | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.6.0',
                'asset'     => $asset,
                'errors'    => $errors,
                'csrfToken' => Security::generateCsrfToken(),
            ]);
            return;
        }

        $this->assetRepo->update($asset);
        Session::flash('success', "Asset '{$asset->name}' updated.");
        $this->redirect($this->baseUrl . '/assets/detail?id=' . $id);
    }

    /** POST /assets/delete */
    public function delete(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }
        $id = (int) ($_POST['asset_id'] ?? 0);
        $this->assetRepo->delete($id);
        Session::flash('success', 'Asset deleted.');
        $this->redirect($this->baseUrl . '/assets');
    }

    /** POST /assets/note */
    public function addNote(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }
        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $note    = trim(Security::sanitize($_POST['note'] ?? ''));
        if ($assetId && $note !== '') {
            $userId = (int) ($this->currentUser['id'] ?? 0);
            $this->assetRepo->addNote($assetId, $userId, $note);
            Session::flash('success', 'Note added.');
        } else {
            Session::flash('error', 'Note cannot be empty.');
        }
        $this->redirect($this->baseUrl . '/assets/detail?id=' . $assetId);
    }
}

    /** POST /assets/set-location — manual geo-location pin (no geocoding API) */
    public function setLocation(): void
    {
        \App\Middleware\RoleMiddleware::requireRole('analyst');
        if (!\App\Core\Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            \App\Core\Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }

        $id  = (int) ($_POST['asset_id'] ?? 0);
        $lat = is_numeric($_POST['latitude'] ?? '')  ? (float) $_POST['latitude']  : null;
        $lng = is_numeric($_POST['longitude'] ?? '') ? (float) $_POST['longitude'] : null;

        if (!$id || $lat === null || $lng === null) {
            \App\Core\Session::flash('error', 'Asset ID and valid coordinates are required.');
            $this->redirect($this->baseUrl . '/assets');
            return;
        }

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            \App\Core\Session::flash('error', 'Coordinates out of valid range.');
            $this->redirect($this->baseUrl . '/assets/detail?id=' . $id);
            return;
        }

        $province     = \App\Core\Security::sanitize($_POST['province'] ?? '') ?: null;
        $district     = \App\Core\Security::sanitize($_POST['district'] ?? '') ?: null;
        $locationName = \App\Core\Security::sanitize($_POST['location_name'] ?? '') ?: null;

        $this->assetRepo->setLocation($id, $lat, $lng, $province, $district, $locationName);
        \App\Core\Session::flash('success', 'Asset location set. It will appear on the Operational Map.');
        $this->redirect($this->baseUrl . '/assets/detail?id=' . $id);
    }
