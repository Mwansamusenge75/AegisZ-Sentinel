# AegisZ Sentinel
## Zambia Cyber Situational Awareness & Threat Intelligence Platform (Z-CSATIP)

**Version:** 0.3.0 (Threat Intelligence Ingestion Engine)  
**Status:** Passive Data Ingestion from Real Public Sources  
**Environment:** XAMPP / Apache / MySQL / PHP 8.x

---

### Overview

AegisZ Sentinel is a production-grade cybersecurity platform for Zambia's national cyber defense. Version 0.3.0 introduces a **passive threat intelligence ingestion engine** that pulls real cybersecurity data from 4 public free sources and stores it into the existing cyber object model.

### What's New in v0.4.0

- **4 Ingestion Workers** — CLI scripts that fetch from URLHaus, OTX, NVD, and CISA KEV
- **Feed Service** — Shared orchestration logic (fetch → validate → normalize → deduplicate → store → log)
- **Ingestion Services** — `IOCIngestionService` and `ThreatIngestionService` for data transformation
- **Duplicate Handling** — Prevents database bloat; updates `last_seen` on existing IOCs
- **Status Tracking** — `ingestion_status` table records every worker run for dashboard display
- **Dashboard Update** — "Last Ingestion Status" panel showing worker health and last run times

### Architecture

```
/app
  /Workers              - CLI ingestion scripts (4 workers)
    UrlHausWorker.php
    OTXWorker.php
    NvdWorker.php
    CisaWorker.php
  /Services/Feeds       - Shared ingestion logic
    FeedService.php
    IOCIngestionService.php
    ThreatIngestionService.php
    IngestionStatusService.php
  /Domain               - Cyber object domains (v0.2.0)
    /Asset, /IOC, /Threat, /Alert, /Incident
  /Api                  - REST API placeholders (v0.2.0)
  /Core                 - Framework kernel (v0.1.0)
  /Controllers          - Web controllers
  /Views                - SOC dashboard UI

/config
/database/migrations    - 5 migration files (001-005)
/storage/logs           - Application + worker logs
/public                 - Web root
/routes                 - Route definitions
/docs                   - Documentation + cron setup
```

### Data Sources

| Source | Type | Endpoint | Frequency |
|--------|------|----------|-----------|
| **URLHaus** | Malicious URLs | `urlhaus-api.abuse.ch` | Every 15 min |
| **AlienVault OTX** | IOCs + Threats | `otx.alienvault.com` | Every 30 min |
| **NVD (NIST)** | CVEs | `services.nvd.nist.gov` | Every hour |
| **CISA KEV** | Known Exploited Vulns | `cisa.gov` | Daily at 06:00 |

### Installation

1. **Extract** to `C:/xampp/htdocs/aegisz-sentinel/`
2. **Run all 5 SQL migrations** in phpMyAdmin (in order):
   - `001_initial_schema.sql`
   - `002_seed_data.sql`
   - `003_cyber_data_backbone.sql`
   - `004_cyber_data_seed.sql`
   - `005_ingestion_status.sql`
3. **Configure** `config/config.php` — set DB credentials and `base_url`
4. **Test a worker manually:**
   ```bash
   cd C:/xampp/htdocs/aegisz-sentinel
   php app/Workers/NvdWorker.php
   ```
5. **Set up cron jobs** — see `docs/CRON_SETUP.md`
6. **Visit** `http://localhost/aegisz-sentinel/public/`

### Running Workers

All workers are **CLI-only** and safe to run manually or via cron:

```bash
# Manual test (one-time run)
php app/Workers/UrlHausWorker.php
php app/Workers/OTXWorker.php
php app/Workers/NvdWorker.php
php app/Workers/CisaWorker.php

# Check logs
tail -f storage/logs/app.log
```

### Worker Behavior

Each worker follows this flow:

```
FETCH from public API
   ↓
VALIDATE response (JSON check, retry once on failure)
   ↓
NORMALIZE data (map to IOC/Threat structure)
   ↓
CHECK DUPLICATES (by value for IOCs, by title+source for Threats)
   ↓
STORE in database (insert new or update last_seen)
   ↓
LOG results (worker name, counts, duration, errors)
   ↓
RECORD STATUS in ingestion_status table
```

### Security

- **No API keys** — all endpoints are public and free
- **Input sanitization** — all external data is stripped and validated
- **Prepared statements** — zero SQL injection risk
- **Timeout handling** — workers won't hang (30-60s timeouts)
- **Retry logic** — one retry on transient failures, then graceful exit
- **Error isolation** — one worker failure doesn't affect others

### Database Schema (v0.4.0)

| Table | Purpose |
|-------|---------|
| `system_settings` | App configuration |
| `audit_logs` | Immutable activity trail |
| `assets` | Infrastructure inventory |
| `iocs` | Indicators of Compromise (populated by workers) |
| `threats` | Threat intelligence (populated by workers) |
| `alerts` | Security alerts |
| `incidents` | Security incidents |
| `ingestion_status` | Worker run tracking (new in v0.4.0) |

### Next Version Roadmap (v0.4.0+)

| Priority | Module |
|----------|--------|
| P0 | Authentication & RBAC (JWT) |
| P0 | Alert Engine (trigger alerts from IOC matches) |
| P1 | Incident Management (full lifecycle workflow) |
| P1 | MITRE ATT&CK Mapping |
| P2 | AI Correlation Engine |
| P3 | Zambia Cyber Map (Leaflet.js with real geo data) |

### License

Government of Zambia — Internal Use. All rights reserved.

---

**Built for the defense of Zambia's digital sovereignty.**
