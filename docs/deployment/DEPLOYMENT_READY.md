# Deployment Readiness — Almohit Hotels API

## Railway Deployment Checklist

### Pre-Deploy

- [x] `railway.json` exists with Nixpacks builder
- [x] `Procfile` exists with migration release command
- [x] Health check endpoint at `/api/health/`
- [x] `composer.json` with all dependencies
- [x] `.env.example` with all required vars documented

### Required Environment Variables

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate: `php artisan key:generate --show` |
| `APP_URL` | Railway generated domain |
| `NIXPACKS_PHP_ROOT_DIR` | `/app/public` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Railway Postgres host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Railway Postgres database |
| `DB_USERNAME` | Railway Postgres user |
| `DB_PASSWORD` | Railway Postgres password |

### Post-Deploy

- [ ] Run `php artisan storage:link` (if not automated)
- [ ] Verify health: `curl https://your-app.railway.app/api/health/`
- [ ] Test auth flow end-to-end
- [ ] Configure custom domain (if needed)

## Nixpacks Notes

Nixpacks auto-detects Laravel and configures PHP-FPM + Nginx. Document root is `/app/public`. Set `NIXPACKS_PHP_ROOT_DIR=/app/public` if not auto-detected.

## Production Checklist

See `docs/deployment/DEPLOYMENT.md` for full production deployment guide.
See `docs/deployment/.env.example.production` for production environment template.
