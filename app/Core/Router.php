<?php
/**
 * AegisZ Sentinel - Router
 * Simple but extensible routing engine with GET/POST support and middleware hooks.
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->basePath = rtrim($config['app']['base_url'], '/');
    }

    public function get(string $route, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $route, $handler, $middleware);
    }

    public function post(string $route, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $route, $handler, $middleware);
    }

    private function addRoute(string $method, string $route, string $handler, array $middleware): void
    {
        $this->routes[$method][$route] = [
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace($this->basePath, '', $uri);
        $uri = rtrim($uri, '/') ?: '/';
        $method = $_SERVER['REQUEST_METHOD'];

        if (!isset($this->routes[$method][$uri])) {
            $this->handle404();
            return;
        }

        $route = $this->routes[$method][$uri];

        // Run middleware
        foreach ($route['middleware'] as $mw) {
            $middlewareClass = "App\\Middleware\\{$mw}";
            if (class_exists($middlewareClass)) {
                $instance = new $middlewareClass();
                if (method_exists($instance, 'handle')) {
                    $instance->handle();
                }
            }
        }

        // Parse handler: Controller@method
        [$controllerClass, $methodName] = explode('@', $route['handler']);
        $controllerClass = "App\\Controllers\\{$controllerClass}";

        if (!class_exists($controllerClass)) {
            $this->handle404();
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            $this->handle404();
            return;
        }

        $controller->$methodName();
    }

    private function handle404(): void
    {
        http_response_code(404);
        $view = new View();
        $view->render('errors/404', ['title' => 'Page Not Found']);
    }
}
