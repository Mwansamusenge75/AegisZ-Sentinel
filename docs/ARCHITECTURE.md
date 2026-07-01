# AegisZ Sentinel — Architecture (v0.4.0)

## Overview

AegisZ Sentinel is a pure PHP, zero-dependency, shared-hosting-compatible
Cyber Threat Intelligence platform built for the Zambia SOC context.

## Version History

| Version | Focus |
|---------|-------|
| v0.1.0  | Core MVC framework, audit logging, dark SOC UI |
| v0.2.0  | Cyber domain model (Assets, IOCs, Threats, Alerts, Incidents), REST API placeholders |
| v0.3.0  | Threat Intelligence Ingestion Engine (URLHaus, OTX, NVD, CISA), CLI workers |
| v0.4.0  | Intelligence Layer — Correlation, Risk Scoring, MITRE ATT&CK Mapping |

---

## Layer Responsibilities (Strict)

| Layer | Responsibility | May NOT |
|-------|---------------|---------|
| Controllers | HTTP request/response only | Contain SQL, business logic |
| Repositories | Database queries only | Contain business logic |
| Services | Business logic only | Contain SQL, HTTP code |
| Workers | CLI orchestration only | Load the HTTP router |
| Views | Presentation only | Contain business logic |

---

## Directory Structure (v0.4.0)

```
app/
  Controllers/
    DashboardController.php       — updated v0.4.0 (intelligence data)
    IntelligenceController.php    — NEW v0.4.0
    LogController.php
    StatusController.php
    BaseController.php
    Api/
      AlertApiController.php
      AssetApiController.php
      IOCApiController.php
      IncidentApiController.php
      ThreatApiController.php
      ApiBaseController.php

  Services/
    Correlation/
      ThreatCorrelationService.php   — NEW v0.4.0
    Scoring/
      RiskScoringService.php         — NEW v0.4.0
    ThreatIntel/
      MitreMappingService.php        — NEW v0.4.0
    Feeds/
      FeedService.php
      IOCIngestionService.php
      ThreatIngestionService.php
      IngestionStatusService.php

  Domain/
    Alert/    — AlertEntity, AlertRepository, AlertService
    Asset/    — AssetEntity, AssetRepository, AssetService
    IOC/      — IOCEntity, IOCRepository, IOCService
    Incident/ — IncidentEntity, IncidentRepository, IncidentService
    Threat/   — ThreatEntity, ThreatRepository, ThreatService

  Workers/                  — HTTP-free CLI workers
    CisaWorker.php
    NvdWorker.php
    OTXWorker.php
    UrlHausWorker.php

  Views/pages/
    dashboard/index.php     — updated v0.4.0 (intelligence widgets)
    intelligence/index.php  — NEW v0.4.0
    logs/index.php
    status/index.php
    errors/404.php

cli/
  intelligence_worker.php   — NEW v0.4.0

database/migrations/
  006_intelligence_layer.sql   — NEW v0.4.0

routes/
  web.php                   — updated v0.4.0 (/intelligence route)
```

---

## Intelligence Layer (v0.4.0)

### ThreatCorrelationService

Correlates data across all domains without auto-generating alerts.

**Correlation types:**

| Type | Logic |
|------|-------|
| `ioc_asset` | IOC IP/domain matches an active asset's ip_address or hostname |
| `ioc_alert` | High-confidence IOC (≥70%) linked to an open alert |
| `threat_incident` | Critical/high threat severity-matches an open incident |

Every correlation record stores a human-readable `explanation` field.
No black-box output. Deduplicated daily by `correlation_hash`.

### RiskScoringService

Produces a 0–100 Security Posture Score with fully transparent deductions.

| Factor | Max Deduction |
|--------|--------------|
| Critical threats (7 days) | -20 |
| Critical CVEs (30 days) | -15 |
| Open incidents | -20 |
| High-confidence IOCs | -15 |
| Critical assets in correlations | -20 |
| Open critical alerts | -10 |

Score snapshots stored in `risk_score_history` for trend display.

### MitreMappingService

Maps threat records to MITRE ATT&CK techniques using keyword rules.
No external API calls. No ML inference.

**Supported techniques (v0.4.0):**
T1566, T1059, T1071, T1041, T1105, T1190, T1486, T1110, T1078, T1133

Mappings are written back to `threats.mitre_technique` and audited
in the `mitre_mappings` table.

### IntelligenceWorker (CLI)

```
php cli/intelligence_worker.php
```

Stage 1: MITRE mapping → Stage 2: Correlation → Stage 3: Risk Scoring

Must run AFTER all ingestion workers. Never load the HTTP router.

---

## Database Schema (v0.4.0 additions)

| Table | Purpose |
|-------|---------|
| `correlations` | Explainable correlation records |
| `risk_score_history` | Posture score snapshots |
| `mitre_mappings` | ATT&CK mapping audit log |

---

## Security Principles

- Prepared statements only — no string-interpolated SQL
- XSS: all output via `Security::e()`
- Workers: CLI-only guard (`php_sapi_name() === 'cli'`)
- No composer, no external runtime dependencies
- PHP 8.0+ (pure typed properties, match removed in views for 7.x compat)

