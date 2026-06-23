# Repository Audit — Almohit Hotels Laravel Backend

Audit date: 2026-06-23
Branch: `release/github-cleanup`
Commit: `2b58688` (base before cleanup)

## Classification Legend

| Label | Action |
|-------|--------|
| **KEEP** | Preserve in-place, no changes |
| **KEEP (rewrite)** | Preserve file location but rewrite content |
| **MOVE → docs/api/** | Relocate to `docs/api/` |
| **MOVE → docs/deployment/** | Relocate to `docs/deployment/` |
| **MOVE → docs/handoff/** | Relocate to `docs/handoff/` |
| **MOVE → docs/archive/** | Relocate to `docs/archive/` — historical/internal, not for public consumption |

---

## Root Directory

| File | Classification | Rationale |
|------|---------------|-----------|
| `README.md` | **KEEP (rewrite)** | Core project documentation — will be rewritten professionally |
| `LICENSE` | **KEEP** | MIT license |
| `composer.json` | **KEEP** | PHP dependency manifest |
| `composer.lock` | **KEEP** | Locked dependency versions |
| `railway.json` | **KEEP** | Railway deployment configuration |
| `Procfile` | **KEEP** | Railway release phase (migrations) |
| `.env.example` | **KEEP** | Environment template for local development |
| `.env.example.production` | **MOVE → docs/archive/** | Redundant with `.env.example`; production config documented in `docs/deployment/` |
| `artisan` | **KEEP** | Laravel CLI entry point |
| `package.json` | **KEEP** | npm manifest (Vite/build tooling) |
| `vite.config.js` | **KEEP** | Vite configuration |
| `phpunit.xml` | **KEEP** | PHPUnit configuration |
| `.editorconfig` | **KEEP** | Editor settings |
| `.gitattributes` | **KEEP** | Git attribute rules |
| `.gitignore` | **KEEP** | Git ignore rules |
| `.npmrc` | **KEEP** | npm configuration |
| `GITHUB_PUSH_REPORT.md` | **MOVE → docs/archive/** | Historical deployment log; irrelevant to public |
| `RAILWAY_HEALTHCHECK_REPORT.md` | **MOVE → docs/archive/** | Historical investigation report; irrelevant to public |

---

## `app/` — Application Source Code

All files are **KEEP**. Core business logic and middleware.

| File | Classification |
|------|---------------|
| `app/Http/Controllers/Api/AuthController.php` | KEEP |
| `app/Http/Controllers/Api/BookingController.php` | KEEP |
| `app/Http/Controllers/Api/CrudController.php` | KEEP |
| `app/Http/Controllers/Api/HotelController.php` | KEEP |
| `app/Http/Controllers/Api/ImageUploadController.php` | KEEP |
| `app/Http/Controllers/Api/ReviewController.php` | KEEP |
| `app/Http/Controllers/Controller.php` | KEEP |
| `app/Http/Middleware/AuthenticateApiToken.php` | KEEP |
| `app/Http/Middleware/RequireRole.php` | KEEP |
| `app/Http/Middleware/ResolvePublicHotel.php` | KEEP |
| `app/Models/*` (23 models) | KEEP |
| `app/Providers/AppServiceProvider.php` | KEEP |
| `app/Support/CompatResponse.php` | KEEP |

---

## `bootstrap/` — Framework Boot

All files **KEEP**.

| File | Classification |
|------|---------------|
| `bootstrap/app.php` | KEEP |
| `bootstrap/providers.php` | KEEP |
| `bootstrap/cache/.gitignore` | KEEP |

---

## `config/` — Application Configuration

All files **KEEP**.

| File | Classification |
|------|---------------|
| `config/almohit.php` | KEEP |
| `config/app.php` | KEEP |
| `config/auth.php` | KEEP |
| `config/cache.php` | KEEP |
| `config/cors.php` | KEEP |
| `config/database.php` | KEEP |
| `config/filesystems.php` | KEEP |
| `config/logging.php` | KEEP |
| `config/mail.php` | KEEP |
| `config/queue.php` | KEEP |
| `config/services.php` | KEEP |
| `config/session.php` | KEEP |

---

## `database/` — Migrations, Seeds, Factories

All files **KEEP**.

| File | Classification |
|------|---------------|
| `database/factories/UserFactory.php` | KEEP |
| `database/.gitignore` | KEEP |
| `database/migrations/0001_01_01_000000_create_users_table.php` | KEEP |
| `database/migrations/0001_01_01_000001_create_cache_table.php` | KEEP |
| `database/migrations/0001_01_01_000002_create_jobs_table.php` | KEEP |
| `database/migrations/2026_06_23_000000_create_almohit_domain_tables.php` | KEEP |
| `database/migrations/2026_06_23_000001_add_performance_indexes.php` | KEEP |
| `database/seeders/DatabaseSeeder.php` | KEEP |

---

## `public/` — Web Server Document Root

All files **KEEP**.

| File | Classification |
|------|---------------|
| `public/favicon.ico` | KEEP |
| `public/.htaccess` | KEEP |
| `public/index.php` | KEEP |
| `public/media` | KEEP |
| `public/robots.txt` | KEEP |

---

## `resources/` — Frontend Assets

All files **KEEP**.

| File | Classification |
|------|---------------|
| `resources/css/app.css` | KEEP |
| `resources/js/app.js` | KEEP |
| `resources/views/welcome.blade.php` | KEEP |

---

## `routes/` — HTTP Route Definitions

All files **KEEP**.

| File | Classification |
|------|---------------|
| `routes/api.php` | KEEP |
| `routes/console.php` | KEEP |
| `routes/web.php` | KEEP |

---

## `storage/` — Runtime Storage

All directory `.gitignore` files **KEEP** (preserve empty directory structure).

---

## `tests/` — Test Suite

All files **KEEP**.

| File | Classification |
|------|---------------|
| `tests/Feature/ApiCompatibilityTest.php` | KEEP |
| `tests/Feature/ExampleTest.php` | KEEP |
| `tests/TestCase.php` | KEEP |
| `tests/Unit/ExampleTest.php` | KEEP |

---

## `docs/` — Documentation

| File | Classification | Rationale |
|------|---------------|-----------|
| `docs/API.md` | **MOVE → docs/api/** | API documentation index; belongs in `docs/api/` |
| `docs/DEPLOYMENT.md` | **MOVE → docs/deployment/** | Deployment guide; belongs in `docs/deployment/` |
| `docs/DEVELOPMENT.md` | **MOVE → docs/deployment/** | Development setup guide; belongs in `docs/deployment/` |
| `docs/DEMO_ACCOUNTS.md` | **MOVE → docs/archive/** | Contains hardcoded demo credentials; not for public README |
| `docs/FRONTEND_HANDOFF.md` | **MOVE → docs/handoff/** | Frontend integration handoff |
| `docs/HANDOFF_PACKAGE.md` | **MOVE → docs/handoff/** | Handoff package documentation |
| `docs/MVP_SCOPE_REPORT.md` | **MOVE → docs/archive/** | Historical scope audit; irrelevant to public |
| `docs/NEXTJS.env.example` | **MOVE → docs/archive/** | Frontend env template; not backend concern |
| `docs/NEXTJS_MVP_HANDOFF.md` | **MOVE → docs/archive/** | Historical frontend handoff |
| `docs/OPENAPI_SPEC.json` | **MOVE → docs/api/** | OpenAPI spec (JSON); belongs in `docs/api/` |
| `docs/OPENAPI_SPEC.yaml` | **MOVE → docs/api/** | OpenAPI spec (YAML); belongs in `docs/api/` |
| `docs/POSTMAN_COLLECTION.json` | **MOVE → docs/api/** | Postman collection; belongs in `docs/api/` |
| `docs/archive/*` (17 files) | **KEEP** | Already archived |

---

## Summary of Moves

| From | To | Count |
|------|----|-------|
| Root → `docs/archive/` | `GITHUB_PUSH_REPORT.md`, `RAILWAY_HEALTHCHECK_REPORT.md`, `.env.example.production` | 3 |
| `docs/` → `docs/api/` | `API.md`, `OPENAPI_SPEC.json`, `OPENAPI_SPEC.yaml`, `POSTMAN_COLLECTION.json` | 4 |
| `docs/` → `docs/deployment/` | `DEPLOYMENT.md`, `DEVELOPMENT.md` | 2 |
| `docs/` → `docs/handoff/` | `FRONTEND_HANDOFF.md`, `HANDOFF_PACKAGE.md` | 2 |
| `docs/` → `docs/archive/` | `DEMO_ACCOUNTS.md`, `MVP_SCOPE_REPORT.md`, `NEXTJS.env.example`, `NEXTJS_MVP_HANDOFF.md` | 4 |
| **Total files moved** | | **15** |
| **Files preserved in-place** | | **106** |
| **Files removed** | | **0** |
