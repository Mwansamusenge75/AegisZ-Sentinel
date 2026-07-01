<?php
/**
 * AegisZ Sentinel - CLI Bootstrap
 * Standalone bootstrap for command-line workers.
 * Does NOT load the web router or trigger HTTP dispatch.
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('Africa/Lusaka');

// Load environment variables from .env (v0.7.0 — for API keys, never hardcoded)
require_once __DIR__ . '/app/Core/Env.php';
\App\Core\Env::load();

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

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
require __DIR__ . '/config/config.php';

// Initialize core
use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Logger;

Config::load();

// Register error handler
$errorHandler = new ErrorHandler();
$errorHandler->register();

// Log CLI bootstrap
$logger = new Logger();
$logger->info('CLI worker bootstrapped', ['php_sapi' => php_sapi_name()]);
