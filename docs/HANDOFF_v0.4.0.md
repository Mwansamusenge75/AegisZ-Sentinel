# AegisZ Sentinel v0.4.0 — Development Handoff Report

**Version:** v0.4.0 — Intelligence Platform
**Build Date:** 2026-06-27
**Previous Version:** v0.3.0 — Threat Intelligence Ingestion Engine
**Status:** Complete. Backward compatible with all v0.3.0 functionality.

---

## What Was Built

### 1. Threat Correlation Engine

**File:** `app/Services/Correlation/ThreatCorrelationService.php`

Correlates data across the full domain model:

| Correlation Type | Logic |
|-----------------|-------|
| `ioc_asset` | IOC IP/domain value matched against active asset IP addresses and hostnames |
| `ioc_alert` | High-confidence IOC (≥70%) linked via `alerts.linked_ioc_id` to an open alert |
| `threat_incident` | Critical/high-severity threat severity-matches an open/investigating incident |

Every correlation produces an `explanation` field in plain English.
No black-box output. No automatic alert generation.
Deduplication by daily `correlation_hash` prevents duplicate records.

**Methods:**
- `correlate()` → run all correlation passes, return array
- `persist(array $correlations)` → store new records, skip duplicates
- `getRecent(int $limit)` → fetch for dashboard
- `countBySeverity()` → severity distribution for dashboard badge

---

### 2. Risk Scoring Engine

**File:** `app/Services/Scoring/RiskScoringService.php`

Transparent Security Posture Score (0–100) with a full deduction breakdown.

**Scoring factors:**

| Factor | Max Deduction | Data Source |
|--------|--------------|-------------|
| Critical threats (last 7 days) | -20 | `threats` table |
| Critical CVEs (NVD/CISA, last 30 days) | -15 | `threats` table |
| Open incidents (weighted by severity) | -20 | `incidents` table |
| High-confidence IOCs (≥80%) | -15 | `iocs` table |
| Critical assets in active correlations | -20 | `correlations` + `assets` |
| Open critical alerts | -10 | `alerts` table |

**Rating thresholds:** Excellent (90+) | Good (75–89) | Fair (60–74) | At Risk (40–59) | Critical (<40)

**Methods:**
- `calculate()` → returns `['score', 'rating', 'deduction', 'breakdown']`
- `persist(array $result)` → stores snapshot in `risk_score_history`
- `getLatest()` → most recent snapshot (with breakdown array)
- `getHistory(int $limit)` → score trend for history panel

---

### 3. MITRE ATT&CK Mapping Service

**File:** `app/Services/ThreatIntel/MitreMappingService.php`

Rule-based keyword matching. No external APIs. No inference.

**Supported techniques (v0.4.0):**

| ID | Name | Tactic |
|----|------|--------|
| T1566 | Phishing | Initial Access |
| T1059 | Command and Scripting Interpreter | Execution |
| T1071 | Application Layer Protocol | Command and Control |
| T1041 | Exfiltration Over C2 Channel | Exfiltration |
| T1105 | Ingress Tool Transfer | Command and Control |
| T1190 | Exploit Public-Facing Application | Initial Access |
| T1486 | Data Encrypted for Impact | Impact |
| T1110 | Brute Force | Credential Access |
| T1078 | Valid Accounts | Defense Evasion |
| T1133 | External Remote Services | Initial Access |

**Methods:**
- `mapThreats()` → maps all unmapped threats, writes back to `threats.mitre_technique`
- `matchTechnique(string $title, string $description)` → returns technique ID or null
- `getTechniqueDistribution()` → count per technique for dashboard bar chart
- `getTechniqueRegistry()` → full registry for reference display
- `resolveId(string $id)` → get name/tactic for a technique ID

---

### 4. Intelligence Worker (CLI)

**File:** `cli/intelligence_worker.php`

Run after all ingestion workers:

```
php cli/intelligence_worker.php
```

**Pipeline position:**
```
UrlHausWorker → OTXWorker → NvdWorker → CisaWorker → IntelligenceWorker
```

