-- AegisZ Sentinel - Migration 003: Cyber Data Backbone (v0.2.0)
-- Extends schema with Assets, IOCs, Threats, Alerts, Incidents

USE aegisz_db;

-- ============================================================
-- 1. ASSETS
-- ============================================================
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hostname VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    asset_type ENUM('server', 'endpoint', 'router', 'app', 'db', 'website') NOT NULL DEFAULT 'server',
    department VARCHAR(100) NULL,
    owner VARCHAR(100) NULL,
    location VARCHAR(100) NULL,
    criticality ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_type (asset_type),
    INDEX idx_criticality (criticality),
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. IOCS (Indicators of Compromise)
-- ============================================================
CREATE TABLE IF NOT EXISTS iocs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('ip', 'domain', 'url', 'hash') NOT NULL,
    value VARCHAR(512) NOT NULL,
    source VARCHAR(100) NULL,
    confidence_score TINYINT UNSIGNED NULL CHECK (confidence_score BETWEEN 0 AND 100),
    first_seen TIMESTAMP NULL,
    last_seen TIMESTAMP NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ioc_type (type),
    INDEX idx_ioc_value (value(100)),
    INDEX idx_confidence (confidence_score),
    INDEX idx_source (source),
    INDEX idx_first_seen (first_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. THREATS
-- ============================================================
CREATE TABLE IF NOT EXISTS threats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    source_feed VARCHAR(100) NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    mitre_technique VARCHAR(50) NULL,
    raw_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_threat_severity (severity),
    INDEX idx_mitre (mitre_technique),
    INDEX idx_source_feed (source_feed),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. ALERTS
-- ============================================================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'acknowledged', 'closed') NOT NULL DEFAULT 'open',
    linked_ioc_id INT NULL,
    linked_asset_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alert_status (status),
    INDEX idx_alert_severity (severity),
    INDEX idx_linked_ioc (linked_ioc_id),
    INDEX idx_linked_asset (linked_asset_id),
    FOREIGN KEY (linked_ioc_id) REFERENCES iocs(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_asset_id) REFERENCES assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. INCIDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    status ENUM('open', 'investigating', 'contained', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    linked_alert_id INT NULL,
    linked_asset_id INT NULL,
    timeline JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incident_status (status),
    INDEX idx_incident_severity (severity),
    INDEX idx_linked_alert (linked_alert_id),
    INDEX idx_linked_asset (linked_asset_id),
    FOREIGN KEY (linked_alert_id) REFERENCES alerts(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_asset_id) REFERENCES assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
