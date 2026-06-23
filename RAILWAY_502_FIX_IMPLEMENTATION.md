# Railway 502 Fix Implementation

## Problem

Public endpoint returned HTTP 502 "Application failed to respond" while internal Railway healthchecks passed.

## Root Cause

Two independent issues:

### 1. `startCommand` used `php artisan serve`

`railway.json` had a custom start command that overrode Nixpacks' default Docker CMD:

```json
"startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT"
```

This ran PHP's built-in development server — single-threaded, no process supervision. It passed the initial healthcheck but crashed or became unresponsive under production traffic. Nginx and php-fpm (both installed by Nixpacks during build) were never started.

### 2. `NIXPACKS_PHP_ROOT_DIR` not set

Nixpacks' nginx template defaults document root to `/app`, but Laravel's entry point is at `/app/public/index.php`. Without `NIXPACKS_PHP_ROOT_DIR=/app/public`, even the default nginx+php-fpm CMD would fail to find `index.php`.

## Changes Made

### File: `railway.json`

Removed the `startCommand` property. Railway now uses Nixpacks' default start command:

```
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf \
  && (php-fpm -y /assets/php-fpm.conf & nginx -c /nginx.conf)
```

**Before:**
```json
{
    "build": {
        "builder": "NIXPACKS",
        "buildCommand": "composer install --no-dev --optimize-autoloader"
    },
    "deploy": {
        "healthcheckPath": "/api/health",
        "healthcheckTimeout": 30,
        "restartPolicyType": "ON_FAILURE",
        "restartPolicyMaxRetries": 3,
        "startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT"
    }
}
```

**After:**
```json
{
    "build": {
        "builder": "NIXPACKS",
        "buildCommand": "composer install --no-dev --optimize-autoloader"
    },
    "deploy": {
        "healthcheckPath": "/api/health",
        "healthcheckTimeout": 30,
        "restartPolicyType": "ON_FAILURE",
        "restartPolicyMaxRetries": 3
    }
}
```

## Required Environment Variable

Nixpacks auto-detects `artisan` and sets `IS_LARAVEL=yes`, which enables Laravel routing in the nginx template (`try_files $uri $uri/ /index.php?$query_string`). However, the nginx document root must point to Laravel's `public/` directory.

**Set this in Railway dashboard → Variables:**

| Key                      | Value         |
|--------------------------|---------------|
| `NIXPACKS_PHP_ROOT_DIR`  | `/app/public` |

Without this, nginx looks for `index.php` at `/app/index.php` (does not exist) instead of `/app/public/index.php`.

## Deploy Steps

1. Set `NIXPACKS_PHP_ROOT_DIR=/app/public` in Railway dashboard
2. Deploy by pushing this commit
3. Verify: `https://almohit-hotels-backend-production.up.railway.app/api/health` returns 200

## Verification

Local testing confirmed all three required endpoints respond correctly:

| Endpoint       | HTTP Status |
|----------------|-------------|
| `/api/health`  | 200         |
| `/`            | 200         |
| `/api/hotels`  | 200         |

## Nixpacks Runtime

After this fix, Railway starts the container with:

| Process  | Role                                  |
|----------|---------------------------------------|
| nginx    | Web server, reverse proxy to php-fpm  |
| php-fpm  | PHP FastCGI Process Manager           |
| Laravel  | Application logic via index.php       |

Nixpacks' nginx template includes Laravel-specific routing when `IS_LARAVEL=yes` is auto-detected (presence of `artisan` file).
