# Railway 502 Analysis & Fix

## Symptom

Deployment shows as ACTIVE on Railway Dashboard, but all endpoints return:

```json
{
  "status": "error",
  "code": 502,
  "message": "Application failed to respond"
}
```

Response time before 502: ~2.5 seconds (connection timeout).

## Root Cause

**`php artisan config:cache` during Docker build froze stale database credentials.**

### How It Happened

1. **Build phase** (`railway.json`): `composer install --no-dev --optimize-autoloader && php artisan config:cache`
   - `php artisan config:cache` evaluates all `env()` calls in config files and serializes the results into `bootstrap/cache/config.php`
   - The `.env` file (baked into the Docker image) contains `DB_HOST=172.21.0.2` — a local Docker bridge IP
   - `config:cache` freezes `DB_HOST=172.21.0.2` as the database host in the cached config

2. **Start command**: `php artisan migrate --force && php artisan storage:link && node /assets/scripts/prestart.mjs ...`
   - At container startup, `php artisan migrate --force` reads the cached config
   - Tries to connect to `172.21.0.2:5432` — this host does NOT exist in Railway's runtime network
   - Connection attempt hangs for ~2.5 seconds then times out
   - Migration command exits with error code 1
   - The `&&` chain **stops immediately** — the web server (Nginx + PHP-FPM) is **never started**

3. **Railway health check** hits port 80, finds no process listening, times out → returns HTTP 502.

### Why the Build Appeared to Succeed

- Step 11/14 (`composer install && php artisan config:cache`) **passed** — config caching doesn't need a database
- Step 13/14 (`php artisan migrate --force` in release phase) was the original failure (fixed previously by moving to `startCommand`)
- But the moved migration still failed — just at a different stage (container startup instead of Docker build)

## Exact Failing Component

| Component | Status | Reason |
|-----------|--------|--------|
| `php artisan config:cache` during build | ❌ | Freezes `DB_HOST=172.21.0.2` from `.env` |
| `php artisan migrate --force` at startup | ❌ | Tries `172.21.0.2:5432`, times out |
| `&&` chain propagation | ❌ | Migration failure prevents web server start |
| Nginx + PHP-FPM startup | ❌ | Never reached |
| Railway health check → port 80 | ❌ | No process listening → 502 after 2.5s timeout |

## Fix Applied

### 1. `config/database.php` — Read Railway's `DATABASE_URL`

```diff
- 'url' => env('DB_URL'),
+ 'url' => env('DATABASE_URL', env('DB_URL')),
```

Railway auto-injects a `DATABASE_URL` environment variable for PostgreSQL add-ons (format: `postgresql://user:password@host:5432/database`). Laravel's Postgres connector parses this URL to derive connection details (host, port, database, username, password). Since `url` takes precedence over individual `DB_HOST`/`DB_PORT` etc., the stale `.env` values are ignored.

### 2. `railway.json` — Move `config:cache` from build to runtime

**Build phase** (removed `config:cache`):
```diff
- "buildCommand": "composer install --no-dev --optimize-autoloader && php artisan config:cache"
+ "buildCommand": "composer install --no-dev --optimize-autoloader"
```

No more config caching during build — prevents stale `.env` values from being frozen.

**Start phase** (added `config:cache` after migrations):
```diff
- "startCommand": "php artisan migrate --force && php artisan storage:link && node ..."
+ "startCommand": "php artisan migrate --force && php artisan storage:link && php artisan config:cache && node ..."
```

Config is now cached at container startup, AFTER Railway's environment variables are available. The correct `DATABASE_URL` is captured in the cache.

### Runtime Flow (after fix)

| Step | Command | DB Available? | Outcome |
|------|---------|---------------|---------|
| 1 | `php artisan migrate --force` | ✅ `DATABASE_URL` resolves to `postgres.railway.internal` | Migrations run |
| 2 | `php artisan storage:link` | n/a | Symlink created |
| 3 | `php artisan config:cache` | ✅ Railway env vars available | Correct values cached |
| 4 | `node prestart.mjs && (php-fpm & nginx)` | n/a | Web server starts |
| 5 | Railway health check → `/api/health` | ✅ | Returns HTTP 200 |

## Verification

1. Config change reads `DATABASE_URL`:
   ```bash
   DATABASE_URL="postgresql://u:p@host:5432/db" php artisan tinker \
     --execute="dd(config('database.connections.pgsql.url'))"
   # → "postgresql://u:p@host:5432/db"
   ```

2. `php artisan config:cache` works locally: ✅ "Configuration cached successfully."

3. All API routes intact: ✅ 121 routes listed.

## Files Changed

| File | Change |
|------|--------|
| `config/database.php:89` | `env('DB_URL')` → `env('DATABASE_URL', env('DB_URL'))` |
| `railway.json:5` | Removed `&& php artisan config:cache` from buildCommand |
| `railway.json:12` | Added `&& php artisan config:cache` to startCommand |

## Environment Variables Required on Railway

These are auto-injected by Railway's PostgreSQL plugin — no manual setup needed:

| Variable | Value (example) | Used By |
|----------|-----------------|---------|
| `DATABASE_URL` | `postgresql://postgres:...@postgres.railway.internal:5432/railway` | `config/database.php` (via `env('DATABASE_URL')`) |

## Edge Cases

- **No `DATABASE_URL` set**: Falls back to `env('DB_URL')` (which may be `null`), then to individual `env('DB_HOST')` etc. from `.env`. If deploying to a non-Railway environment without `DATABASE_URL`, set `DB_URL` or individual `DB_*` variables.
- **Config cache race condition**: `config:cache` runs after migrations but before web server starts. The health endpoint responds even with uncached config because `DATABASE_URL` is read from environment at request time.
