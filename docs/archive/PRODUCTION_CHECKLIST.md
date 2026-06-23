# Production Checklist — Almohit Hotels Laravel

Generated: 2026-06-23

---

## 1. Logging

| # | Check | Status | Details |
|---|-------|--------|---------|
| 1.1 | Logging channel configured | ✅ CHECKED | `config/logging.php:21` — Default channel is `stack`, supports `single`, `daily`, `slack`, `papertrail`, etc. All config driven by `env()`. |
| 1.2 | Controllers logging meaningful events | ❌ MISSING | Zero `Log::` calls exist in any controller (`app/Http/Controllers/Api/`). No booking-created, booking-confirmed, hotel-published, or review-submitted events are logged. |
| 1.3 | Auth events logged (login/logout/failed) | ❌ MISSING | `AuthController::login()` (`app/Http/Controllers/Api/AuthController.php:85`) and `logout()` (`:156`) do NOT write any log entries. No event listeners/subscribers found in `app/Listeners/` or `app/Events/`. |
| 1.4 | Booking events logged | ❌ MISSING | `BookingController::store()` (`:37`), `inquiry()` (`:79`), `confirm()` (`:145`), `cancel()` (`:159`) perform no logging. |
| 1.5 | Error logging in exception handler | ⚠️ NEEDS ATTENTION | `bootstrap/app.php:22-25` — JSON rendering for API errors is configured, but no custom error logging (e.g., Sentry, Flare, or custom Log::error in exception handler). Relies on Laravel defaults. |

**Recommendations:**
- Add `Log::info()` / `Log::error()` calls in all controller actions for audit trail
- Create event classes (`UserLoggedIn`, `BookingCreated`, `BookingConfirmed`) with listeners that write logs
- Integrate a production error tracker (Sentry, Flare, etc.) in the exceptions handler
- Log failed login attempts with email/IP

---

## 2. Queue Readiness

| # | Check | Status | Details |
|---|-------|--------|---------|
| 2.1 | Queue driver configured | ✅ CHECKED | `config/queue.php:16` — Default driver is `database` (`QUEUE_CONNECTION=database`). |
| 2.2 | Queueable jobs exist | ❌ MISSING | No files found in `app/Jobs/`. No job classes created. |
| 2.3 | `failed_jobs` migration exists | ✅ CHECKED | `database/migrations/0001_01_01_000002_create_jobs_table.php:37` — Includes `failed_jobs` table with uuid, connection, queue, payload, exception, failed_at columns. |
| 2.4 | Jobs table migration exists | ✅ CHECKED | Same migration creates `jobs` (`:14`) and `job_batches` (`:24`) tables. |
| 2.5 | Email sending should use queues | ⚠️ NEEDS ATTENTION | `config/mail.php:17` — Default mailer is `log`. No production mailer configured. When switching to SMTP/SES, emails must be queued to avoid request-timeouts. No Mailables found. |
| 2.6 | Image processing should use queues | ⚠️ NEEDS ATTENTION | `ImageUploadController` (`app/Http/Controllers/Api/ImageUploadController.php:69`) processes images synchronously. Thumbnail generation and image optimization should be queued. |

**Recommendations:**
- Create job classes for: `SendEmailVerification`, `SendBookingConfirmation`, `ProcessUploadedImage`
- Configure a production queue worker (supervisor) on the server
- Set `QUEUE_CONNECTION=database` and run `php artisan queue:work`

---

## 3. File Storage

| # | Check | Status | Details |
|---|-------|--------|---------|
| 3.1 | Public disk configured | ✅ CHECKED | `config/filesystems.php:41` — Driver: `local`, root: `storage/app/public`, URL: `APP_URL/storage`. |
| 3.2 | Local disk configured | ✅ CHECKED | `config/filesystems.php:33` — Driver: `local`, root: `storage/app/private`. |
| 3.3 | S3/cloud storage configured | ⚠️ NEEDS ATTENTION | `config/filesystems.php:50` — S3 disk defined with key/secret/region/bucket/endpoint from env. Credentials in `.env.example` (`:66-70`) exist but are empty by default. Not currently used — all images stored on `public` disk. |
| 3.4 | `storage:link` created | ✅ CHECKED | Symlink exists: `public/storage -> storage/app/public`. Both directories contain hotel/room-type/service subdirectories. |
| 3.5 | `public/media` symlink documented | ⚠️ NEEDS ATTENTION | `public/media/` is a real directory (not a symlink). Images are stored at `/media/...` path via `ImageUploadController`. The `filesystems.php:76-78` links array only includes `public_path('storage')`. The `/media/` URL path does not map to a symlink — files are stored at `storage/app/public/hotels/...` but served via `public/media/hotels/...`. This works only if the web server is configured to serve from `public/media`. |

**Recommendations:**
- Verify web server configuration serves `public/media/` directory correctly
- For production, migrate to S3 with CloudFront CDN
- Update `storage:link` documentation to include the `/media/` path mapping
- Set `FILESYSTEM_DISK=s3` when ready for cloud storage

---

## 4. Environment Handling

