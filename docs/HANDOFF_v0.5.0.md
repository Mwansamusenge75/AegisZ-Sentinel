# AegisZ Sentinel v0.5.0 — Development Handoff Report

**Version:** v0.5.0 — Authentication, RBAC & Analyst Workflow
**Build Date:** 2026-06-28
**Previous Version:** v0.4.0 — Intelligence Layer
**Status:** Complete. Fully backward compatible with v0.1.0–v0.4.0.

---

## What Was Built

### 1. Session Management

**File:** `app/Core/Session.php`

PHP native session wrapper. Provides clean API for all session operations.

- `Session::start()` — starts session with hardened cookie config (httponly, samesite=Strict, 1hr lifetime)
- `Session::setUser(array)` — stores auth user, calls `regenerate()` to prevent session fixation
- `Session::getUser()` — returns current auth user array or null
- `Session::isAuthenticated()` — boolean check
- `Session::destroy()` — full session destroy with cookie clearance (logout)
- `Session::flash(key, msg)` / `Session::getFlash(key)` — one-time flash messages
- Session timeout enforced at 3600 seconds (1 hour) both via `gc_maxlifetime` and explicit expiry check in `AuthMiddleware`

---

### 2. Authentication Middleware

**File:** `app/Middleware/AuthMiddleware.php`

- Called automatically from `BaseController::__construct()`
- Redirects unauthenticated requests to `/login`, storing the original URL in session for post-login redirect
- Checks session expiry (1 hour since login_at)
- Static convenience method: `AuthMiddleware::requireAuth()`

---

### 3. Role Middleware

**File:** `app/Middleware/RoleMiddleware.php`

Role hierarchy (highest privileges first):

```
admin → analyst → viewer
```

- `RoleMiddleware::requireRole('admin')` — call in controller methods to enforce a minimum role
- `RoleMiddleware::currentUserHasRole('analyst')` — boolean check for use in views
- Sends 403 with flash error and redirects to dashboard on failure (no blank error page)

---

### 4. User Domain

**Files:** `app/Domain/User/UserEntity.php`, `UserRepositoryInterface.php`, `UserRepository.php`, `UserService.php`

| Class | Responsibility |
|-------|---------------|
| `UserEntity` | Value object. `toPublicArray()` never includes password hash. |
| `UserRepository` | All SQL for the `users` table. Prepared statements only. |
| `UserService` | Auth logic, user CRUD, password policy enforcement. |

**Password policy:** minimum 10 chars, at least one uppercase, lowercase, digit, and special character. Enforced in `validatePasswordStrength()`.

**Hashing:** `PASSWORD_BCRYPT` with cost 12. Verified with `password_verify()`. Hash never logged or transmitted.

**Authentication flow:**
1. Check username exists
2. Check account is `active`
3. `password_verify()` against stored hash
4. Update `last_login_at`
5. Write audit log entry
6. `Session::setUser()` (regenerates session ID)

Failed login attempts are audit-logged with username and IP but never reveal whether the username exists (returns identical error message either way).

---

### 5. Alert Lifecycle Management

**Files:**
- `app/Repositories/AlertWorkflowRepository.php`
- `app/Services/Workflow/AlertWorkflowService.php`
- `app/Controllers/AlertWorkflowController.php`
- `app/Views/pages/alerts/index.php`

**Lifecycle:**
```
open → acknowledged → assigned → escalated → resolved → closed
```

Transition rules are enforced in `AlertWorkflowService::TRANSITIONS`. Invalid transitions are rejected with a descriptive error. Every transition writes to `alert_workflow_log` with: `alert_id`, `user_id`, `from_status`, `to_status`, `note`, `timestamp`.

Analyst role required to perform transitions. Viewers see the queue but no action buttons.

---

### 6. Incident Workflow Management

**Files:**
- `app/Repositories/IncidentWorkflowRepository.php`
- `app/Services/Workflow/IncidentWorkflowService.php`
- `app/Controllers/IncidentWorkflowController.php`
- `app/Views/pages/incidents/index.php`
- `app/Views/pages/incidents/detail.php`

**Lifecycle:**
```
open → investigating → contained → resolved → closed
```

Analysts can add notes at any point (append-only, up to 5000 chars). Notes and status transitions both appear in the detail view timeline. Workflow history shows full audit trail with username and timestamp.

---

### 7. User Administration

**Files:**
- `app/Controllers/Admin/UserAdminController.php`
- `app/Views/pages/admin/users/index.php`
- `app/Views/pages/admin/users/create.php`
- `app/Views/pages/admin/users/edit.php`

Admin role required for all operations. Self-delete is blocked. Admin password reset bypasses current password check. All operations are audit-logged.

---

### 8. Auth Controller + Login Page

**Files:**
- `app/Controllers/AuthController.php` (extends `PublicBaseController`)
- `app/Controllers/PublicBaseController.php`
- `app/Views/pages/auth/login.php` (standalone — no main layout)

CSRF token validated on login POST. After login, redirects to originally requested URL (stored in session before redirect to `/login`). Logout POSTs to `/logout` (CSRF protected), destroys session, redirects to `/login`.

