<?php
/**
 * AegisZ Sentinel - File-Based Logger
 * Production-style logging to /storage/logs/app.log
 */

namespace App\Core;

class Logger
{
    private string $logPath;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->logPath = $config['paths']['storage'] . '/logs/app.log';
        $this->ensureDirectory();
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        if ($config['app']['debug']) {
            $this->log('DEBUG', $message, $context);
        }
    }
}
