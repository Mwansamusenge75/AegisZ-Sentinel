<?php
/**
 * AegisZ Sentinel - IOC Controller (v0.6.0)
 * HTTP handler for IOC management. No SQL. No business logic.
 */

namespace App\Controllers;

use App\Core\Security;
use App\Core\Session;
use App\Core\SearchQuery;
use App\Domain\IOC\IOCEntity;
use App\Domain\IOC\IOCRepository;
use App\Middleware\RoleMiddleware;

class IOCController extends BaseController
{
    private IOCRepository $iocRepo;

    public function __construct()
    {
        parent::__construct();
        $this->iocRepo = new IOCRepository();
    }

    /** GET /iocs */
    public function index(): void
    {
        $q      = SearchQuery::fromRequest($_GET, ['created_at','value','confidence_score','type','last_seen']);
        $result = $this->iocRepo->search($q);

        $this->render('iocs/index', [
            'title'     => 'IOCs | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.6.0',
            'iocs'      => $result['data'],
            'paginator' => $result['paginator'],
            'q'         => $q,
            'sources'   => $this->iocRepo->getDistinctSources(),
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** GET /iocs/detail?id=N */
    public function detail(): void
    {
        $id  = (int) ($_GET['id'] ?? 0);
        $ioc = $this->iocRepo->findById($id);
        if (!$ioc) {
            Session::flash('error', 'IOC not found.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }

        $this->render('iocs/detail', [
            'title'        => "IOC: {$ioc->value} | AegisZ Sentinel",
            'appName'      => 'AegisZ Sentinel',
            'version'      => '0.6.0',
            'ioc'          => $ioc,
            'correlations' => $this->iocRepo->getLinkedCorrelations($id),
            'alerts'       => $this->iocRepo->getLinkedAlerts($id),
            'threats'      => $this->iocRepo->getLinkedThreats($ioc->value),
            'history'      => $this->iocRepo->getHistory($id),
            'csrfToken'    => Security::generateCsrfToken(),
        ]);
    }

    /** GET /iocs/create */
    public function create(): void
    {
        RoleMiddleware::requireRole('analyst');
        $this->render('iocs/create', [
            'title'     => 'Create IOC | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.6.0',
            'errors'    => [],
            'old'       => [],
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** POST /iocs/store */
    public function store(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/iocs/create');
            return;
        }

        $entity                  = new IOCEntity();
        $entity->type            = Security::sanitize($_POST['type'] ?? 'ip');
        $entity->value           = Security::sanitize($_POST['value'] ?? '');
        $entity->source          = 'manual';
        $entity->confidenceScore = (int) ($_POST['confidence_score'] ?? 75);
        $entity->firstSeen       = date('Y-m-d H:i:s');
        $entity->lastSeen        = date('Y-m-d H:i:s');
        $entity->falsePositive   = false;
        $entity->expiryAt        = Security::sanitize($_POST['expiry_at'] ?? '') ?: null;
        $rawTags                 = Security::sanitize($_POST['tags'] ?? '');
        $entity->tags            = $rawTags ? array_filter(array_map('trim', explode(',', $rawTags))) : null;

        $errors = $entity->validate();

        // Duplicate check
        if (empty($errors) && $this->iocRepo->findByValue($entity->value)) {
            $errors[] = 'An IOC with this value already exists.';
        }

        if (!empty($errors)) {
            $this->render('iocs/create', [
                'title'     => 'Create IOC | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.6.0',
                'errors'    => $errors,
                'old'       => $_POST,
                'csrfToken' => Security::generateCsrfToken(),
            ]);
            return;
        }

        $id = $this->iocRepo->create($entity);
        $this->iocRepo->addHistory($id, 'created', "Manually created by analyst {$this->currentUser['username']}");
        $this->logger->info("[IOCController] IOC created manually: {$entity->value}", ['id' => $id]);
        Session::flash('success', "IOC '{$entity->value}' created.");
        $this->redirect($this->baseUrl . '/iocs/detail?id=' . $id);
    }

    /** GET /iocs/edit?id=N */
    public function edit(): void
    {
        RoleMiddleware::requireRole('analyst');
        $id  = (int) ($_GET['id'] ?? 0);
        $ioc = $this->iocRepo->findById($id);
        if (!$ioc) {
            Session::flash('error', 'IOC not found.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }
        $this->render('iocs/edit', [
            'title'     => 'Edit IOC | AegisZ Sentinel',
            'appName'   => 'AegisZ Sentinel',
            'version'   => '0.6.0',
            'ioc'       => $ioc,
            'errors'    => [],
            'csrfToken' => Security::generateCsrfToken(),
        ]);
    }

    /** POST /iocs/update */
    public function update(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }

        $id  = (int) ($_POST['ioc_id'] ?? 0);
        $ioc = $this->iocRepo->findById($id);
        if (!$ioc) {
            Session::flash('error', 'IOC not found.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }

        $ioc->confidenceScore = (int) ($_POST['confidence_score'] ?? $ioc->confidenceScore);
        $ioc->expiryAt        = Security::sanitize($_POST['expiry_at'] ?? '') ?: null;
        $rawTags              = Security::sanitize($_POST['tags'] ?? '');
        $ioc->tags            = $rawTags ? array_filter(array_map('trim', explode(',', $rawTags))) : null;

        $errors = $ioc->validate();
        if (!empty($errors)) {
            $this->render('iocs/edit', [
                'title'     => 'Edit IOC | AegisZ Sentinel',
                'appName'   => 'AegisZ Sentinel',
                'version'   => '0.6.0',
                'ioc'       => $ioc,
                'errors'    => $errors,
                'csrfToken' => Security::generateCsrfToken(),
            ]);
            return;
        }

        $this->iocRepo->update($ioc);
        $this->iocRepo->addHistory($id, 'updated', "Updated by {$this->currentUser['username']}");
        Session::flash('success', 'IOC updated.');
        $this->redirect($this->baseUrl . '/iocs/detail?id=' . $id);
    }

    /** POST /iocs/flag — toggle false positive */
    public function flag(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }

        $id  = (int) ($_POST['ioc_id'] ?? 0);
        $ioc = $this->iocRepo->findById($id);
        if (!$ioc) {
            Session::flash('error', 'IOC not found.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }

        $newValue = !$ioc->falsePositive;
        $this->iocRepo->setFalsePositive($id, $newValue);
        $label = $newValue ? 'flagged as false positive' : 'cleared as false positive';
        $this->iocRepo->addHistory($id, 'false_positive_' . ($newValue ? 'set' : 'cleared'),
            "By {$this->currentUser['username']}");
        Session::flash('success', "IOC {$label}.");
        $this->redirect($this->baseUrl . '/iocs/detail?id=' . $id);
    }

    /** POST /iocs/delete */
    public function delete(): void
    {
        RoleMiddleware::requireRole('analyst');
        if (!Security::validateCsrfToken($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Invalid request token.');
            $this->redirect($this->baseUrl . '/iocs');
            return;
        }
        $id = (int) ($_POST['ioc_id'] ?? 0);
        $this->iocRepo->delete($id);
        Session::flash('success', 'IOC deleted.');
        $this->redirect($this->baseUrl . '/iocs');
    }
}
