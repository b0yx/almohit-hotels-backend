# Almohit Hotels API

> RESTful API backend for the Almohit Hotels booking platform — powering hotel listings, room management, reservations, guest reviews, and image handling with role-based access control.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-4169E1?logo=postgresql)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

- **Authentication** — Email signup, OTP verification, login/logout, role-based tokens (customer, staff, admin)
- **Hotels** — Property CRUD, publish/unpublish workflow, readiness validation, subdomain-based multi-tenancy
- **Rooms** — Room types, seasonal pricing, availability blocks, date-range availability queries
- **Bookings** — Public inquiry, staff confirmation, customer cancellation, guest management
- **Reviews** — Public submission, staff moderation, aggregated rating summaries
- **Images** — Multipart uploads for property, room, and service images with primary image support
- **Admin** — User management, role assignment, audit logs, contact message handling
- **API Docs** — OpenAPI 3.0.3 spec, Postman collection, frontend integration guide

---

## Architecture

```
┌──────────────────────────────────────────────┐
│              Next.js Frontend                  │
└────────────────────┬─────────────────────────┘
                     │ HTTPS / JSON (snake_case)
                     │ Authorization: Bearer <token>
                     ▼
┌──────────────────────────────────────────────┐
│         Laravel REST API (this repo)           │
│  PHP 8.4+ │ Laravel 13 │ PostgreSQL            │
├──────────────────────────────────────────────┤
│  app/                                          │
│  ├── Http/Controllers/Api/    Controller layer │
│  ├── Models/                  Eloquent models  │
│  ├── Http/Middleware/         Auth, role,      │
│  │                             tenant          │
│  └── Support/                 Response format  │
│  routes/api.php               70+ endpoints   │
└──────────────────────────────────────────────┘
```

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| **Runtime** | PHP 8.4+ |
| **Framework** | Laravel 13 |
| **Database** | PostgreSQL 15+ |
| **Auth** | Custom token-based (SHA-256 hashed tokens) |
| **Cache** | Database-driven (`database` driver) |
| **Queue** | Database-driven (`database` driver) |
| **Storage** | Local (`public` disk) / S3-compatible |
| **Frontend** | Next.js (separate repository) |
| **Deployment** | Railway / Laravel Cloud |

---

## Installation

### Requirements

- PHP 8.4+
- Composer 2.x
- PostgreSQL 15+
- Node.js 20+ (optional, for Vite asset building)

### Setup

```bash
# Clone the repository
git clone https://github.com/your-org/almohit-hotels-backend.git
cd almohit-hotels-backend

# Install PHP dependencies
composer install

# Environment configuration
cp .env.example .env
# Edit .env with your database credentials (see Environment Variables below)

# Generate application key
php artisan key:generate

# Storage symlink (for image uploads)
php artisan storage:link

# Run database migrations
php artisan migrate

# Seed demo data (optional)
php artisan db:seed
```

### Quick Dev Server

```bash
# Start the development server
php artisan serve

# Or run all services concurrently (server + queue + logs + Vite):
npm install && composer run dev
```

The API is available at `http://localhost:8000/api/`.

---

## Environment Variables

| Variable | Required | Default | Description |
|----------|:--------:|---------|-------------|
| `APP_ENV` | ✅ | `local` | Application environment (`local`, `production`, `testing`) |
| `APP_DEBUG` | ✅ | `true` | Enable/disable verbose error pages |
| `APP_KEY` | ✅ | — | 32-char base64-encoded application key (generate via `php artisan key:generate --show`) |
| `APP_URL` | ✅ | `http://localhost` | Base URL for the application |
| `DB_CONNECTION` | ✅ | `pgsql` | Database driver |
| `DB_HOST` | ✅ | `127.0.0.1` | Database host |
| `DB_PORT` | ✅ | `5432` | Database port |
| `DB_DATABASE` | ✅ | `almohit_hotels` | Database name |
| `DB_USERNAME` | ✅ | `postgres` | Database user |
| `DB_PASSWORD` | ✅ | — | Database password |
| `CACHE_STORE` | ❌ | `database` | Cache driver (`array`, `database`, `redis`) |
| `SESSION_DRIVER` | ❌ | `array` | Session driver (`array` for API-only) |
| `QUEUE_CONNECTION` | ❌ | `database` | Queue driver (`sync`, `database`) |
| `FILESYSTEM_DISK` | ❌ | `local` | Storage disk (`local`, `public`, `s3`) |
| `MAIL_MAILER` | ❌ | `log` | Mail driver (`log`, `smtp`) |
| `PUBLIC_BASE_DOMAIN` | ❌ | `almohit.com` | Base domain for subdomain hotel resolution |
| `FRONTEND_HOME_URL` | ❌ | `http://localhost:3000/` | Frontend home URL |

