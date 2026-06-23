# Almohit Hotels API

RESTful API for the Almohit Hotels booking platform — a Laravel 13 backend powering hotel listings, room management, booking reservations, guest reviews, and image handling with role-based access control.

Built as a migration from Django REST Framework to Laravel with full endpoint parity, PostgreSQL persistence, and Sanctum-style token authentication.

---

## Features

- **Authentication** — Email signup, OTP verification, login/logout, role-based tokens (customer, staff, admin)
- **Hotels** — Property CRUD, publish/unpublish workflow, readiness validation, setup wizard, subdomain-based multi-tenancy
- **Rooms** — Room types, seasonal pricing, availability blocks, date-range availability queries
- **Bookings** — Public inquiry (no auth required), staff confirmation, customer cancellation, guest management
- **Reviews** — Public submission, staff moderation, aggregated rating summaries
- **Images** — Multipart uploads for property, room, and service images with primary image support
- **Admin** — User management, role assignment, audit logs, contact message handling
- **API Docs** — OpenAPI 3.0.3 spec, Postman collection, frontend integration guide

---

## Architecture

```
┌──────────────────────────────────────────────────────┐
│                    Next.js Frontend                    │
│                   (coming soon — see docs/)            │
└──────────────────────┬───────────────────────────────┘
                       │ HTTPS / JSON (snake_case)
                       │ Authorization: Bearer <token>
                       ▼
┌──────────────────────────────────────────────────────┐
│              Laravel REST API (this repo)              │
│  PHP 8.3+ │ Laravel 13 │ PostgreSQL │ Sanctum tokens  │
├──────────────────────────────────────────────────────┤
│  app/                                                    │
│  ├── Http/Controllers/Api/    ← Controller layer       │
│  ├── Models/                  ← Eloquent models (23)   │
│  ├── Http/Middleware/         ← Auth, tenant, role     │
│  └── Support/                 ← Compat response layer  │
│  routes/api.php               ← 71+ API endpoints     │
└──────────────────────────────────────────────────────┘
```

---

## Requirements

- PHP 8.3+
- Composer 2.x
- PostgreSQL 15+
- Node.js 20+ (for Vite asset building, optional for API-only)

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-org/almohit-hotels-laravel.git
cd almohit-hotels-laravel

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. (Optional) Seed demo accounts
php artisan db:seed

# 7. Start the development server
php artisan serve
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Application environment |
| `APP_DEBUG` | `true` | Enable debug mode |
| `APP_URL` | `http://localhost` | Application URL |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `5432` | Database port |
| `DB_DATABASE` | `almohit_hotels` | Database name |
| `DB_USERNAME` | `postgres` | Database user |
| `DB_PASSWORD` | — | Database password |
| `CACHE_STORE` | `array` | Cache driver |
| `SESSION_DRIVER` | `array` | Session driver (API-only) |
| `FILESYSTEM_DISK` | `local` | File storage disk |
| `QUEUE_CONNECTION` | `sync` | Queue driver |

See `.env.example` for the complete list.

---

## Running Locally

```bash
# Start the API server
php artisan serve

# Run queue worker (for async jobs)
php artisan queue:listen

# Run tests
php artisan test
```

The API will be available at `http://localhost:8000/api/`.

---

## Demo Accounts

Run `php artisan db:seed` to create test accounts:

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@almohit.com` | `adminpass123` |
| Staff | `staff@almohit.com` | `staffpass123` |
| Customer | `customer@almohit.com` | `customerpass123` |

---

## API Documentation

| Resource | Location | Description |
|----------|----------|-------------|
| **OpenAPI Spec (YAML)** | `docs/OPENAPI_SPEC.yaml` | Full API specification |
| **OpenAPI Spec (JSON)** | `docs/OPENAPI_SPEC.json` | JSON version |
| **Postman Collection** | `docs/POSTMAN_COLLECTION.json` | Pre-built API collection |
| **Frontend Integration** | `docs/FRONTEND_HANDOFF.md` | Auth flow, pagination, error handling patterns |
| **MVP Handoff** | `docs/NEXTJS_MVP_HANDOFF.md` | MVP-scoped endpoint guide |
| **Postman** | Import `docs/POSTMAN_COLLECTION.json` into Postman for all 84 endpoints |

### Quick Start

```bash
# Health check
curl http://localhost:8000/api/health/

# Signup
curl -X POST http://localhost:8000/api/auth/signup/ \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"SecurePass123!","full_name":"John"}'

# Login
curl -X POST http://localhost:8000/api/auth/login/ \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@almohit.com","password":"adminpass123"}'

# Browse hotels (paginated)
curl http://localhost:8000/api/properties/available/?page=1&page_size=10
```

### Key API Patterns

| Pattern | Detail |
|---------|--------|
| **Base URL** | `http://localhost:8000/api/` |
| **Auth** | `Authorization: Bearer <token>` |
| **Response format** | JSON, snake_case keys |
| **Pagination** | `{ count, next, previous, results }` |
| **Errors** | `{ detail, code }` |
| **Page size** | Default 20, max 100 (`?page_size=N`) |

---

## Deployment

Deployment guides are available in:

- `docs/DEPLOYMENT.md` — Railway deployment guide (coming soon after initial setup)
- Standard Laravel deployment to any PHP 8.3+ host with PostgreSQL

### Deploying to Railway

```bash
# Railway auto-detects PHP from composer.json
# Set environment variables in Railway dashboard:
#   APP_KEY, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Run migrations after deploy:
#   php artisan migrate --force
```

---

## Project Structure

```
├── app/
│   ├── Http/Controllers/Api/     ← API controllers
│   ├── Http/Middleware/               ← Auth, role, tenant middleware
│   └── Models/                    ← Eloquent models (23)
├── config/                        ← Configuration files
├── database/
│   ├── migrations/                ← Database migrations
│   └── seeders/                   ← Demo data seeders
├── docs/                          ← Documentation
│   ├── archive/                   ← Internal/AI-generated audit reports
│   ├── FRONTEND_HANDOFF.md
│   ├── NEXTJS_MVP_HANDOFF.md
│   ├── OPENAPI_SPEC.yaml
│   └── POSTMAN_COLLECTION.json
├── routes/
│   └── api.php                    ← API route definitions
├── storage/                       ← Logs, cache, uploaded files
├── tests/                         ← PHPUnit test suite
├── .env.example                   ← Environment template
└── composer.json                  ← PHP dependencies
```

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Contributing

This is an active project. For feature requests or bug reports, please open an issue on GitHub.

For the frontend (Next.js) integration, refer to `docs/FRONTEND_HANDOFF.md` and `docs/NEXTJS_MVP_HANDOFF.md`.
