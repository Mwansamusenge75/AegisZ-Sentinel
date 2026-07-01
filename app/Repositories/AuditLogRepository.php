<?php
/**
 * AegisZ Sentinel - Audit Log Repository
 * Handles all audit_log database queries. Controllers do NOT contain SQL.
 */

namespace App\Repositories;

use App\Models\AuditLog;

class AuditLogRepository
{
    private AuditLog $model;

    public function __construct()
    {
        $this->model = new AuditLog();
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->model->findAll('created_at DESC', $limit);
    }

    public function count(): int
    {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT COUNT(*) FROM audit_logs");
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        return $this->model->insert($data);
    }
}
