<?php
/**
 * AegisZ Sentinel - System Setting Repository
 */

namespace App\Repositories;

use App\Models\SystemSetting;
use App\Core\Database;

class SystemSettingRepository
{
    private SystemSetting $model;

    public function __construct()
    {
        $this->model = new SystemSetting();
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    public function set(string $key, string $value): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
        return $stmt->execute(['key' => $key, 'value' => $value]);
    }

    public function all(): array
    {
        return $this->model->findAll('id ASC', 100);
    }
}
