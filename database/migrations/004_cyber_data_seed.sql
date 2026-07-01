-- AegisZ Sentinel - Migration 004: Cyber Data Seed (v0.2.0)
-- Safe structural examples only. NO real attack scenarios. NO malware claims.

USE aegisz_db;

-- ============================================================
-- ASSETS (3 Zambia-based examples)
-- ============================================================
INSERT INTO assets (name, hostname, ip_address, asset_type, department, owner, location, criticality, status) VALUES
('Lusaka Core Router', 'core-rtr-lus-01', '10.0.1.1', 'router', 'Network Operations', 'NetOps Team', 'Lusaka', 'critical', 'active'),
('Bank of Zambia DB Server', 'boz-db-prod-01', '10.0.5.20', 'db', 'IT Infrastructure', 'Database Team', 'Lusaka', 'critical', 'active'),
('Zambia ISP Edge Node', 'isp-edge-ndola-01', '10.0.10.5', 'server', 'External Services', 'ISP Relations', 'Ndola', 'high', 'active');

-- ============================================================
-- IOCS (3 generic harmless values for structural testing)
-- ============================================================
INSERT INTO iocs (type, value, source, confidence_score, first_seen, last_seen, raw_data) VALUES
('ip', '192.0.2.100', 'urlhaus', 45, '2026-06-20 08:00:00', '2026-06-26 14:30:00', '{"asn": 12345, "country": "XX"}'),
('domain', 'example-suspicious.test', 'otx', 30, '2026-06-15 10:00:00', '2026-06-26 16:00:00', '{"registrar": "test-registrar", "age_days": 12}'),
('hash', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 'nvd', 60, '2026-06-10 12:00:00', '2026-06-26 09:00:00', '{"hash_type": "sha256", "file_type": "unknown"}');

-- ============================================================
-- THREATS (2 descriptive-only entries, no fake attack claims)
-- ============================================================
INSERT INTO threats (title, description, source_feed, severity, mitre_technique, raw_data) VALUES
('Phishing Campaign Targeting Financial Sector', 'Observed increase in phishing emails impersonating banking institutions. No confirmed compromises. Monitoring active.', 'gdelt', 'high', 'T1566.001', '{"region": "southern_africa", "sector": "finance"}'),
('Outdated SSL/TLS Configuration Detected', 'Multiple government portals running deprecated TLS 1.0. Recommended upgrade to TLS 1.3.', 'nvd', 'medium', 'T1557', '{"cvss_score": 5.3, "cwe": "CWE-319"}');

-- ============================================================
-- ALERTS (1 open alert, structural example)
-- ============================================================
INSERT INTO alerts (title, severity, status, linked_ioc_id, linked_asset_id) VALUES
('Suspicious Domain Detected on ISP Node', 'high', 'open', 2, 3);

-- ============================================================
-- INCIDENTS (1 open incident, structural example)
-- ============================================================
INSERT INTO incidents (title, status, severity, linked_alert_id, linked_asset_id, timeline) VALUES
('Investigation: Suspicious Domain Activity', 'open', 'high', 1, 3, '[{"event": "alert_created", "time": "2026-06-26 10:00:00"}, {"event": "incident_opened", "time": "2026-06-26 10:15:00"}]');
