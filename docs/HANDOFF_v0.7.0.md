# AegisZ Sentinel v0.7.0 — Development Handoff Report

**Version:** v0.7.0 — NCSAM + OpenRouter AI Intelligence Layer
**Build Date:** 2026-07-01
**Previous Version:** v0.6.0 — SOC Operations & Asset Management Layer
**Status:** Complete. Fully backward compatible with v0.1.0–v0.6.0.

---

## Security Notice — API Key

The OpenRouter API key provided during this session was pasted into a chat
conversation, which is insecure. **Rotate the key immediately:**

1. Go to https://openrouter.ai/keys
2. Delete the key ending in `e0c5`
3. Generate a new key
4. Put the new key only into your `.env` file (gitignored, never committed)

The `.env` file in the project root is gitignored and excluded from the
deliverable zip. See `.env.example` for the required format.

---

## Assets Permission Bug — Fixed

Reported issue: "Assets says I don't have permission."

**Root cause:** `RoleMiddleware::deny()` produced a generic "you don't have
permission" message that made it appear the entire Assets module was locked,
when in fact only the create/edit/delete/note actions require `analyst` role.
Viewing the list and detail pages is correctly open to all authenticated roles
and was never actually blocked.

**Fix:** The denial message now explicitly names the required role and clarifies
that viewing is still available. The underlying permission model was already
correct — no route or controller logic was changed.

---

## What Was Built

### 1. Environment Variable Loader (`app/Core/Env.php`)
Minimal, dependency-free `.env` file parser. Loaded early in both
`public/index.php` and `cli_bootstrap.php`. All secrets (API keys) are read
from `.env`, never hardcoded in PHP files that could be committed to VCS.

### 2. OpenRouter AI Intelligence Layer

| File | Purpose |
|------|---------|
| `config/openrouter.php` | Configuration — model, temperature, cache TTL, fallback models. All values read from env. |
| `app/Services/AI/OpenRouterClient.php` | Thin cURL client. Logs metadata to `storage/logs/ai.log` — never logs keys, prompts, or responses. Supports primary + fallback model chain. |
| `app/Services/AI/PromptBuilder.php` | Builds system and user prompts. Enforces Zambia-context framing and JSON-only response instructions. |
| `app/Services/AI/OutputValidator.php` | Validates and sanitises all AI output before it reaches the application. Tolerates markdown-fenced JSON. Returns `null` on invalid output — callers degrade gracefully. |
| `app/Services/AI/IntelligenceAnalysisService.php` | Orchestrator. Read-only on operational data — only calls `find*()`/`get*()`/`count()` methods. Never imports any write method from Alert, Incident, Correlation, or RiskScore classes. |
| `app/Repositories/AIAssessmentRepository.php` | SQL for `ai_assessments` and `ai_explanations` cache tables only. |
| `app/Controllers/Api/AIApiController.php` | `GET /api/ai/assessment`, `GET /api/ai/explain?type=X&id=N`, `POST /api/ai/refresh` (analyst+ only). |

**Default model:** `meta-llama/llama-3.3-70b-instruct:free` (a capable free-tier
model on OpenRouter). Override via `OPENROUTER_MODEL` in `.env`. Fallback chain:
`google/gemini-2.0-flash-exp:free` → `qwen/qwen-2.5-72b-instruct:free`.

**Assessment cache:** 20 minutes (DB-backed). Also force-refreshed at the end of
each Intelligence Worker run (Stage 4, added to `cli/intelligence_worker.php`).

**AI cannot:** create alerts, close incidents, modify risk scores, update threat
intelligence, or delete any record. This is enforced architecturally — the AI
service has no import of any write method from any operational domain class.

### 3. Intelligence Bus (`app/Services/IntelligenceBus/IntelligenceBusService.php`)
Database-backed event log (`intelligence_bus_events` table). Shared-hosting
compatible — no persistent daemon, no message broker. Workers publish events
(`publish()`); consumers read unprocessed events (`getUnprocessed()`). The
Intelligence Worker now publishes 4 event types at the end of each run:
`mitre_mapped`, `correlation_generated`, `risk_score_updated`,
`intelligence_worker_complete`. Future modules (news, external feeds) plug into
this bus without touching existing worker code.

### 4. News Intelligence Scaffolding

| File | Purpose |
|------|---------|
| `app/Domain/News/NewsSourceInterface.php` | Contract for future provider implementations (ZNBC, Reuters, etc.) |
| `app/Domain/News/NewsArticleEntity.php` | Normalised article value object |
| `app/Services/News/NewsIngestionService.php` | Pipeline stub: register-source → fetch → normalize → store → bus event. Zero providers registered in v0.7.0 — `run()` is a safe no-op. |

