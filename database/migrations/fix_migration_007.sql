-- AegisZ Sentinel - Migration 007 CORRECTED (v0.5.0)
-- Run this if you already ran the original 007_auth_rbac.sql.
-- It removes the broken admin user and re-inserts with a placeholder hash,
-- then the fix_admin_password.php script sets the correct bcrypt hash.

USE aegisz_db;

-- Remove the broken admin user (wrong hash)
DELETE FROM users WHERE username = 'admin';

-- Re-insert with placeholder (fix_admin_password.php will set the real bcrypt hash)
INSERT INTO users (username, email, password_hash, role, status, full_name)
VALUES (
    'admin',
    'admin@aegisz.local',
    'PLACEHOLDER_RUN_FIX_SCRIPT',
    'admin',
    'active',
    'System Administrator'
);

SELECT 'Now run fix_admin_password.php to set the correct password hash.' AS next_step;
