-- AegisZ Sentinel - Fix: risk_score_history missing columns (v0.4.0 → v0.5.0)
-- Run in phpMyAdmin with aegisz_db selected.
-- Safe to run multiple times (uses IF NOT EXISTS logic via ALTER IGNORE).

USE aegisz_db;

-- Add missing columns if they don't already exist
ALTER TABLE risk_score_history
    ADD COLUMN IF NOT EXISTS rating          VARCHAR(20)      NOT NULL DEFAULT 'Unknown' AFTER score,
    ADD COLUMN IF NOT EXISTS total_deduction TINYINT UNSIGNED NOT NULL DEFAULT 0          AFTER rating,
    ADD COLUMN IF NOT EXISTS breakdown_json  JSON             NULL                        AFTER total_deduction;

SELECT 'risk_score_history table fixed.' AS result;
