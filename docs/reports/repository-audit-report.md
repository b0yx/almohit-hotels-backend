# Repository Audit Report — Almohit Hotels Laravel Backend

## 1. Full File Classification

### 1.1 Core Application Files (KEEP)

```
app/Http/Controllers/Controller.php
app/Http/Controllers/Api/AuthController.php
app/Http/Controllers/Api/BookingController.php
app/Http/Controllers/Api/CrudController.php
app/Http/Controllers/Api/HotelController.php
app/Http/Controllers/Api/ImageUploadController.php
app/Http/Controllers/Api/ReviewController.php

app/Http/Middleware/AuthenticateApiToken.php
app/Http/Middleware/RequireRole.php
app/Http/Middleware/ResolvePublicHotel.php

app/Mail/PasswordResetOtpMail.php

app/Models/ApiToken.php
app/Models/AuditLog.php
app/Models/AvailabilityBlock.php
app/Models/BookingGuest.php
app/Models/BookingInquiry.php
app/Models/ChannelManagerConnection.php
app/Models/ContactMessage.php
app/Models/EmailOTP.php
app/Models/Hotel.php
app/Models/HotelAmenity.php
app/Models/HotelImage.php
app/Models/HotelPolicy.php
app/Models/HotelService.php
app/Models/PasswordResetOtp.php
app/Models/PropertyContacts.php
app/Models/PropertySetupStatus.php
app/Models/PropertySocialMedia.php
app/Models/Review.php
app/Models/RoomPrice.php
app/Models/RoomType.php
app/Models/RoomTypeImage.php
app/Models/ServiceCategory.php
app/Models/ServiceImage.php
app/Models/User.php

app/OpenApi/OpenApiSpec.php
app/Providers/AppServiceProvider.php
app/Support/CompatResponse.php
```

**Total: 43 files** — All actively used via routes, imports, Eloquent relationships, or test references.

### 1.2 Dead Code Candidates (REMOVE)

| File | Reason | Evidence |
|------|--------|----------|
| `app/Http/Middleware/RequirePermission.php` | Registered as `permission` in `bootstrap/app.php` but **never applied to any route** | Grep for `permission` in `routes/api.php` returns zero results |
| `app/Support/PermissionService.php` | Only referenced by `RequirePermission.php`. When the middleware is removed, this has no consumers | Grep for `PermissionService` across all non-vendor files returns only `RequirePermission.php` |

### 1.3 Configuration & Infrastructure Files (KEEP)

```
artisan
bootstrap/app.php
bootstrap/providers.php
composer.json
composer.lock
package.json
vite.config.js
phpunit.xml
.env
.env.example
.editorconfig
.gitattributes
.gitignore
.npmrc
Procfile
railway.json
public/.htaccess
public/favicon.ico
public/index.php
public/robots.txt
```

### 1.4 Config Files (KEEP)

```
config/almohit.php
config/app.php
config/auth.php
config/cache.php
config/cors.php
config/database.php
config/filesystems.php
config/l5-swagger.php
config/logging.php
config/mail.php
config/queue.php
config/sanctum.php
config/services.php
config/session.php
```

### 1.5 Database Files (KEEP)

```
database/database.sqlite
database/.gitignore
database/factories/UserFactory.php
database/seeders/DatabaseSeeder.php

database/migrations/0001_01_01_000000_create_users_table.php
database/migrations/0001_01_01_000001_create_cache_table.php
database/migrations/0001_01_01_000002_create_jobs_table.php
database/migrations/2026_06_23_000000_create_almohit_domain_tables.php
database/migrations/2026_06_23_000001_add_performance_indexes.php
database/migrations/2026_06_23_151453_create_personal_access_tokens_table.php
database/migrations/2026_06_24_000001_create_password_reset_otps_table.php
```

**Note:** Migration `2026_06_23_000001_add_performance_indexes.php` has a bug in its `down()` method — it passes an array to `Blueprint::dropIndex()` which expects a single string. Rolling back will fail. The forward migration works correctly.

### 1.6 Test Files (KEEP)

```
tests/TestCase.php
tests/Unit/ExampleTest.php
tests/Feature/ExampleTest.php
tests/Feature/ApiCompatibilityTest.php
tests/Feature/ApiTest.php
tests/Feature/AuthTest.php
tests/Feature/AuthorizationTest.php
tests/Feature/BookingFlowTest.php
tests/Feature/PasswordResetTest.php
tests/Feature/ReviewFlowTest.php
```

**All 10 test files are valid** — every route they reference exists, every model they import is a real class.

### 1.7 Resource/View Files (KEEP)

```
resources/css/app.css
resources/js/app.js
resources/views/emails/password-reset-otp.blade.php
resources/views/vendor/l5-swagger/.gitkeep
resources/views/vendor/l5-swagger/index.blade.php
resources/views/welcome.blade.php
```

### 1.8 Route Files (KEEP)

```
routes/api.php
routes/console.php
routes/web.php
```

---

## 2. Orphaned / Redundant Root-Level Files

### 2.1 Orphaned Reports — MOVE to `docs/reports/`

