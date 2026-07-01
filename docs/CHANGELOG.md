# AegisZ Sentinel — Changelog

All notable changes to this project are documented here, newest first.

---

## v0.6.0 — SOC Operations & Asset Management Layer

**Focus:** Operations, not AI. Analysts can now manage assets, browse intelligence, and investigate data efficiently.

### Added
- Reusable Search Framework (`SearchQuery`, `Paginator`) used across all new modules
- Asset Management: full CRUD, search, filters, pagination, analyst notes, detail page with linked alerts/incidents/correlations/threats
- IOC Management: manual creation, false-positive flagging, expiry, tags, full history audit trail
- Threat Intelligence Explorer: read-only search by CVE, severity, source, MITRE technique, date range
- Correlation Explorer: read-only search/filter with full linked-record resolution
- Role-aware Dashboard: distinct admin / analyst / viewer layouts
- Timeline and Pagination reusable view components

### Fixed
- `View::render()` now makes `$view` available inside page templates (not just layouts), enabling `$view->partial()` calls from any page

### Database
- Migration 008: `asset_notes`, `ioc_history` tables; new columns on `assets`, `iocs`, `threats`, `correlations`

---

## v0.5.0 — Authentication, RBAC & Analyst Workflow

### Added
- Session-based authentication with bcrypt password hashing (cost 12)
- Role-based access control: `admin > analyst > viewer` hierarchy
- Alert lifecycle workflow: open → acknowledged → assigned → escalated → resolved → closed
- Incident lifecycle workflow: open → investigating → contained → resolved → closed, with analyst notes
- User administration (admin-only): create, edit, delete, password reset
- Audit logging for all auth and workflow events

### Database
- Migration 007: `users`, `alert_workflow_log`, `incident_notes`, `incident_workflow_log`; `assigned_to` added to `alerts` and `incidents`

---

## v0.4.0 — Intelligence Layer

### Added
- Threat Correlation Engine: explainable IOC × Asset × Alert × Incident correlation
- Risk Scoring Engine: transparent 0–100 Security Posture Score with full deduction breakdown
- MITRE ATT&CK Mapping Service: keyword-based technique mapping (10 techniques)
- Intelligence Worker (CLI): runs after ingestion workers, orchestrates mapping → correlation → scoring
- Intelligence Dashboard widgets and dedicated Intelligence page

### Database
- Migration 006: `correlations`, `risk_score_history`, `mitre_mappings`

---

## v0.3.0 — Threat Intelligence Ingestion Engine

### Added
- URLHaus Worker, AlienVault OTX Worker, NVD Worker, CISA KEV Worker
- FeedService, IOC Ingestion Service, Threat Ingestion Service
- Ingestion Status tracking
- CLI Bootstrap for all workers

### Database
- Migration 005: `ingestion_status`

---

## v0.2.0 — Cyber Data Backbone

### Added
- Domain-Driven Design structure: Asset, IOC, Threat, Alert, Incident domains
- Repository pattern implementation for all domains
- REST API placeholder controllers
- Dashboard with cyber object counts

### Database
- Migrations 003–004: core cyber data tables and seed data

---

## v0.1.0 — Core MVC Framework

### Added
- Custom PHP MVC framework (Router, Controller, View, Database, Config, Logger, Security)
- Dark SOC-themed dashboard UI
- Audit logging foundation
- System health/status page

### Database
- Migrations 001–002: initial schema and seed data

---

## v0.7.0 — NCSAM + OpenRouter AI Intelligence Layer

**Focus:** National Cyber Situational Awareness Map as the operational centerpiece; AI-powered advisory intelligence layer.

### Added
- `app/Core/Env.php` — dependency-free `.env` loader; API keys never hardcoded
- OpenRouter AI layer: `OpenRouterClient`, `PromptBuilder`, `OutputValidator`, `IntelligenceAnalysisService` — advisory only, read-only on operational data
- `config/openrouter.php` — model config, cache TTL, fallback model chain
- `AIAssessmentRepository` — DB-backed cache for AI assessments and per-object explanations
- `AIApiController` — `GET /api/ai/assessment`, `GET /api/ai/explain`, `POST /api/ai/refresh`
- Intelligence Bus (`IntelligenceBusService`) — DB-backed event log decoupling workers from consumers
- News Intelligence scaffolding (`NewsSourceInterface`, `NewsArticleEntity`, `NewsIngestionService`) — pipeline wired, zero live providers in v0.7.0
- National Cyber Situational Awareness Map (NCSAM) at `/operations/map`
- 6 JSON Map API endpoints: assets, incidents, alerts, threats, heatmap, province
- Leaflet.js + OpenStreetMap tile rendering — no Google Maps, no paid services
- Toggleable operational layers: Government, Banking, Telecom, Energy, Healthcare, Education, Water, Airports, Border Posts, IXPs, Data Centres, Critical Infrastructure
- Province Intelligence Panel and National Overview Panel
- Threat Density Heatmap (Leaflet.heat) built from alerts + incidents + correlations
- Animated threat path capability (renders when real origin coordinates exist — never fabricated)
- Asset geo-location fields: latitude, longitude, province, district, location_name, operational_sector
- "Set Location on Map" UI on asset detail page (manual pin, no paid geocoding)
- AI Explanation panel on asset detail page
- AI assessment widget on all three role-aware dashboards
- NCSAM quick-launch on all dashboards
- "Operational Map" as primary nav item in sidebar
- Migration 010: Sample critical infrastructure seed including 7 Zambian universities/research institutions (UNZA, CBU, Mulungushi, ICU, ZAMREN, Cavendish, Mukuba)

### Fixed
- `RoleMiddleware::deny()` now produces a specific, actionable message identifying the required role and clarifying that viewing is still available (addresses "Assets says I don't have permission" report)

### Database
- Migration 009: `ai_assessments`, `ai_explanations`, `intelligence_bus_events`, `news_articles`; geo columns on `assets`; origin geo columns on `threats`
- Migration 010: 14 sample geo-located critical infrastructure assets (idempotent seed)
