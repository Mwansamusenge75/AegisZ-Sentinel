-- AegisZ Sentinel - Migration 006: Intelligence Layer (v0.4.0)
-- Creates: correlations, risk_score_history, mitre_mappings

USE aegisz_db;

-- ============================================================
-- 1. CORRELATIONS
-- Stores explainable correlation records produced by
-- ThreatCorrelationService. One record per unique correlation
-- event per day (deduplicated by correlation_hash).
-- ============================================================
CREATE TABLE IF NOT EXISTS correlations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    correlation_type ENUM('ioc_asset', 'ioc_alert', 'threat_incident') NOT NULL,
    ioc_id           INT NULL,
    ioc_value        VARCHAR(512) NULL,
    ioc_type         VARCHAR(50) NULL,
    source_feed      VARCHAR(100) NULL,
    asset_id         INT NULL,
    asset_name       VARCHAR(255) NULL,
    alert_id         INT NULL,
    incident_id      INT NULL,
    confidence       TINYINT UNSIGNED NOT NULL DEFAULT 50 CHECK (confidence BETWEEN 0 AND 100),
    severity         ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    explanation      TEXT NOT NULL,
    correlation_hash VARCHAR(32) NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_corr_type       (correlation_type),
    INDEX idx_corr_severity   (severity),
    INDEX idx_corr_confidence (confidence),
    INDEX idx_corr_hash       (correlation_hash),
    INDEX idx_corr_ioc        (ioc_id),
    INDEX idx_corr_asset      (asset_id),
    INDEX idx_corr_created    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. RISK SCORE HISTORY
-- Stores one Security Posture Score snapshot per Intelligence
-- Worker run. Used for trend display on the dashboard.
-- ============================================================
CREATE TABLE IF NOT EXISTS risk_score_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    score           TINYINT UNSIGNED NOT NULL CHECK (score BETWEEN 0 AND 100),
    rating          VARCHAR(20) NOT NULL,
    total_deduction TINYINT UNSIGNED NOT NULL DEFAULT 0,
    breakdown_json  JSON NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rsh_score   (score),
    INDEX idx_rsh_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. MITRE MAPPINGS
-- Audit table recording when a threat was mapped to a technique
-- and by which method ('keyword' in v0.4.0).
-- ============================================================
CREATE TABLE IF NOT EXISTS mitre_mappings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    threat_id      INT NOT NULL,
    technique_id   VARCHAR(10) NOT NULL,
    technique_name VARCHAR(100) NOT NULL,
    tactic         VARCHAR(100) NOT NULL,
    source_feed    VARCHAR(100) NULL,
    match_method   VARCHAR(50) NOT NULL DEFAULT 'keyword',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mm_threat     (threat_id),
    INDEX idx_mm_technique  (technique_id),
    INDEX idx_mm_tactic     (tactic),
    INDEX idx_mm_created    (created_at),
    FOREIGN KEY (threat_id) REFERENCES threats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Add IntelligenceWorker to ingestion_status tracking
-- ============================================================
INSERT INTO ingestion_status (worker_name, last_run_at, status, records_fetched, records_inserted, records_updated, records_skipped)
VALUES ('IntelligenceWorker', NULL, 'success', 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE worker_name = worker_name;