**Planned providers (future v0.8.0+):**
- Zambia (priority): ZNBC, Diamond TV, Prime TV Zambia, Muvi TV, Phoenix News,
  News Diggers, Zambia Daily Mail, Times of Zambia
- International: CNN, BBC, Reuters, Bloomberg, Al Jazeera

### 5. National Cyber Situational Awareness Map (NCSAM)

**Route:** `GET /operations/map`

| File | Purpose |
|------|---------|
| `app/Services/Map/MapService.php` | All map business logic — asset markers, incident/alert overlays, heatmap points, province intelligence, national overview |
| `app/Controllers/OperationsController.php` | Page controller |
| `app/Controllers/Api/MapApiController.php` | 6 JSON API endpoints |
| `app/Views/pages/operations/map.php` | Map page — Leaflet.js + controls |
| `public/assets/js/ncsam-map.js` | Map JS: layer management, popups, detail panels, filter wiring |
| `public/assets/data/zambia-provinces.json` | Province centroid coordinates + major cities (factual, not fabricated boundary polygons) |

**Map layers (all toggleable):**
- Assets (clustered, colored by criticality)
- Incidents (pulsing red for active critical)
- Active Alerts
- Threat Density Heatmap (Leaflet.heat, built from alerts + incidents + correlations)
- Threat Origins (renders only where real `origin_lat`/`origin_lng` data exists — no fabricated paths)

**Operational Sector layers:** Government, Banking, Telecommunications, Energy,
Healthcare, Education, Water Utilities, Airports, Border Posts, Internet Exchange
Points, Data Centres, Critical Infrastructure.

**Clicking a province marker:** opens the Province Intelligence Panel (asset
count, critical assets, open alerts, open incidents, province risk score).

**Clicking an asset marker:** loads the full asset detail panel including linked
threats, open alerts, open incidents, latest IOC, and a direct link to the asset
detail page.

**Performance:** Marker clustering via Leaflet.markercluster. Map data loaded
lazily via separate API endpoints (not inline on page render). Heatmap and
threat origin layers are off by default and only fetched when enabled.

**Note on geographic boundaries:** Province boundary polygon GeoJSON was not
included because no reliable public-domain source was accessible at build time,
and fabricating boundary coordinates would be dishonest. The map uses accurate
province capital centroid coordinates for province markers. A real boundary
GeoJSON (e.g. from GADM, Natural Earth, or HDX) can be dropped into
`public/assets/data/zambia-provinces.json` as a `FeatureCollection` — the map
loader code already checks `type === 'FeatureCollection'` and will render
boundaries automatically without any code change.

**Animated threat paths:** Built on top of nullable `origin_lat`/`origin_lng`
fields added to the `threats` table. The JS renderer only draws polylines where
these fields are populated — it never invents origin coordinates. A future
geolocation enrichment worker can populate these fields and animated paths will
appear automatically.

### 6. Asset Extensions (v0.7.0)

New fields on `AssetEntity` and `AssetRepository`:
- `latitude`, `longitude`, `province`, `district`, `location_name`, `operational_sector`
- `setLocation()` repository method — updates geo fields without touching other asset data
- `findAllWithLocation()` repository method — returns only geo-located assets for map rendering
- `hasGeoLocation()` entity helper

**Set Location UI:** Added to asset detail page. Manual coordinate entry (no
paid geocoding API). Analyst role required. Accepts lat/lng with Zambia
province/district dropdown.

**AI Explanation button:** Added to asset detail page. Calls
`GET /api/ai/explain?type=asset&id=N`. Renders inline if AI is configured,
shows "not configured" gracefully if not.

### 7. Sample Seed — Critical Infrastructure (Migration 010)

Seeds geo-located asset records for representative Zambian institutions so the
map has real, meaningful markers immediately after first install.

**Universities and research institutions (explicit requirement):**
- University of Zambia (UNZA) — Lusaka, -15.3927, 28.3398
- Copperbelt University (CBU) — Kitwe, -12.8084, 28.2189
- Mulungushi University — Kabwe, -14.4892, 28.5453
- Information and Communications University (ICU) — Lusaka, -15.4067, 28.2871
- ZAMREN (Zambia Research and Education Network) — Lusaka, -15.3875, 28.3228
- Cavendish University Zambia — Lusaka
- Mukuba University — Kitwe