See [`.env.example`](.env.example) for the full list of available variables.

---

## Database Setup

### Local PostgreSQL

```sql
CREATE DATABASE almohit_hotels;
CREATE USER almohit WITH PASSWORD 'your-password';
GRANT ALL PRIVILEGES ON DATABASE almohit_hotels TO almohit;
```

### Migrations

The database contains **34 tables** across 5 migration files:

| Migration | Tables |
|-----------|--------|
| `0001_01_01_000000` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001` | `cache`, `cache_locks` |
| `0001_01_01_000002` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_06_23_000000` | Core domain tables (25 tables) |
| `2026_06_23_000001` | Performance indexes |

```bash
php artisan migrate
```

### Seed Data

```bash
php artisan db:seed
```

Creates 4 demo accounts (OTP code `123456` in local environment):

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@almohit.com` | `adminpass123` |
| **Staff** | `staff@almohit.com` | `staffpass123` |
| **Customer** | `customer@almohit.com` | `customerpass123` |
| **Test** | `test@example.com` | `testpass123` |

---

## Running Locally

### Development Server

```bash
php artisan serve
# API available at http://localhost:8000/api/
```

### Queue Worker (for async jobs)

```bash
php artisan queue:listen --tries=1 --timeout=0
```

### Running Tests

```bash
php artisan test
```

### Verify Health

```bash
curl http://localhost:8000/api/health/
```

Expected response:
```json
{ "status": "ok", "checks": { "database": "ok", "cache": "ok" }, "timestamp": "2026-06-23T12:00:00Z", "app_env": "local" }
```

---

## API Documentation

Full API documentation is available in several formats:

| Resource | Location | Description |
|----------|----------|-------------|
| **OpenAPI Spec (YAML)** | [`docs/api/OPENAPI_SPEC.yaml`](docs/api/OPENAPI_SPEC.yaml) | Complete OpenAPI 3.0.3 specification (56 paths, 15 schemas) |
| **OpenAPI Spec (JSON)** | [`docs/api/OPENAPI_SPEC.json`](docs/api/OPENAPI_SPEC.json) | JSON version for tool imports |
| **Postman Collection** | [`docs/api/POSTMAN_COLLECTION.json`](docs/api/POSTMAN_COLLECTION.json) | 84 endpoints across 11 folders |
| **API Reference** | [`docs/api/API.md`](docs/api/API.md) | Quick reference and navigation |
| **Frontend Handoff** | [`docs/handoff/FRONTEND_HANDOFF.md`](docs/handoff/FRONTEND_HANDOFF.md) | Comprehensive integration guide |
| **Developer Guide** | [`docs/deployment/DEVELOPMENT.md`](docs/deployment/DEVELOPMENT.md) | Full backend developer documentation |
| **Knowledge Transfer** | [`docs/architecture/PROJECT_KNOWLEDGE_TRANSFER.md`](docs/architecture/PROJECT_KNOWLEDGE_TRANSFER.md) | Comprehensive project reference (942 lines) |

### API Basics

| Property | Value |
|----------|-------|
| **Base URL** | `http://localhost:8000/api/` |
| **Auth** | `Authorization: Bearer <token>` |
| **Response format** | JSON, `snake_case` keys |
| **Pagination** | `{ count, next, previous, results }` |
| **Errors** | `{ detail: string, code: string }` |
| **Page size** | Default 20, max 100 (`?page_size=N`) |

---

## Authentication Flow

The API uses a custom token-based authentication system (not Laravel Sanctum/Passport).

```
1. POST /api/auth/signup/          → Create account (email, password, full_name)
2. POST /api/auth/verify-otp/      → Verify email with OTP code
3. POST /api/auth/login/           → Login → receives Bearer token
4. Use Authorization: Bearer <token> → for all authenticated requests
5. POST /api/auth/logout/          → Invalidate token
```

