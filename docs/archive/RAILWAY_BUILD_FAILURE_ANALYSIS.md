# Railway Build Failure Analysis & Fix

## Root Cause

The Docker build failed at **Step 13/14** (`php artisan migrate --force && php artisan storage:link`) with:

```
SQLSTATE[08006] [7] could not translate host name "postgres.railway.internal" to address: Name or service not known
```

### Why It Happened

- Nixpacks reads the `Procfile` and converts its `release` phase into a Docker `RUN` command during image creation (Step 13/14).
- The `release` command (`php artisan migrate --force && php artisan storage:link`) requires a database connection.
- The database host (`postgres.railway.internal`) is a Railway-internal hostname that only resolves in the **deploy runtime network**, not in the **Docker build environment**.
- As a result, migrations fail during the build, causing the entire build to fail.

### What Was NOT Wrong

- The **build phase** (`composer install --no-dev --optimize-autoloader && php artisan config:cache`) passes successfully (Step 11/14).
- PHP version, Composer dependencies, and Laravel configuration are all correct.
- The database service itself is correctly configured — it just isn't reachable during Docker image creation.

### The Build Process (Nixpacks on Railway)

| Phase | Command | When | DB Available? |
|-------|---------|------|---------------|
| Build | `composer install --no-dev --optimize-autoloader && php artisan config:cache` | Docker build | ❌ Not needed |
| **Release** | **`php artisan migrate --force && php artisan storage:link`** | **Docker build** | **❌ FAILS** |
| Start | `node /assets/scripts/prestart.mjs ... && (php-fpm & nginx ...)` | Container startup | ✅ |

## Fix Applied

### 1. Deleted `Procfile`

Removed the `release: php artisan migrate --force && php artisan storage:link` line. This prevents Nixpacks from running migrations as a Docker build step.

### 2. Added `startCommand` to `railway.json`

Moved migration execution to the container **startup phase**, when the database is available:

```json
"startCommand": "php artisan migrate --force && php artisan storage:link && node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf && (php-fpm -y /assets/php-fpm.conf & nginx -c /nginx.conf)"
```

This chained command:
1. Runs `php artisan migrate --force` — executes pending migrations
2. Runs `php artisan storage:link` — creates the storage symlink
3. Starts the web server (Nginx + PHP-FPM) using Nixpacks' auto-generated command

### Verification

```bash
composer install --no-dev --optimize-autoloader    # ✅ Passes
php artisan config:cache                           # ✅ "Configuration cached successfully"
php artisan route:list --path=api                  # ✅ All 121 API routes listed
php artisan config:clear                           # ✅ Cache cleared successfully
```

## Migration Strategy

- First deploy after fix: Run `php artisan migrate --force` automatically via `startCommand` on container startup.
- Subsequent deploys: Migrations are idempotent — `migrate --force` only runs pending migrations.
- Manual fallback: If needed, migrations can be triggered via Railway CLI: `railway run php artisan migrate --force`.

## Files Changed

| File | Change |
|------|--------|
| `Procfile` | **Deleted** — contained `release: php artisan migrate --force && php artisan storage:link` |
| `railway.json` | **Added** `deploy.startCommand` — runs migrations + starts web server at container startup |