These files have zero references in code, config, or documentation. They are standalone reports that should be organized into the `docs/reports/` directory.

| File | Lines | Description |
|------|-------|-------------|
| `API_TEST_COVERAGE.md` | 30 | API test coverage summary |
| `API_TEST_PLAN.md` | 104 | Original API test plan |
| `FINAL_API_TEST_REPORT.md` | 35 | Final test results report |
| `forgot-password-implementation-report.md` | 209 | Password reset implementation report |

### 2.2 Orphaned Cleanup Artifacts — DELETE

These are leftover files from previous repository cleanup sessions. They reference each other but nothing in the actual codebase references them.

| File | Lines | Reason |
|------|-------|--------|
| `FILES_TO_DELETE.md` | 30 | Artifact from a previous cleanup. Content is a todo list of files to delete (most already gone). Self-referential — only referenced by `FINAL_PUSH_REPORT.md` which is also being deleted |
| `FINAL_PUSH_REPORT.md` | 50 | Release push report. Superseded by current state |
| `POST_CLEANUP_VERIFICATION.md` | 66 | Previous cleanup verification. No longer relevant as we are re-verifying |
| `REPOSITORY_CLEANUP_AUDIT.md` | 52 | Previous cleanup audit. Being replaced by this report |

### 2.3 Root-Level File Summary

| File | Status |
|------|--------|
| `README.md` | KEEP — project README |
| `LICENSE` | KEEP — MIT license |
| `API_TEST_COVERAGE.md` | MOVE to `docs/reports/` |
| `API_TEST_PLAN.md` | MOVE to `docs/reports/` |
| `FINAL_API_TEST_REPORT.md` | MOVE to `docs/reports/` |
| `forgot-password-implementation-report.md` | MOVE to `docs/reports/` |
| `FILES_TO_DELETE.md` | DELETE (cleanup artifact) |
| `FINAL_PUSH_REPORT.md` | DELETE (cleanup artifact) |
| `POST_CLEANUP_VERIFICATION.md` | DELETE (cleanup artifact) |
| `REPOSITORY_CLEANUP_AUDIT.md` | DELETE (cleanup artifact) |

---

## 3. Duplicate Files

| File | Size | Duplicate Of | Action |
|------|------|-------------|--------|
| `docs/postman_collection.json` | 2,756 bytes | `docs/api/POSTMAN_COLLECTION.json` (86,620 bytes) | **DELETE** — This is a minimal skeleton with only 2 folders (Auth, Properties) and no event scripts. The full collection at `docs/api/POSTMAN_COLLECTION.json` has 84 endpoints across 11 folders with full request/response schemas and is referenced by 6 documentation files. The smaller file is an incomplete early draft with zero references. |

---

## 4. Generated / Cache Files

| File | Action |
|------|--------|
| `storage/api-docs/api-docs.json` | KEEP — Generated by l5-swagger but tracked in git. Can be regenerated with `php artisan l5-swagger:generate` |
| `.phpunit.result.cache` | KEEP — Test cache. Should be added to `.gitignore` if not already |
| `database/database.sqlite` | KEEP — Local SQLite database for testing |
| `storage/app/public/hotels/*.png` | NOT TRACKED — Local uploaded images, not in git |
| `storage/app/public/room-types/*.png` | NOT TRACKED — Local uploaded images, not in git |
| `storage/app/public/services/*.png` | NOT TRACKED — Local uploaded images, not in git |

---

## 5. Branch Audit

### 5.1 Local Branches

| Branch | Last Commit | Unique vs HEAD | Mergable Status | Action |
|--------|------------|----------------|-----------------|--------|
| `release/clean-production-ready` | `ff304ba` feat: add forgot-password | — (current) | Active | **KEEP** |
| `main` | `5330555` docs: add final repository report | 0 unique | Ancestor of HEAD | **KEEP** (standard branch) |
| `cleanup/prepare-publication` | `a76cf74` Production hardening | 0 unique | Fully merged into HEAD | **DELETE** |
| `fix/railway-build` | `8269b10` fix: Railway 502 config cache | 0 unique | Fully merged into HEAD | **DELETE** |
| `release/github-cleanup` | `0b75a1b` prepare github structure | 0 unique | Fully merged into HEAD | **DELETE** |
| `release/production-hardening-2026-06-23` | `6ecc977` docs: pre-push verification | 0 unique | Fully merged into HEAD | **DELETE** |

### 5.2 Remote Branches

| Branch | Unique vs HEAD | Status | Action |
|--------|---------------|--------|--------|
| `origin/main` | 3 unique (GitHub PR merge commits) | Remote tracking | **KEEP** |
| `origin/release/clean-production-ready` | 0 unique | Synced (up to date) | **KEEP** |
| `origin/soliman` | 1 unique (`97a4f24` — "اول تعديلاتي ل المشروع") | Another developer's work | **KEEP** (do not touch) |

---

## 6. Dead Code Analysis

### 6.1 `RequirePermission` Middleware

```php
// bootstrap/app.php line 20
'permission' => \App\Http\Middleware\RequirePermission::class,
```

The middleware is registered but **never used**:

