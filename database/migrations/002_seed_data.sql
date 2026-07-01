-- AegisZ Sentinel - Seed Data
-- Insert sample data for UI demonstration.

USE aegisz_db;

-- Seed system settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('app_name', 'AegisZ Sentinel'),
('app_version', '0.1.0'),
('timezone', 'Africa/Lusaka'),
('maintenance_mode', 'false')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Seed fake audit logs for UI display (NO fake threats, only system/demo data)
INSERT INTO audit_logs (level, message, source, ip_address) VALUES
('info', 'Application bootstrapped successfully', 'system', '127.0.0.1'),
('info', 'Database connection established', 'database', '127.0.0.1'),
('info', 'Logger initialized and log file verified', 'logger', '127.0.0.1'),
('info', 'Dashboard page loaded', 'dashboard', '192.168.1.10'),
('info', 'System health check completed', 'health', '127.0.0.1'),
('warning', 'Log file size approaching rotation threshold', 'logger', '127.0.0.1'),
('info', 'Configuration loaded from config.php', 'config', '127.0.0.1'),
('info', 'View renderer initialized', 'view', '127.0.0.1'),
('info', 'Router dispatched request', 'router', '127.0.0.1'),
('info', 'Session handler structure verified', 'session', '127.0.0.1'),
('info', 'CSRF token generated', 'security', '192.168.1.10'),
('info', 'System status page loaded', 'status', '192.168.1.10'),
('info', 'Audit log repository queried', 'repository', '127.0.0.1'),
('info', 'Storage directory permissions verified', 'filesystem', '127.0.0.1'),
('info', 'Error handler registered', 'error', '127.0.0.1');
