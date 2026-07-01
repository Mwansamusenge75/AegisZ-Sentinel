<?php
/**
 * AegisZ Sentinel - Bootstrap Entry Point
 * All requests route through here.
 */

// Error reporting (development)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('Africa/Lusaka');

// Load environment variables from .env (v0.7.0)
require_once __DIR__ . '/../app/Core/Env.php';
\App\Core\Env::load();

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load config
require __DIR__ . '/../config/config.php';

// Initialize core
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Logger;

Config::load();

// Register error handler
$errorHandler = new ErrorHandler();
$errorHandler->register();

// Log application bootstrap
$logger = new Logger();
$logger->info('Application bootstrapped', ['uri' => $_SERVER['REQUEST_URI'] ?? 'unknown']);

// Route the request
require __DIR__ . '/../routes/web.php';
