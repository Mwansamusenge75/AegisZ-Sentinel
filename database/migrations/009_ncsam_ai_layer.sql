-- AegisZ Sentinel - Migration 009: NCSAM Map + AI Intelligence Layer (v0.7.0)
-- Idempotent: safe to re-run. Uses IF NOT EXISTS throughout.
-- No existing data is modified or deleted.

USE aegisz_db;

-- ============================================================
-- 1. ASSETS — geographic fields for map integration
-- ============================================================
ALTER TABLE assets
    ADD COLUMN IF NOT EXISTS latitude      DECIMAL(10,7) NULL AFTER notes,
    ADD COLUMN IF NOT EXISTS longitude     DECIMAL(10,7) NULL AFTER latitude,
    ADD COLUMN IF NOT EXISTS province      VARCHAR(50)   NULL AFTER longitude,
    ADD COLUMN IF NOT EXISTS district      VARCHAR(100)  NULL AFTER province,
    ADD COLUMN IF NOT EXISTS location_name VARCHAR(150)  NULL AFTER district;

ALTER TABLE assets
    ADD INDEX IF NOT EXISTS idx_assets_geo (latitude, longitude),
    ADD INDEX IF NOT EXISTS idx_assets_province (province);

-- ============================================================
-- 2. THREATS — optional origin geolocation (nullable, never fabricated)
-- Populated only by a future geolocation enrichment step. The platform
-- never invents coordinates; animated threat paths only render where
-- this data genuinely exists.
-- ============================================================
ALTER TABLE threats
    ADD COLUMN IF NOT EXISTS origin_lat     DECIMAL(10,7) NULL AFTER affected_systems,
    ADD COLUMN IF NOT EXISTS origin_lng     DECIMAL(10,7) NULL AFTER origin_lat,
    ADD COLUMN IF NOT EXISTS origin_country VARCHAR(100)  NULL AFTER origin_lng;

-- ============================================================
-- 3. AI ASSESSMENTS — cached National Assessment outputs
-- ============================================================
CREATE TABLE IF NOT EXISTS ai_assessments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    payload_json  JSON NOT NULL,
    model         VARCHAR(150) NULL,
    threat_level  VARCHAR(20)  NULL,
    confidence    TINYINT UNSIGNED NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_assess_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. AI EXPLANATIONS — cached per-object explanations
-- ============================================================
CREATE TABLE IF NOT EXISTS ai_explanations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    object_type  VARCHAR(50) NOT NULL,
    object_id    INT NOT NULL,
    payload_json JSON NOT NULL,
    model        VARCHAR(150) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_expl_object (object_type, object_id),
    INDEX idx_ai_expl_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. INTELLIGENCE BUS EVENTS — centralized event log
-- ============================================================
CREATE TABLE IF NOT EXISTS intelligence_bus_events (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    event_type       VARCHAR(60) NOT NULL,
    source_component VARCHAR(100) NULL,
    payload_json     JSON NULL,
    processed        TINYINT(1) NOT NULL DEFAULT 0,
    processed_at     TIMESTAMP NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ibe_type      (event_type),
    INDEX idx_ibe_processed (processed),
    INDEX idx_ibe_created   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. NEWS ARTICLES — scaffolding table for future News Intelligence
-- (v0.8.0+). No data is ingested in v0.7.0; table exists so the
-- NewsIngestionService contract has somewhere real to write once
-- provider implementations land.
-- ============================================================
CREATE TABLE IF NOT EXISTS news_articles (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    source_id        VARCHAR(50) NOT NULL,
    title            VARCHAR(500) NOT NULL,
    summary          TEXT NULL,
    url              VARCHAR(1000) NULL,
    published_at     DATETIME NULL,
    priority_tier    ENUM('national','international') NOT NULL DEFAULT 'international',
    affects_zambia   TINYINT(1) NULL,
    affected_sectors JSON NULL,
    is_immediate     TINYINT(1) NULL,
    ai_confidence    TINYINT UNSIGNED NULL,
    ai_summary       TEXT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_news_url (url(255)),
    INDEX idx_news_source   (source_id),
    INDEX idx_news_priority (priority_tier),
    INDEX idx_news_published(published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. OPERATIONAL LAYERS — sector tagging for the map's toggleable
-- infrastructure layers (Government, Banking, Telecom, Energy,
-- Healthcare, Education, Water, Airports, Border Posts, IXPs,
-- Data Centres, Critical Infrastructure)
-- ============================================================
ALTER TABLE assets
    ADD COLUMN IF NOT EXISTS operational_sector VARCHAR(50) NULL AFTER location_name;

ALTER TABLE assets
    ADD INDEX IF NOT EXISTS idx_assets_sector (operational_sector);

SELECT 'Migration 009 (NCSAM Map + AI Intelligence Layer) applied successfully.' AS result;