### Signup

```bash
curl -X POST http://localhost:8000/api/auth/signup/ \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"SecurePass123!","full_name":"John"}'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login/ \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@almohit.com","password":"adminpass123"}'
```

Response:
```json
{
  "token_type": "bearer",
  "access": "1|abc123...token...",
  "user": { "id": 1, "email": "admin@almohit.com", "role": "admin" }
}
```

> **Dev note:** In `APP_ENV=local`, the OTP is returned in the signup response as `debug_code` for convenience. The OTP code is always `123456` in local/testing environments.

---

## Booking Flow

```
1. POST /api/bookings/inquiry/         → Public: check availability & price estimate
2. POST /api/bookings/confirm/         → Auth: create confirmed booking
3. GET  /api/bookings/                 → Auth: list bookings
4. GET  /api/bookings/{id}/            → Auth: single booking detail
5. POST /api/bookings/{id}/cancel/     → Auth: cancel booking
```

- **Inquiry** is public (no authentication required)
- **Confirm, List, Detail, Cancel** require authentication
- Customers see only their own bookings; staff see assigned hotels' bookings

---

## Project Structure

```
almohit-hotels-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/             # API controllers (6)
│   │   └── Middleware/          # Auth, role, tenant middleware
│   ├── Models/                  # Eloquent models (23)
│   ├── Providers/               # Service providers
│   └── Support/                 # Response formatter
├── bootstrap/                   # Framework bootstrap
├── config/                      # Application configuration (12 files)
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # Database migrations (5)
│   └── seeders/                 # Demo data seeders
├── docs/
│   ├── api/                     # OpenAPI spec, Postman collection
│   ├── architecture/            # Project knowledge transfer
│   ├── deployment/              # Deployment guides
│   └── handoff/                 # Frontend integration docs
├── public/                      # Web server document root
├── resources/                   # Views, CSS, JS
├── routes/
│   ├── api.php                  # API route definitions (70+ endpoints)
│   ├── console.php              # Artisan commands
│   └── web.php                  # Web routes
├── storage/                     # Logs, cache, uploaded files
├── tests/                       # PHPUnit test suite
├── .env.example                 # Environment template
├── .gitignore                   # Git ignore rules
├── composer.json                # PHP dependencies
├── railway.json                 # Railway deployment config
└── Procfile                     # Railway release commands
```

---

## Development Guidelines

### Code Style

This project follows Laravel and PSR-12 coding standards. Run Pint to check:

```bash
./vendor/bin/pint
```

### Commit Messages

Use conventional commits:
- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation
- `refactor:` — Code refactoring
- `test:` — Tests

### API Conventions

- All endpoints require a trailing slash (e.g., `/api/properties/`)
- Response keys use `snake_case`
- Pagination uses DRF-compatible format: `{ count, next, previous, results }`
- Error responses follow `{ detail, code }` format
- Token auth via `Authorization: Bearer <token>` header

---

## Deployment Notes

### Railway

1. Push to GitHub and connect repository to Railway
2. Set environment variables (see [`.env.example`](.env.example))
3. Generate `APP_KEY`: `php artisan key:generate --show`
4. Set `NIXPACKS_PHP_ROOT_DIR=/app/public`
5. Railway auto-detects the build from [`railway.json`](railway.json)

### Production Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Generate a strong `APP_KEY`
- Use PostgreSQL with SSL (`DB_SSLMODE=require`)
- Configure S3 for file storage (`FILESYSTEM_DISK=s3`)
- Set `SESSION_DRIVER=array` (API-only, no sessions needed)
- Enable queue worker for async jobs: `php artisan queue:work`

See [`docs/deployment/DEPLOYMENT.md`](docs/deployment/DEPLOYMENT.md) for detailed deployment instructions.

---

## Contribution Guide

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-feature`
3. Commit your changes: `git commit -am 'feat: add awesome feature'`
4. Push to the branch: `git push origin feat/my-feature`
5. Open a Pull Request

Please ensure:
- Tests pass: `php artisan test`
- Code follows PSR-12: `./vendor/bin/pint`
- API changes are documented in OpenAPI spec

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Support

For feature requests or bug reports, please [open an issue](https://github.com/your-org/almohit-hotels-backend/issues) on GitHub.