---

### 9. Updated BaseController

**File:** `app/Controllers/BaseController.php`

Now calls `AuthMiddleware::requireAuth()` in constructor. Injects into every `render()` call:
- `$currentUser` — session user array
- `$baseUrl` — from config
- `$flashError` / `$flashSuccess` — consumed from session flash

---

## Database Migration

**File:** `database/migrations/007_auth_rbac.sql`

New tables:

| Table | Purpose |
|-------|---------|
| `users` | Platform users with bcrypt password hash |
| `alert_workflow_log` | Alert status transition audit trail |
| `incident_notes` | Analyst notes (append-only) |
| `incident_workflow_log` | Incident status transition audit trail |

Column additions:

| Table | Column | Purpose |
|-------|--------|---------|
| `alerts` | `assigned_to` (INT FK → users) | Assigned analyst |
| `alerts` | `status` ENUM extended | Added: acknowledged, assigned, escalated, resolved |
| `incidents` | `assigned_to` (INT FK → users) | Assigned analyst |

**Default admin account (seeded in migration):**

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `ChangeMe123!` |
| Role | `admin` |

**Change this password immediately after first login via `/admin/users`.**

---

## Files Created / Modified

### New Files (21)

| File | Type |
|------|------|
| `app/Core/Session.php` | Core |
| `app/Middleware/AuthMiddleware.php` | Middleware |
| `app/Middleware/RoleMiddleware.php` | Middleware |
| `app/Domain/User/UserEntity.php` | Domain |
| `app/Domain/User/UserRepositoryInterface.php` | Domain |
| `app/Domain/User/UserRepository.php` | Domain |
| `app/Domain/User/UserService.php` | Domain |
| `app/Repositories/AlertWorkflowRepository.php` | Repository |
| `app/Repositories/IncidentWorkflowRepository.php` | Repository |
| `app/Services/Workflow/AlertWorkflowService.php` | Service |
| `app/Services/Workflow/IncidentWorkflowService.php` | Service |
| `app/Controllers/PublicBaseController.php` | Controller |
| `app/Controllers/AuthController.php` | Controller |
| `app/Controllers/AlertWorkflowController.php` | Controller |
| `app/Controllers/IncidentWorkflowController.php` | Controller |
| `app/Controllers/Admin/UserAdminController.php` | Controller |
| `app/Views/pages/auth/login.php` | View |
| `app/Views/pages/alerts/index.php` | View |
| `app/Views/pages/incidents/index.php` | View |
| `app/Views/pages/incidents/detail.php` | View |
| `app/Views/pages/admin/users/index.php` | View |
| `app/Views/pages/admin/users/create.php` | View |
| `app/Views/pages/admin/users/edit.php` | View |
| `database/migrations/007_auth_rbac.sql` | Migration |
| `docs/HANDOFF_v0.5.0.md` | This document |

### Modified Files (6)

| File | Change |
|------|--------|
| `app/Controllers/BaseController.php` | Added auth enforcement + session injection |
| `app/Domain/Alert/AlertEntity.php` | Extended status enum + `assigned_to` field |
| `app/Views/layouts/main.php` | Pass `currentUser` + `baseUrl` to partials; global flash messages |
| `app/Views/components/navbar.php` | User info + logout button |
| `app/Views/components/sidebar.php` | Role-conditional nav: Alerts, Incidents, Admin section |
| `routes/web.php` | Auth routes, alert/incident/admin routes |
| `config/config.php` | Version updated to 0.5.0 |

### Untouched (All Prior Versions Preserved)

All v0.3.0 workers, feed services, v0.4.0 intelligence services, all existing repositories, CLI bootstrap, migrations 001–006, Core framework classes.

---

## Migration & First Run

```sql
-- In phpMyAdmin with aegisz_db selected, run only 007:
source database/migrations/007_auth_rbac.sql;
```

Then open `http://localhost/aegisz-sentinel/` — you will be redirected to `/login`.

**Default credentials:**
- Username: `admin`
- Password: `ChangeMe123!`

**Immediately after login:**
1. Go to `/admin/users`
2. Edit the `admin` user
3. Reset the password to something strong

---

## Architecture Compliance

- ✅ No SQL in Controllers
- ✅ No business logic in Views
- ✅ Services contain only business logic, no SQL
- ✅ All DB access via Repositories with PDO prepared statements
- ✅ Workers untouched — CLI-only with HTTP guard
- ✅ No Composer, no frameworks, shared hosting compatible
- ✅ CSRF protection on all POST forms
- ✅ XSS protection via `Security::e()` on all output
- ✅ Passwords hashed with bcrypt cost 12, never stored plain
- ✅ Session fixation prevented via ID regeneration on login
- ✅ Backward compatible with v0.1.0–v0.4.0

---

## v0.6.0 Suggestions

- API authentication (token-based, for the existing REST API placeholders)
- Alert auto-generation from high-severity correlations (with analyst review gate)
- Asset management CRUD UI
- IOC search and manual entry UI
- Expanded MITRE technique set
- Dashboard role-awareness (analysts see their own assigned alerts/incidents)
