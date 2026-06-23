# Railway Healthcheck Investigation Report

## Root Cause

The Railway healthcheck was failing because **no web server was listening on `$PORT`**.

### What was happening

`railway.json` contained a custom `startCommand`:

```json
"startCommand": "php artisan migrate --force && php artisan storage:link"
```

This **overrode** Nixpacks' auto-generated web server start command. The command ran:
1. `php artisan migrate --force` — completes successfully
2. `php artisan storage:link` — completes successfully
3. **Command exits** — no Nginx, no PHP-FPM, no process listening on `$PORT`

Railway's healthcheck then hits `$PORT/api/health` and gets **connection refused**.

### How Nixpacks PHP normally works

When no `startCommand` is provided, Nixpacks auto-generates:

```bash
node /assets/scripts/prestart.mjs /app/nginx.template.conf /nginx.conf \
  && (php-fpm -y /assets/php-fpm.conf & nginx -c /nginx.conf)
```

This starts PHP-FPM listening on `127.0.0.1:9000` and Nginx on `$PORT`, proxying `.php` requests to PHP-FPM.

## Changes Made

### 1. `railway.json` — removed `startCommand`

```diff
 "deploy": {
-    "startCommand": "php artisan migrate --force && php artisan storage:link",
     "healthcheckPath": "/api/health",
-    "healthcheckTimeout": 5,
+    "healthcheckTimeout": 30,
     "restartPolicyType": "ON_FAILURE",
     "restartPolicyMaxRetries": 3
 }
```

Without `startCommand`, Nixpacks falls back to its auto-generated command that starts Nginx + PHP-FPM.

Healthcheck timeout increased from 5 → 30 seconds to allow Nginx + PHP-FPM to fully initialize before Railway checks.

### 2. `Procfile` — new file with `release` phase

```
release: php artisan migrate --force && php artisan storage:link
```

Nixpacks runs the `release` phase **after build and before the web process starts**. This ensures migrations and storage links are set up before the healthcheck runs.

### 3. `docs/DEPLOYMENT.md` — added required env var

Added `NIXPACKS_PHP_ROOT_DIR=/app/public` to the required environment variables table. This tells the Nixpacks Nginx config to serve files from Laravel's `public/` directory instead of the project root (`/app`).

## Verification

| Check | Status |
|-------|--------|
| `php artisan config:cache` | ✅ passes |
| `php artisan route:list --path=api/health` | ✅ route registered |
| Health endpoint returns 200 | ✅ verified locally |
| `Procfile` syntax | ✅ valid |
| `railway.json` syntax | ✅ valid |

## Remaining Requirements

The following environment variable MUST be set in the Railway dashboard:

- `NIXPACKS_PHP_ROOT_DIR` = `/app/public`