```
$ grep -r "permission" routes/ --include="*.php"
→ No results

$ grep -r "RequirePermission" app/ --include="*.php"
→ app/Http/Middleware/RequirePermission.php (self)
→ bootstrap/app.php (registration only)
```

The middleware references `PermissionService`:

```
$ grep -r "PermissionService" app/ --include="*.php"
→ app/Http/Middleware/RequirePermission.php (import + usage)
→ app/Support/PermissionService.php (definition)
```

**Verdict:** Both `RequirePermission.php` and `PermissionService.php` are dead code. They exist as infrastructure that was built but never wired to any route.

### 6.2 `ChannelManagerConnection` Model

The model and its API resource route are commented out in `routes/api.php` line 113:
```php
// FUTURE: Channel Manager integration — disabled for MVP
// Route::apiResource('channel-manager-connections', ...)
```

**Verdict:** The model exists in `app/Models/ChannelManagerConnection.php` and the migration creates its table, but there is no active route. This is **planned future code**, not dead code. **KEEP.**

---

## 7. Broken References

| Issue | Severity | Details |
|-------|----------|---------|
| Migration down() array bug | **Medium** | `2026_06_23_000001_add_performance_indexes.php` passes an array to `Blueprint::dropIndex()` which expects a single string. Rollback will fail. Forward migration works. |
| No broken route references | None | All routes in `routes/api.php` point to existing controller methods |
| No broken imports | None | All class imports resolve to existing files |
| No missing views | None | All view references point to existing templates |

---

## 8. Cleanup Summary

### Files to DELETE

```
app/Http/Middleware/RequirePermission.php      (dead middleware - never routed)
app/Support/PermissionService.php              (dead service - only used by above)
FILES_TO_DELETE.md                             (cleanup artifact)
FINAL_PUSH_REPORT.md                           (cleanup artifact)
POST_CLEANUP_VERIFICATION.md                   (cleanup artifact)
REPOSITORY_CLEANUP_AUDIT.md                    (cleanup artifact)
docs/postman_collection.json                   (duplicate - small incomplete draft)
```

### Files to MOVE (→ `docs/reports/`)

```
API_TEST_COVERAGE.md
API_TEST_PLAN.md
FINAL_API_TEST_REPORT.md
forgot-password-implementation-report.md
```

### Branches to DELETE (local)

```
cleanup/prepare-publication     (0 unique commits, fully merged)
fix/railway-build               (0 unique commits, fully merged)
release/github-cleanup           (0 unique commits, fully merged)
release/production-hardening-2026-06-23  (0 unique commits, fully merged)
```

### Integration Bug to FIX (optional)

`database/migrations/2026_06_23_000001_add_performance_indexes.php` — the `down()` method needs to drop indexes individually instead of passing an array. This only matters if you need to roll back this specific migration.

---

## 9. New Proposed Directory Structure

```
almohit_hotels_laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── CrudController.php
│   │   │   │   ├── HotelController.php
│   │   │   │   ├── ImageUploadController.php
│   │   │   │   └── ReviewController.php
│   │   │   └── Controller.php
│   │   └── Middleware/
│   │       ├── AuthenticateApiToken.php
│   │       ├── RequireRole.php
│   │       └── ResolvePublicHotel.php
│   ├── Mail/
│   │   └── PasswordResetOtpMail.php
│   ├── Models/
│   │   ├── ... (24 models)
│   ├── OpenApi/
│   │   └── OpenApiSpec.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Support/
│       └── CompatResponse.php
├── bootstrap/
├── config/
├── database/
│   ├── migrations/ (7 files)
│   ├── factories/
│   └── seeders/
├── docs/
│   ├── api/
│   │   ├── API.md
│   │   ├── OPENAPI_SPEC.json
│   │   ├── OPENAPI_SPEC.yaml
│   │   └── POSTMAN_COLLECTION.json
│   ├── architecture/
│   │   └── PROJECT_KNOWLEDGE_TRANSFER.md
│   ├── deployment/
│   │   ├── DEPLOYMENT.md
│   │   ├── DEPLOYMENT_READY.md
│   │   ├── DEVELOPMENT.md
│   │   ├── GITHUB_RELEASE_CHECKLIST.md
│   │   ├── PRODUCTION_READINESS_REPORT.md
│   │   └── .env.example.production
│   ├── handoff/
│   │   ├── FRONTEND_HANDOFF.md
│   │   ├── HANDOFF_PACKAGE.md
│   │   └── NEXTJS.env.example
│   └── reports/
│       ├── API_TEST_COVERAGE.md
│       ├── API_TEST_PLAN.md
│       ├── FINAL_API_TEST_REPORT.md
│       └── forgot-password-implementation-report.md
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│   ├── Feature/ (9 test files)
│   ├── Unit/ (1 test file)
│   └── TestCase.php
├── .env
├── .env.example
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── phpunit.xml
├── Procfile
├── railway.json
├── README.md
├── LICENSE
└── vite.config.js
```

---

## 10. Validation Steps (Post-Cleanup)

After cleanup, run:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test
```

Expected result: 59/59 tests passing, application boots cleanly, all routes resolve.
