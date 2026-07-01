-- AegisZ Sentinel - Migration 010: Sample Critical Infrastructure Seed (v0.7.0)
-- Seeds a representative set of geo-located Zambian institutions across
-- sectors so the Operational Map has real, meaningful markers on first
-- load — including the leading technology/research universities, per
-- explicit request. These are real, publicly known institutions with
-- approximate city-level coordinates (not survey-grade) — analysts should
-- refine exact coordinates via the "Set location on map" UI per asset.
--
-- This migration is idempotent: it checks for existing names before
-- inserting, so re-running it will not create duplicates.
-- Safe no-op if these institutions are already present as assets.

USE aegisz_db;

-- Helper pattern: INSERT ... SELECT ... WHERE NOT EXISTS, repeated per row,
-- since MySQL has no native "INSERT IF NOT EXISTS" by name.

-- ============================================================
-- EDUCATION SECTOR — leading Zambian universities & research institutions
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'University of Zambia (UNZA)', 'app', 'high', 'active', 'Education', 'Lusaka', 'Lusaka', 'Great East Road Campus', -15.3927, 28.3398, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'University of Zambia (UNZA)');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Copperbelt University (CBU)', 'app', 'high', 'active', 'Education', 'Copperbelt', 'Kitwe', 'Riverside Campus', -12.8084, 28.2189, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Copperbelt University (CBU)');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Mulungushi University', 'app', 'medium', 'active', 'Education', 'Central', 'Kabwe', 'Main Campus', -14.4892, 28.5453, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Mulungushi University');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Information and Communications University (ICU)', 'app', 'high', 'active', 'Education', 'Lusaka', 'Lusaka', 'ICU Campus', -15.4067, 28.2871, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Information and Communications University (ICU)');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Zambia Research and Education Network (ZAMREN)', 'router', 'critical', 'active', 'Education', 'Lusaka', 'Lusaka', 'National REN Backbone', -15.3875, 28.3228, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Zambia Research and Education Network (ZAMREN)');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Cavendish University Zambia', 'app', 'medium', 'active', 'Education', 'Lusaka', 'Lusaka', 'Lusaka Campus', -15.4198, 28.2871, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Cavendish University Zambia');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Mukuba University', 'app', 'medium', 'active', 'Education', 'Copperbelt', 'Kitwe', 'Main Campus', -12.7833, 28.1833, 'education'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Mukuba University');

-- ============================================================
-- GOVERNMENT SECTOR
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Smart Zambia Institute (Data Centre)', 'db', 'critical', 'active', 'Government', 'Lusaka', 'Lusaka', 'Mulungushi House', -15.4112, 28.2820, 'government'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Smart Zambia Institute (Data Centre)');

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Zambia Information and Communications Technology Authority (ZICTA)', 'app', 'critical', 'active', 'Government', 'Lusaka', 'Lusaka', 'ZICTA Headquarters', -15.3982, 28.3228, 'government'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Zambia Information and Communications Technology Authority (ZICTA)');

-- ============================================================
-- BANKING SECTOR
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Bank of Zambia (Core Banking)', 'db', 'critical', 'active', 'Banking', 'Lusaka', 'Lusaka', 'Bank of Zambia HQ', -15.4156, 28.3193, 'banking'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Bank of Zambia (Core Banking)');

-- ============================================================
-- TELECOMMUNICATIONS SECTOR
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'National Internet Exchange Point (ZINX)', 'router', 'critical', 'active', 'Telecommunications', 'Lusaka', 'Lusaka', 'ZINX Peering Facility', -15.3875, 28.3228, 'internet_exchange'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'National Internet Exchange Point (ZINX)');

-- ============================================================
-- ENERGY SECTOR
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'ZESCO National Control Centre', 'app', 'critical', 'active', 'Energy', 'Lusaka', 'Lusaka', 'ZESCO HQ', -15.4067, 28.3105, 'energy'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'ZESCO National Control Centre');

-- ============================================================
-- HEALTHCARE SECTOR
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'University Teaching Hospital (UTH) Systems', 'app', 'critical', 'active', 'Healthcare', 'Lusaka', 'Lusaka', 'UTH Main Campus', -15.4189, 28.3019, 'healthcare'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'University Teaching Hospital (UTH) Systems');

-- ============================================================
-- AIRPORTS
-- ============================================================

INSERT INTO assets (name, asset_type, criticality, status, department, province, district, location_name, latitude, longitude, operational_sector)
SELECT 'Kenneth Kaunda International Airport Systems', 'app', 'critical', 'active', 'Transport', 'Lusaka', 'Lusaka', 'KKIA', -15.3308, 28.4526, 'airports'
WHERE NOT EXISTS (SELECT 1 FROM assets WHERE name = 'Kenneth Kaunda International Airport Systems');

SELECT 'Migration 010 (Sample Critical Infrastructure Seed) applied successfully.' AS result;