| # | Check | Status | Details |
|---|-------|--------|---------|
| 4.1 | `APP_ENV` usage | ✅ CHECKED | Used correctly in `config/app.php:29` — `env('APP_ENV', 'production')`. |
| 4.2 | `APP_DEBUG` properly gated | ✅ CHECKED | `config/app.php:42` — `(bool) env('APP_DEBUG', false)` — defaults to `false` in production. |
| 4.3 | Environment-specific configs | ⚠️ NEEDS ATTENTION | No environment-specific config files (no `config/production/`, no `.env.production`). Single `.env.example` serves all environments. |
| 4.4 | `.env.example` has required variables | ✅ CHECKED | `.env.example` (`:1-72`) contains all needed vars: DB, APP_KEY, QUEUE, MAIL, SESSION, FILESYSTEM, AWS, etc. |
| 4.5 | Hardcoded vs `env()` in configs | ✅ CHECKED | All config files use `env()` helper. No hardcoded secrets or environment-specific values found in `config/`. |

**Recommendations:**
- Consider `config/` subdirectories for environment-specific overrides (or use Laravel's built-in env detection)
- Set `APP_DEBUG=false` and `APP_ENV=production` in production `.env`
- Rotate the `APP_KEY` before production deployment

---

## 5. Security Headers

| # | Check | Status | Details |
|---|-------|--------|---------|
| 5.1 | CORS configuration | ❌ MISSING | No `config/cors.php` file exists. No `HandleCors` middleware found. API will accept requests from any origin by default. |
| 5.2 | CSP header | ❌ MISSING | No Content-Security-Policy header configured. |
| 5.3 | HSTS header | ❌ MISSING | No Strict-Transport-Security header configured. |
| 5.4 | X-Frame-Options / X-Content-Type-Options | ❌ MISSING | No frame-options or content-type-options headers set. |
| 5.5 | CSRF protection for API routes | ⚠️ NEEDS ATTENTION | API routes use custom `AuthenticateApiToken` middleware (`app/Http/Middleware/AuthenticateApiToken.php`) — no CSRF token needed for API (correct). Web routes (`routes/web.php`) have default Laravel CSRF protection (VerifyCsrfToken). |
| 5.6 | XSS protections | ❌ MISSING | No X-XSS-Protection or other XSS mitigation headers or middleware. SQL uses Eloquent ORM (parameterized) which prevents SQL injection. |
| 5.7 | SQL injection protections | ✅ CHECKED | All queries use Eloquent ORM. Only one raw query found: `BookingController.php:25` — `$query->whereRaw('1 = 0')` which is a safe no-op for unauthorized users. |

**Recommendations:**
- Add `config/cors.php` restricting allowed origins to known frontend domains
- Add security headers middleware: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Use a package like `laravel-security-headers` or write custom middleware
- Set `SESSION_SECURE_COOKIE=true` and `SESSION_SAME_SITE=strict` in production
- Add `X-XSS-Protection` header (though modern browsers use CSP instead)

---

## 6. Rate Limiting

| # | Check | Status | Details |
|---|-------|--------|---------|
| 6.1 | Rate limit middleware in Kernel/bootstrap | ❌ MISSING | No `throttle` middleware registered in `bootstrap/app.php`. The `->withMiddleware()` call only defines `api.token`, `tenant.context`, and `role` aliases. |
| 6.2 | Auth endpoint rate limiting | ❌ MISSING | Login, signup, OTP verification endpoints in `routes/api.php:37-55` have no rate limiting. Vulnerable to brute-force attacks. |
| 6.3 | API global rate limiting | ❌ MISSING | No `throttle:api` middleware applied to the API route group. |
| 6.4 | API token middleware rate limiting | ❌ MISSING | `AuthenticateApiToken` middleware (`app/Http/Middleware/AuthenticateApiToken.php`) has no rate limiting logic. |

**Recommendations:**
- Apply `throttle:60,1` (60 requests/min) to the entire API group
- Apply stricter `throttle:5,1` (5 attempts/min) to login, signup, OTP routes
- Use Laravel's `RateLimiter` facade for named rate limiters in `AppServiceProvider::boot()`

---

## 7. CORS Configuration

| # | Check | Status | Details |
|---|-------|--------|---------|
| 7.1 | CORS config file exists | ❌ MISSING | `config/cors.php` does not exist. Laravel 11 no longer ships this file by default. |
| 7.2 | Allowed origins | ❌ MISSING | No CORS configuration means `*` (all origins) is effectively allowed when `HandleCors` middleware runs. |
| 7.3 | Allowed methods | ❌ MISSING | Not configured. |
| 7.4 | Allowed headers | ❌ MISSING | Not configured. |
| 7.5 | Credentials support | ❌ MISSING | Not configured. |

**Recommendations:**
- Create `config/cors.php` with:
  - `allowed_origins` → whitelist frontend domains (localhost:3000, production frontend URL)
  - `allowed_methods` → `['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS']`
  - `allowed_headers` → `['Content-Type', 'Authorization', 'X-Hotel-Subdomain', 'X-Requested-With']`
  - `supports_credentials` → `true` (if cookies/sessions used)

---

## 8. Session Configuration

| # | Check | Status | Details |
|---|-------|--------|---------|
| 8.1 | Session driver appropriate for API | ⚠️ NEEDS ATTENTION | `config/session.php:21` — Driver is `database`. Since this is an API (stateless, token-based auth via `ApiToken`), sessions should be `array` or session middleware should be removed from API. |
| 8.2 | Cookie secure flag | ⚠️ NEEDS ATTENTION | `config/session.php:172` — `secure` is driven by `SESSION_SECURE_COOKIE` env var. Must be set to `true` in production for HTTPS. |
| 8.3 | HTTP-only flag | ✅ CHECKED | `config/session.php:185` — `http_only` defaults to `true`. |
| 8.4 | Same-Site attribute | ✅ CHECKED | `config/session.php:202` — `same_site` defaults to `lax`. |
| 8.5 | Session encryption | ⚠️ NEEDS ATTENTION | `config/session.php:50` — `encrypt` defaults to `false`. Should enable if sensitive data stored. |
| 8.6 | Serialization | ✅ CHECKED | `config/session.php:231` — Serialization is `json`, preventing PHP object injection. |

**Recommendations:**
- Set `SESSION_DRIVER=array` for API-only deployment, or remove `StartSession` middleware from API group
- Set `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true` in production `.env`
- Set `SESSION_SAME_SITE=strict` for production

---

## 9. Maintenance Mode

| # | Check | Status | Details |
|---|-------|--------|---------|
| 9.1 | Maintenance mode driver configured | ✅ CHECKED | `config/app.php:122` — `APP_MAINTENANCE_DRIVER=file` defined. |
| 9.2 | `PreventRequestsDuringMaintenance` middleware | ✅ CHECKED | Laravel 11 includes this middleware in the default HTTP kernel. No explicit override found — it is active. |
| 9.3 | Maintenance mode custom view | ❌ MISSING | No custom `503.blade.php` view in `resources/views/errors/`. Default Laravel maintenance page will be shown. |
| 9.4 | Maintenance mode JSON response for API | ❌ MISSING | API requests during maintenance will get the HTML maintenance page, not a JSON response. |

**Recommendations:**
- Create `resources/views/errors/503.blade.php` for branded maintenance page
- Ensure `shouldRenderJsonWhen` in `bootstrap/app.php:22-25` handles 503 status for API routes (Laravel 11 should handle this, but test it)

---

## 10. Error Page Handling

| # | Check | Status | Details |
|---|-------|--------|---------|
| 10.1 | JSON responses for API errors | ✅ CHECKED | `bootstrap/app.php:22-25` — `shouldRenderJsonWhen` returns true for `api/*` requests. All API errors return JSON. |
| 10.2 | Custom 404 handling | ⚠️ NEEDS ATTENTION | No custom error views in `resources/views/errors/`. Only `resources/views/welcome.blade.php` exists. Web 404/403/500 pages use Laravel defaults. |
| 10.3 | Custom exception handler | ⚠️ NEEDS ATTENTION | No `app/Exceptions/Handler.php` (Laravel 11 uses `bootstrap/app.php` for exception config). The `withExceptions()` callback only configures JSON rendering. No custom reporting logic (e.g., Sentry, Slack notifications). |
| 10.4 | API error consistency | ⚠️ NEEDS ATTENTION | Error responses use mixed formats: some use `ValidationException` with `detail` key, others return `['detail' => ...]`, some return just `['code' => ...]`. Inconsistent error envelope. |

**Recommendations:**
- Create custom error views: `resources/views/errors/404.blade.php`, `500.blade.php`, `403.blade.php`
- Standardize API error format to `{ "detail": "...", "code": "...", "status": 4xx }`
- Add error reporting integration (Sentry, Bugsnag, or Slack via `Log::channel('slack')`) in the exceptions handler
- Add HTTP exception rendering for 403, 404, 429, 500 in `bootstrap/app.php`

---

## Summary

| Category | ✅ CHECKED | ⚠️ NEEDS ATTENTION | ❌ MISSING |
|----------|-----------|-------------------|-----------|
| Logging | 1 | 1 | 3 |
| Queue Readiness | 2 | 2 | 1 |
| File Storage | 3 | 2 | 0 |
| Environment Handling | 4 | 1 | 0 |
| Security Headers | 1 | 1 | 5 |
| Rate Limiting | 0 | 0 | 4 |
| CORS Configuration | 0 | 0 | 5 |
| Session Configuration | 3 | 3 | 0 |
| Maintenance Mode | 2 | 0 | 2 |
| Error Page Handling | 1 | 3 | 0 |
| **Total** | **17** | **13** | **20** |

### Critical blockers for production:
1. **No rate limiting on auth endpoints** — brute force vulnerability
2. **No CORS configuration** — API accessible from any origin
3. **No security headers** — missing HSTS, CSP, X-Frame-Options
4. **No logging of auth/booking events** — zero audit trail
5. **Session driver is `database` for an API app** — should be `array`
