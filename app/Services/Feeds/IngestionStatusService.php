<?php
/**
 * AegisZ Sentinel - Ingestion Status Service
 * Tracks worker run status for dashboard display.
 */

namespace App\Services\Feeds;

use App\Core\Database;
use PDO;

class IngestionStatusService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM ingestion_status ORDER BY worker_name ASC");
        return $stmt->fetchAll();
    }

    public function updateStatus(string $workerName, string $status, int $fetched = 0, int $inserted = 0, int $updated = 0, int $skipped = 0, ?string $error = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ingestion_status
             (worker_name, last_run_at, status, records_fetched, records_inserted, records_updated, records_skipped, error_message)
             VALUES
             (:worker, NOW(), :status, :fetched, :inserted, :updated, :skipped, :error)
             ON DUPLICATE KEY UPDATE
             last_run_at = NOW(),
             status = :status_upd,
             records_fetched = :fetched_upd,
             records_inserted = :inserted_upd,
             records_updated = :updated_upd,
             records_skipped = :skipped_upd,
             error_message = :error_upd"
        );
        $stmt->execute([
            'worker' => $workerName,
            'status' => $status,
            'fetched' => $fetched,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'error' => $error,
            'status_upd' => $status,
            'fetched_upd' => $fetched,
            'inserted_upd' => $inserted,
            'updated_upd' => $updated,
            'skipped_upd' => $skipped,
            'error_upd' => $error,
        ]);
    }
}
