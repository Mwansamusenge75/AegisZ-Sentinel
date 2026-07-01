<?php
/**
 * AegisZ Sentinel - Base Controller (v0.5.0)
 * All protected controllers extend this. Auth check is enforced here.
 * Session is started and current user injected into every render call.
 */

namespace App\Controllers;

use App\Core\View;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Session;
use App\Core\Config;
use App\Middleware\AuthMiddleware;

abstract class BaseController
{
    protected View $view;
    protected Logger $logger;
    protected ?array $currentUser;
    protected string $baseUrl;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::requireAuth();

        $this->view        = new View();
        $this->logger      = new Logger();
        $this->currentUser = Session::getUser();
        $this->baseUrl     = Config::get('app.base_url', '/aegisz-sentinel');
    }

    protected function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Inject auth context into every view automatically
        $data['currentUser'] = $this->currentUser;
        $data['baseUrl']     = $this->baseUrl;
        // Flash messages
        $data['flashError']   = Session::getFlash('error');
        $data['flashSuccess'] = Session::getFlash('success');

        $this->view->render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }
}
