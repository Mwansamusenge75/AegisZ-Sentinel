-- AegisZ Sentinel - Migration 008: SOC Operations Layer (v0.6.0)
-- Adds: asset_notes, ioc_history tables
-- Extends: assets, iocs, threats, correlations with new columns
-- Idempotent: safe to re-run. Uses IF NOT EXISTS throughout.

USE aegisz_db;

-- ============================================================
-- 1. ASSET NOTES
-- Analyst notes attached to assets (append-only).
-- ============================================================
CREATE TABLE IF NOT EXISTS asset_notes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    asset_id   INT NOT NULL,
    user_id    INT NOT NULL,
    note       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_an_asset   (asset_id),
    INDEX idx_an_user    (user_id),
    INDEX idx_an_created (created_at),
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. IOC HISTORY
-- Change/event log for IOCs (created, updated, flagged, etc).
-- ============================================================
CREATE TABLE IF NOT EXISTS ioc_history (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ioc_id     INT NOT NULL,
    event      VARCHAR(50) NOT NULL,
    detail     TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ih_ioc     (ioc_id),
    INDEX idx_ih_event   (event),
    INDEX idx_ih_created (created_at),
    FOREIGN KEY (ioc_id) REFERENCES iocs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. ASSETS — new columns for v0.6.0 detail page
-- ============================================================
ALTER TABLE assets
    ADD COLUMN IF NOT EXISTS operating_system VARCHAR(100) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS network_segment  VARCHAR(100) NULL AFTER operating_system,
    ADD COLUMN IF NOT EXISTS notes            TEXT         NULL AFTER network_segment;

-- ============================================================
-- 4. IOCS — false positive, expiry, tags
-- ============================================================
ALTER TABLE iocs
    ADD COLUMN IF NOT EXISTS false_positive TINYINT(1) NOT NULL DEFAULT 0 AFTER raw_data,
    ADD COLUMN IF NOT EXISTS expiry_at      DATETIME   NULL              AFTER false_positive,
    ADD COLUMN IF NOT EXISTS tags           JSON       NULL              AFTER expiry_at;

ALTER TABLE iocs
    ADD INDEX IF NOT EXISTS idx_iocs_false_positive (false_positive),
    ADD INDEX IF NOT EXISTS idx_iocs_expiry (expiry_at);

-- ============================================================
-- 5. THREATS — CVE search support
-- ============================================================
ALTER TABLE threats
    ADD COLUMN IF NOT EXISTS cve_id           VARCHAR(20)  NULL AFTER mitre_technique,
    ADD COLUMN IF NOT EXISTS affected_systems TEXT         NULL AFTER cve_id;

ALTER TABLE threats
    ADD INDEX IF NOT EXISTS idx_threats_cve (cve_id);

-- ============================================================
-- 6. CORRELATIONS — lifecycle tracking (optional resolve flow)
-- ============================================================
ALTER TABLE correlations
    ADD COLUMN IF NOT EXISTS resolved_at DATETIME NULL AFTER explanation,
    ADD COLUMN IF NOT EXISTS resolved_by INT      NULL AFTER resolved_at;

-- Add FK for resolved_by only if not already present (guarded by naming)
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'aegisz_db'
      AND TABLE_NAME = 'correlations'
      AND CONSTRAINT_NAME = 'fk_correlation_resolved_by'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE correlations ADD CONSTRAINT fk_correlation_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "fk_correlation_resolved_by already exists" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration 008 (SOC Operations Layer) applied successfully.' AS result;
