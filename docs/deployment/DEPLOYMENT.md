# Deployment Guide — Almohit Hotels Laravel Backend

## Deploying to Railway

### Prerequisites

- A [Railway](https://railway.app) account
- A PostgreSQL database (Railway can provision one)

### Required Environment Variables

Set these in the Railway dashboard under your project's **Variables** tab.

| Variable | Required | Example Value | Notes |
|----------|:--------:|---------------|-------|
| `APP_KEY` | ✅ | *(generate via `php artisan key:generate --show`)* | 32-char base64-encoded key |
| `APP_ENV` | ✅ | `production` | |
| `APP_DEBUG` | ✅ | `false` | Must be `false` in production |
| `APP_URL` | ✅ | `https://your-app.railway.app` | |
| `DB_CONNECTION` | ✅ | `pgsql` | |
| `DB_HOST` | ✅ | *(Railway-provided)* | |
| `DB_PORT` | ✅ | `5432` | |
| `DB_DATABASE` | ✅ | *(Railway-provided)* | |
| `DB_USERNAME` | ✅ | *(Railway-provided)* | |
| `DB_PASSWORD` | ✅ | *(Railway-provided)* | |
| `CACHE_STORE` | ✅ | `database` | `database` or `redis` |
| `SESSION_DRIVER` | ✅ | `array` | API-only, no sessions needed |
| `QUEUE_CONNECTION` | ✅ | `database` | |
| `FILESYSTEM_DISK` | ✅ | `s3` | For production image storage |
| `NIXPACKS_PHP_ROOT_DIR` | ✅ | `/app/public` | Required for Laravel — Nixpacks needs to serve from `public/` |
| `LOG_CHANNEL` | ❌ | `stderr` | Defaults to `stack`, Railway captures stderr |
| `LOG_LEVEL` | ❌ | `warning` | Defaults to `debug` |

### Brevo API Email Configuration

Transactional emails (OTP verification, password reset, admin notifications) are sent through the **Brevo API** (HTTP) instead of SMTP.

| Variable | Required | Example Value | Notes |
|----------|:--------:|---------------|-------|
| `MAIL_PROVIDER` | ✅ | `brevo` | Must be set to `brevo` |
| `BREVO_API_KEY` | ✅ | *(your Brevo API v3 key)* | Generate from Brevo dashboard → SMTP & API → API Keys |
| `MAIL_FROM_ADDRESS` | ✅ | `noreply@almohit.com` | Verified sender in Brevo |
| `MAIL_FROM_NAME` | ❌ | `Almohit Hotels` | Defaults to `APP_NAME` |

**Notes:**
- SMTP variables (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, etc.) are **not required** when using Brevo API.
- The Brevo HTTP API avoids SMTP IP authorization issues (e.g. `525 5.7.1 Unauthorized IP address`).
- On Railway, add `BREVO_API_KEY` as a secret environment variable.
- Do **not** commit the real API key to any repository.

### Railway PostgreSQL Database

Railway can provision a PostgreSQL database for your project:

1. In your Railway project, click **New** → **Database** → **PostgreSQL**
2. Railway will auto-populate `DATABASE_URL` — but Laravel needs individual vars:
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
3. Extract these from the Railway-provided `DATABASE_URL`:
   ```
   postgresql://user:password@host:port/database
   ```
4. Or use the Railway CLI:
   ```bash
   railway connect
   ```

### Deploy Steps

1. **Push to GitHub** and connect your repository to Railway:

   ```bash
   git push origin main
   # Then in Railway dashboard: New Project → Deploy from GitHub repo
   ```

2. **Set environment variables** in Railway dashboard (see table above).

3. **Generate APP_KEY** locally and set it in Railway:

   ```bash
   php artisan key:generate --show
   # Copy the output and set as APP_KEY in Railway dashboard
   ```

4. **Deploy automatically** — `railway.json` + `Procfile` handle the rest:
   - **Build:** `composer install --no-dev --optimize-autoloader && php artisan config:cache`
   - **Release (pre-deploy):** `php artisan migrate --force && php artisan storage:link` (runs from `Procfile`)
   - **Start:** Nixpacks auto-generates Nginx + PHP-FPM web server start command
   - **Document root:** must set `NIXPACKS_PHP_ROOT_DIR=/app/public` in Railway env vars

5. **Seed demo data** (optional, via Railway shell):

   ```bash
   php artisan db:seed
   ```

### Verification

After deployment, verify the health endpoint:

```bash
curl https://your-app.railway.app/api/health/
```

Expected response:

```json
{
    "status": "ok",
    "checks": { "database": "ok", "cache": "ok" },
    "timestamp": "2026-06-23T12:00:00Z",
    "app_env": "production"
}
```

### Troubleshooting

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| `500` on health check | Missing `APP_KEY` | Generate and set `APP_KEY` |
| `database: error` in health check | Wrong DB credentials | Verify `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` |
| `Connection refused` | DB host/port wrong | Verify Railway-provided PostgreSQL connection details |
| Images not loading | Storage link missing | Already handled in startup command |
| `No application encryption key` | APP_KEY not set | Run `php artisan key:generate --show` and set it |

### Scaling Considerations

- **Queue workers:** For production, add a separate service running `php artisan queue:work` for async email/image processing
- **Redis:** Switch `CACHE_STORE` to `redis` for better performance
- **CDN:** Point `AWS_URL` to a CloudFront/CDN distribution for image delivery