**Other sectors:** Bank of Zambia, ZICTA, Smart Zambia Institute, ZINX (internet
exchange), ZESCO National Control Centre, UTH, Kenneth Kaunda International Airport.

All coordinates are city-level approximations — analysts should refine via
"Set Map Location" on each asset detail page.

---

## Migration Instructions

Run these in order in phpMyAdmin with `aegisz_db` selected:

```sql
source database/migrations/009_ncsam_ai_layer.sql;
source database/migrations/010_seed_critical_infrastructure.sql;
```

Migration 009 is idempotent (safe to re-run). Migration 010 uses
`WHERE NOT EXISTS` guards — also safe to re-run without creating duplicates.

---

## Environment Configuration

Create a `.env` file in the project root (same folder as `public/`):

```env
OPENROUTER_API_KEY=your-new-rotated-key-here
OPENROUTER_MODEL=
OPENROUTER_BASE_URL=
```

Leave `OPENROUTER_MODEL` blank to use the default free-tier model. The platform
runs fully without an API key — AI widgets display "not configured" gracefully.

---

## Files Created (17 new)

| File | Type |
|------|------|
| `app/Core/Env.php` | Core |
| `.env.example` | Config template |
| `.gitignore` | VCS |
| `config/openrouter.php` | Config |
| `app/Services/AI/OpenRouterClient.php` | Service |
| `app/Services/AI/PromptBuilder.php` | Service |
| `app/Services/AI/OutputValidator.php` | Service |
| `app/Services/AI/IntelligenceAnalysisService.php` | Service |
| `app/Repositories/AIAssessmentRepository.php` | Repository |
| `app/Controllers/Api/AIApiController.php` | Controller |
| `app/Controllers/Api/MapApiController.php` | Controller |
| `app/Controllers/OperationsController.php` | Controller |
| `app/Services/Map/MapService.php` | Service |
| `app/Services/IntelligenceBus/IntelligenceBusService.php` | Service |
| `app/Domain/News/NewsSourceInterface.php` | Domain |
| `app/Domain/News/NewsArticleEntity.php` | Domain |
| `app/Services/News/NewsIngestionService.php` | Service |
| `app/Views/pages/operations/map.php` | View |
| `public/assets/js/ncsam-map.js` | Frontend |
| `public/assets/data/zambia-provinces.json` | Data |
| `database/migrations/009_ncsam_ai_layer.sql` | Migration |
| `database/migrations/010_seed_critical_infrastructure.sql` | Migration |
| `docs/HANDOFF_v0.7.0.md` | Doc |

## Files Modified (9)

| File | Change |
|------|--------|
| `app/Domain/Asset/AssetEntity.php` | +geo fields (latitude, longitude, province, district, location_name, operational_sector) |
| `app/Domain/Asset/AssetRepository.php` | +geo fields in create/update SQL, +setLocation(), +findAllWithLocation() |
| `app/Domain/Asset/AssetController.php` | +setLocation() action |
| `app/Views/pages/assets/detail.php` | +Set Location form, +AI Explanation panel |
| `app/Views/pages/dashboard/admin.php` | +AI assessment widget, +NCSAM quick-launch |
| `app/Views/pages/dashboard/analyst.php` | +AI assessment widget, +NCSAM quick-launch |
| `app/Views/pages/dashboard/viewer.php` | +AI assessment widget, +NCSAM link |
| `app/Views/components/sidebar.php` | +Operational Map as first nav item |
| `app/Middleware/RoleMiddleware.php` | Better denial message (bug fix) |
| `cli/intelligence_worker.php` | +Stage 4: Intelligence Bus publish + AI assessment refresh |
| `routes/web.php` | +NCSAM routes, +map API routes, +AI API routes, +set-location route |
| `public/index.php` | +Env::load() early bootstrap |
| `cli_bootstrap.php` | +Env::load() early bootstrap |

---

## v0.8.0 Suggestions

- Province boundary GeoJSON integration (replace centroid markers with polygon overlays)
- Live News Intelligence ingestion — implement concrete `NewsSourceInterface` classes for ZNBC, Reuters, and BBC using RSS/Atom feeds (widely available, no paid API required for basic ingestion)
- Real-time map refresh via polling (30s interval on the map page to reload active layer data)
- Threat origin geolocation enrichment worker (populate `threats.origin_lat`/`origin_lng` via a public IP geolocation dataset, enabling animated threat path rendering)
- Intelligence Wall — dedicated full-screen situational display for the SOC floor
- AI explanation buttons on Threat, IOC, Alert, Incident, and Correlation detail pages (same pattern as asset detail, already built)
- User-configurable assessment refresh rate