---

## Authentication & RBAC (v0.5.0)

### Session
`app/Core/Session.php` — PHP native sessions, httponly/SameSite cookies, 1hr timeout, ID regeneration on login.

### Middleware
- `AuthMiddleware` — called from `BaseController::__construct()`. Redirects unauthenticated users to `/login`.
- `RoleMiddleware` — enforces hierarchy `admin > analyst > viewer`. Call `requireRole()` in controller methods.

### Role Access Matrix

| Route | Viewer | Analyst | Admin |
|-------|--------|---------|-------|
| Dashboard | ✅ | ✅ | ✅ |
| Intelligence | ✅ | ✅ | ✅ |
| Alert Queue (view) | ✅ | ✅ | ✅ |
| Alert transitions | ❌ | ✅ | ✅ |
| Incidents (view) | ✅ | ✅ | ✅ |
| Incident transitions + notes | ❌ | ✅ | ✅ |
| User Management | ❌ | ❌ | ✅ |

### Login flow
`GET /login` → form → `POST /login` → `UserService::authenticate()` → `Session::setUser()` → redirect

### Logout flow
`POST /logout` (CSRF) → `Session::destroy()` → redirect to `/login`

### PublicBaseController
Used only by `AuthController`. Does NOT call `AuthMiddleware`. Extends this for any future public-facing route.

---

## SOC Operations Layer (v0.6.0)

### Search Framework
`app/Core/SearchQuery.php` + `app/Core/Paginator.php` — reusable value objects. Every search-capable repository implements `search(SearchQuery $q): array` returning `['data' => [...], 'paginator' => Paginator]`.

### New Modules

| Module | Read/Write | Routes |
|--------|-----------|--------|
| Assets | Full CRUD (analyst+) | `/assets`, `/assets/detail`, `/assets/create`, `/assets/edit` |
| IOCs | Full CRUD (analyst+) | `/iocs`, `/iocs/detail`, `/iocs/create`, `/iocs/edit`, `/iocs/flag` |
| Threats | Read-only (worker-generated) | `/threats`, `/threats/detail` |
| Correlations | Read-only (worker-generated) | `/correlations`, `/correlations/detail` |

### Role-Aware Dashboard
`DashboardController::index()` routes via `match($role)` to `dashboard/admin`, `dashboard/analyst`, or `dashboard/viewer`. Analyst dashboard falls back to all-open items when the analyst has no assignments.

### Reusable Components
`app/Views/components/timeline.php`, `app/Views/components/pagination.php` — called via `$view->partial(name, data)` from any page template.

---

## AI Intelligence Layer (v0.7.0)

### Security Boundary
`IntelligenceAnalysisService` and `OpenRouterClient` have zero imports from any
class that can write to operational data. They cannot create, close, modify, or
delete Alerts, Incidents, Correlations, Threats, or Risk Scores. This is
architectural — not policy.

### Env Loader
`app/Core/Env.php` provides `Env::load()` and `Env::get()`. Both `public/index.php`
and `cli_bootstrap.php` call `Env::load()` before any config file is required.
API keys live only in `.env` (gitignored).

### Assessment Caching
`ai_assessments` table — one row per Intelligence Worker run (or TTL expiry).
`ai_explanations` table — one row per object type+id (permanent per object,
since ingested records don't change after storage).

### Intelligence Bus
`intelligence_bus_events` table. Producers call `publish(eventType, payload)`.
Consumers call `getUnprocessed(?type)` and `markProcessed(id)`. The bus is
polled (not pushed) — appropriate for shared hosting with cron workers and no
persistent daemons.

### News Scaffolding Pipeline (future)
`NewsSourceInterface` → `NewsIngestionService::registerSource()` → `run()` →
normalize → store in `news_articles` → `IntelligenceBusService::publish(EVENT_NEWS_INGESTED)` → future AI analysis pass.

---

## NCSAM — National Cyber Situational Awareness Map (v0.7.0)

### Stack
Leaflet.js 1.9.4 + OpenStreetMap + Leaflet.markercluster + Leaflet.heat.
No Google Maps. No paid mapping or geocoding services.

### Data pipeline
`MapApiController` → `MapService` → domain `Repository::findAllWithLocation()` →
JSON response → `ncsam-map.js` → Leaflet layer update.

All 6 Map API endpoints are auth-enforced (extend `BaseController`). The
existing unauthenticated `App\Api\*` placeholder controllers (v0.2.0) are
unchanged.

### Province boundary data
Province centroids and major city coordinates stored in
`public/assets/data/zambia-provinces.json`. A `FeatureCollection` GeoJSON
(polygon boundaries) can be dropped into this file path in a future release
and the map will render province outlines without any code change.

### Threat origin paths
`threats.origin_lat` and `threats.origin_lng` (nullable). Animated polylines
only render where these are populated. A future geolocation enrichment worker
populates them; the rendering is already built in `ncsam-map.js`.

### Layer toggle pattern
All layers are `L.layerGroup()` or `L.markerClusterGroup()` instances.
Toggles call `map.addLayer()`/`map.removeLayer()`. Heatmap and threat origin
layers are loaded lazily on first enable.
