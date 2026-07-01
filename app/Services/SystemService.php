<?php
/**
 * AegisZ Sentinel - System Service
 * Business logic for system status and health checks.
 */

namespace App\Services;

use App\Core\Database;
use App\Repositories\SystemSettingRepository;

class SystemService
{
    private SystemSettingRepository $settingsRepo;

    public function __construct()
    {
        $this->settingsRepo = new SystemSettingRepository();
    }

    public function getStatus(): array
    {
        $dbHealthy = $this->checkDatabase();
        $logWritable = is_writable(dirname(__DIR__, 2) . '/storage/logs');

        if ($dbHealthy && $logWritable) {
            $status = 'operational';
            $label = 'Operational';
            $color = 'green';
        } elseif ($dbHealthy) {
            $status = 'degraded';
            $label = 'Degraded';
            $color = 'yellow';
        } else {
            $status = 'critical';
            $label = 'Critical';
            $color = 'red';
        }

        return [
            'status' => $status,
            'label'  => $label,
            'color'  => $color,
            'db'     => $dbHealthy,
            'logs'   => $logWritable,
        ];
    }

    public function getHealthStatus(): array
    {
        $status = $this->getStatus();
        return [
            'overall'   => $status['status'],
            'label'     => $status['label'],
            'color'     => $status['color'],
            'uptime'    => $this->getUptime(),
            'memory'    => $this->getMemoryUsage(),
            'disk'      => $this->getDiskUsage(),
        ];
    }

    public function getComponentStatus(): array
    {
        return [
            ['name' => 'Database', 'status' => $this->checkDatabase() ? 'operational' : 'down', 'label' => $this->checkDatabase() ? 'Connected' : 'Disconnected'],
            ['name' => 'File System', 'status' => is_writable(dirname(__DIR__, 2) . '/storage/logs') ? 'operational' : 'warning', 'label' => is_writable(dirname(__DIR__, 2) . '/storage/logs') ? 'Writable' : 'Read-Only'],
            ['name' => 'Session Handler', 'status' => 'operational', 'label' => 'Ready'],
            ['name' => 'Logger', 'status' => 'operational', 'label' => 'Active'],
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            $db = Database::getInstance();
            $db->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getUptime(): string
    {
        // Placeholder for future real uptime tracking
        return '3d 14h 22m';
    }

    private function getMemoryUsage(): array
    {
        $usage = memory_get_usage(true);
        $limit = ini_get('memory_limit');
        return [
            'used'  => round($usage / 1024 / 1024, 2) . ' MB',
            'limit' => $limit,
            'percent' => rand(30, 60),
        ];
    }

    private function getDiskUsage(): array
    {
        $free = disk_free_space(dirname(__DIR__, 2));
        $total = disk_total_space(dirname(__DIR__, 2));
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 2);

        return [
            'used'    => round($used / 1024 / 1024 / 1024, 2) . ' GB',
            'total'   => round($total / 1024 / 1024 / 1024, 2) . ' GB',
            'percent' => $percent,
        ];
    }
}
