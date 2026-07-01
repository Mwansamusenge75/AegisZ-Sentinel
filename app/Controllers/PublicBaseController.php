<?php
/**
 * AegisZ Sentinel - Public Base Controller (v0.5.0)
 * Base for controllers that must NOT require authentication.
 * Used only by AuthController (login page).
 * Does NOT call AuthMiddleware.
 */

namespace App\Controllers;

use App\Core\View;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Session;
use App\Core\Config;

abstract class PublicBaseController
{
    protected View $view;
    protected Logger $logger;
    protected string $baseUrl;

    public function __construct()
    {
        Session::start();
        $this->view    = new View();
        $this->logger  = new Logger();
        $this->baseUrl = Config::get('app.base_url', '/aegisz-sentinel');
    }

    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        $data['baseUrl']      = $this->baseUrl;
        $data['flashError']   = Session::getFlash('error');
        $data['flashSuccess'] = Session::getFlash('success');
        $this->view->render($view, $data, $layout);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }
}
