<?php
/**
 * AegisZ Sentinel - View Renderer
 * Renders PHP view templates with extracted data.
 */

namespace App\Core;

class View
{
    private string $viewPath;
    private string $layoutPath;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->viewPath = $config['paths']['app'] . '/Views';
        $this->layoutPath = $this->viewPath . '/layouts';
    }

    public function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $viewFile = $this->viewPath . '/pages/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view}");
        }

        extract($data);
        $view = $this; // Make View instance available inside page templates (e.g. $view->partial())

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = $this->layoutPath . '/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    public function partial(string $component, array $data = []): void
    {
        $file = $this->viewPath . '/components/' . $component . '.php';
        if (file_exists($file)) {
            extract($data);
            require $file;
        }
    }
}
