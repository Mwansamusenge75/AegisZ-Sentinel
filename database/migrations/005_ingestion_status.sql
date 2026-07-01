-- AegisZ Sentinel - Migration 005: Ingestion Status Tracking (v0.3.0)
-- Tracks last run time and results for each feed worker.

USE aegisz_db;

CREATE TABLE IF NOT EXISTS ingestion_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_name VARCHAR(50) NOT NULL UNIQUE,
    last_run_at TIMESTAMP NULL,
    status ENUM('success', 'failed', 'running') DEFAULT 'success',
    records_fetched INT DEFAULT 0,
    records_inserted INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_skipped INT DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_worker_name (worker_name),
    INDEX idx_last_run (last_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial status rows
INSERT INTO ingestion_status (worker_name, last_run_at, status, records_fetched, records_inserted, records_updated, records_skipped)
VALUES
('UrlHausWorker', NULL, 'success', 0, 0, 0, 0),
('OTXWorker', NULL, 'success', 0, 0, 0, 0),
('NvdWorker', NULL, 'success', 0, 0, 0, 0),
('CisaWorker', NULL, 'success', 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE worker_name = worker_name;
