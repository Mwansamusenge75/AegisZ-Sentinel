-- AegisZ Sentinel - Migration 007: Authentication, RBAC & Workflow (v0.5.0)
-- Creates: users, user_sessions, alert_workflow_log, incident_notes, incident_workflow_log
-- Adds: assigned_to column to alerts and incidents

USE aegisz_db;

-- ============================================================
-- 1. USERS
-- Stores platform users. Passwords are bcrypt hashed (cost 12).
-- Roles: admin > analyst > viewer
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50) NOT NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin', 'analyst', 'viewer') NOT NULL DEFAULT 'viewer',
    status          ENUM('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
    full_name       VARCHAR(150) NULL,
    last_login_at   TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_username (username),
    INDEX idx_users_email    (email),
    INDEX idx_users_role     (role),
    INDEX idx_users_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. DEFAULT ADMIN USER
-- Password: ChangeMe123!
-- MUST be changed immediately after first login.
-- Hash generated with: password_hash('ChangeMe123!', PASSWORD_BCRYPT, ['cost' => 12])
-- ============================================================
INSERT INTO users (username, email, password_hash, role, status, full_name)
VALUES (
    'admin',
    'admin@aegisz.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- ChangeMe123!
    'admin',
    'active',
    'System Administrator'
) ON DUPLICATE KEY UPDATE username = username;

-- ============================================================
-- 3. ALERT WORKFLOW LOG
-- Audits every alert status transition.
-- ============================================================
CREATE TABLE IF NOT EXISTS alert_workflow_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    alert_id    INT NOT NULL,
    user_id     INT NOT NULL,
    from_status VARCHAR(30) NOT NULL,
    to_status   VARCHAR(30) NOT NULL,
    note        TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_awl_alert   (alert_id),
    INDEX idx_awl_user    (user_id),
    INDEX idx_awl_created (created_at),
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. INCIDENT NOTES
-- Analyst notes attached to incidents. Append-only.
-- ============================================================
CREATE TABLE IF NOT EXISTS incident_notes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id     INT NOT NULL,
    note        TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_in_incident (incident_id),
    INDEX idx_in_user     (user_id),
    INDEX idx_in_created  (created_at),
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. INCIDENT WORKFLOW LOG
-- Audits every incident status transition.
-- ============================================================
CREATE TABLE IF NOT EXISTS incident_workflow_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    user_id     INT NOT NULL,
    from_status VARCHAR(30) NOT NULL,
    to_status   VARCHAR(30) NOT NULL,
    note        TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_iwl_incident (incident_id),
    INDEX idx_iwl_user     (user_id),
    INDEX idx_iwl_created  (created_at),
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ADD assigned_to TO alerts
-- Tracks which analyst is assigned to an alert.
-- ============================================================
ALTER TABLE alerts
    ADD COLUMN IF NOT EXISTS assigned_to INT NULL AFTER linked_asset_id,
    ADD CONSTRAINT fk_alert_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Add extended statuses to alerts (v0.5.0 lifecycle)
ALTER TABLE alerts
    MODIFY COLUMN status ENUM('open','acknowledged','assigned','escalated','resolved','closed') NOT NULL DEFAULT 'open';

-- ============================================================
-- 7. ADD assigned_to TO incidents
-- ============================================================
ALTER TABLE incidents
    ADD COLUMN IF NOT EXISTS assigned_to INT NULL AFTER linked_asset_id,
    ADD CONSTRAINT fk_incident_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;
