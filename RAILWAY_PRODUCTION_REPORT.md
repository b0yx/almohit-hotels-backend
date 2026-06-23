# Railway Production Readiness — Almohit Hotels API

**Date:** 2026-06-23

---

## Configuration Status

### railway.json

| Field | Value | Status |
|-------|-------|:------:|
| Builder | `NIXPACKS` | ✅ |
| Build command | `composer install --no-dev --optimize-autoloader` | ✅ |
| Health check path | `/api/health/` | ✅ |
| Health check timeout | 30s | ✅ |
| Restart policy | ON_FAILURE, max 3 retries | ✅ |

### Procfile

| Process | Command | Status |
|---------|---------|:------:|
| release | `php artisan migrate --force && php artisan storage:link` | ✅ |

### Health Check

Endpoint: `GET /api/health/` returns:
```json
{
  "status": "ok",
  "checks": { "database": "ok", "cache": "ok" },
  "timestamp": "2026-06-23T00:00:00Z",
  "app_env": "production"
}
```

### Environment Variables

| Variable | Dev (`.env.example`) | Production (`.env.example.production`) |
|----------|---------------------|----------------------------------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `CACHE_STORE` | `array` | `database` |
| `SESSION_DRIVER` | `array` | `array` |
| `QUEUE_CONNECTION` | `sync` | `database` |
| `FILESYSTEM_DISK` | `local` | `s3` |
| `DB_CONNECTION` | `pgsql` | `pgsql` |

---

## Verification Steps

| Step | Command | Status |
|------|---------|:------:|
| Config cache | `php artisan config:cache` | ✅ |
| Route cache | `php artisan route:cache` | ✅ |
| Migration | `php artisan migrate --force` | ✅ |
| Storage link | `php artisan storage:link` | ✅ via Procfile |
| Health check | `curl /api/health/` | ✅ |
| Test suite | `php artisan test` | ✅ |

---

## Notes

- Nixpacks auto-detects Laravel and configures PHP-FPM + Nginx
- Set `NIXPACKS_PHP_ROOT_DIR=/app/public` in Railway env vars
- Storage symlink is created during release phase
- All auth endpoints have rate limiting applied
- OTP brute force protection enabled
- Production env template at `docs/deployment/.env.example.production`
