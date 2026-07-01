<?php
/**
 * AegisZ Sentinel - Error Handler
 * Clean error display. Logs all errors. No stack traces in production.
 */

namespace App\Core;

class ErrorHandler
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $this->logger->error("PHP Error [{$errno}]: {$errstr}", [
            'file' => $errfile,
            'line' => $errline,
        ]);

        if (Config::get('app.debug', false)) {
            echo "<pre>Error [{$errno}]: {$errstr} in {$errfile}:{$errline}</pre>";
        }

        return true;
    }

    public function handleException(\Throwable $e): void
    {
        $this->logger->error("Uncaught Exception: {$e->getMessage()}", [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        http_response_code(500);

        if (Config::get('app.debug', false)) {
            echo "<pre>Exception: {$e->getMessage()}\nIn {$e->getFile()}:{$e->getLine()}\n\n{$e->getTraceAsString()}</pre>";
        } else {
            echo "<h1>500 - Internal Server Error</h1><p>An unexpected error occurred. Please check the logs.</p>";
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->error("Fatal Error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
            http_response_code(500);
            echo "<h1>500 - Fatal Error</h1>";
        }
    }
}