**Stages:**
1. MITRE mapping (maps unmapped threats)
2. Correlation (correlates IOCs × assets × alerts × incidents)
3. Risk scoring (calculates and persists posture score)

HTTP guard: `php_sapi_name() === 'cli'` enforced. Worker does not load the router.

---

### 5. Intelligence Controller + Page

**Files:**
- `app/Controllers/IntelligenceController.php`
- `app/Views/pages/intelligence/index.php`
- Route: `/intelligence` (added to `routes/web.php`)

**Page sections:**
- Security Posture Score with full deduction breakdown
- Score history trend (last 14 runs)
- Threat correlations list (most recent 25, with explanations)
- MITRE ATT&CK technique distribution bar chart
- MITRE technique registry reference table

---

### 6. Updated Dashboard

**File:** `app/Views/pages/dashboard/index.php`
**File:** `app/Controllers/DashboardController.php`

New intelligence widgets added above the existing ingestion panel:

- **Security Posture Score** — score, rating, mini score bar, deduction list
- **MITRE ATT&CK Distribution** — horizontal bar chart of technique counts
- **Recent Threat Correlations** — table: type, IOC value, asset, source, confidence, severity, explanation excerpt

---

### 7. Database Migration

**File:** `database/migrations/006_intelligence_layer.sql`

New tables:

| Table | Purpose |
|-------|---------|
| `correlations` | Explainable correlation records with daily deduplication |
| `risk_score_history` | Score snapshots for trend display |
| `mitre_mappings` | ATT&CK mapping audit with match method recorded |

Also inserts `IntelligenceWorker` row into `ingestion_status` for tracking.

---

## Migration Instructions

Run in phpMyAdmin with `aegisz_db` selected, or via MySQL CLI:

```sql
source database/migrations/006_intelligence_layer.sql;
```

Then run the Intelligence Worker once to populate all tables:

```bash
php cli/intelligence_worker.php
```

---

## Files Changed / Added

### New Files

| File | Type |
|------|------|
| `app/Services/Correlation/ThreatCorrelationService.php` | Service |
| `app/Services/Scoring/RiskScoringService.php` | Service |
| `app/Services/ThreatIntel/MitreMappingService.php` | Service |
| `app/Controllers/IntelligenceController.php` | Controller |
| `app/Views/pages/intelligence/index.php` | View |
| `cli/intelligence_worker.php` | CLI Worker |
| `database/migrations/006_intelligence_layer.sql` | Migration |
| `docs/HANDOFF_v0.4.0.md` | This document |

### Modified Files

| File | Change |
|------|--------|
| `app/Controllers/DashboardController.php` | Added v0.4.0 intelligence data |
| `app/Views/pages/dashboard/index.php` | Added intelligence widgets |
| `app/Views/components/sidebar.php` | Added Intelligence nav section |
| `routes/web.php` | Added `/intelligence` route |
| `docs/ARCHITECTURE.md` | Updated for v0.4.0 |

### Unchanged Files (All v0.3.0 functionality preserved)

All Workers, Feed Services, Domain entities/repositories/services,
Core framework classes, existing migrations, CSS, JS — untouched.

---

## Architecture Compliance

- ✅ No SQL inside Controllers
- ✅ No Business Logic inside Views
- ✅ Services contain only business logic
- ✅ Workers are CLI-only with HTTP guard
- ✅ Prepared statements only
- ✅ No Composer / No frameworks
- ✅ Shared hosting compatible
- ✅ Zero paid dependencies
- ✅ Backward compatible with v0.3.0

---

## What Is NOT in v0.4.0 (Deferred)

- Authentication / RBAC
- Auto-generated alerts from correlations
- AI/ML-based threat analysis
- Notification system (email, SMS)
- Executive reports
- Incident workflow management
- Real-time (WebSocket) updates

---

## Next Version Suggestions (v0.5.0)

- Authentication layer (session-based, no JWT complexity needed for v1)
- Alert auto-generation from high-severity correlations (with analyst review gate)
- Expanded MITRE technique set (20-30 techniques)
- API endpoints for correlations and risk score (`/api/correlations`, `/api/score`)
- CRON schedule documentation update for IntelligenceWorker
