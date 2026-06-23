# Almohit Hotels API

RESTful API backend for the Almohit Hotels booking platform. Built with Laravel 13, powering hotel listings, room management, reservations, guest reviews, and image handling with role-based access control.

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
│  └── Support/                 Compat layer     │
│  routes/api.php               70+ endpoints   │
└──────────────────────────────────────────────┘
```

---

## Requirements

- PHP 8.4+
- Composer 2.x
- PostgreSQL 15+
- Node.js 20+ (optional, for Vite asset building)

---

## Quick Start

```bash
# Clone and install
git clone https://github.com/b0yx/almohit-hotels-backend.git
cd almohit-hotels-backend
composer install

# Environment setup
cp .env.example .env
# Edit .env with your database credentials

# Generate key and run migrations
php artisan key:generate
php artisan migrate

# Seed demo data (optional)
php artisan db:seed

# Start development server
php artisan serve
```

The API is available at `http://localhost:8000/api/`.

---

## API Documentation

| Resource | Path | Description |
|----------|------|-------------|
| **OpenAPI Spec (YAML)** | `docs/api/OPENAPI_SPEC.yaml` | Full API specification |
| **OpenAPI Spec (JSON)** | `docs/api/OPENAPI_SPEC.json` | JSON version |
| **Postman Collection** | `docs/api/POSTMAN_COLLECTION.json` | Pre-built API collection |
| **API Overview** | `docs/api/API.md` | Endpoint index and patterns |
| **Frontend Integration** | `docs/handoff/FRONTEND_HANDOFF.md` | Auth flow, pagination, error handling |

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

### Key Patterns

| Pattern | Detail |
|---------|--------|
| **Base URL** | `http://localhost:8000/api/` |
| **Auth** | `Authorization: Bearer <token>` |
| **Response format** | JSON, snake_case keys |
| **Pagination** | `{ count, next, previous, results }` |
| **Errors** | `{ detail, code }` |
| **Page size** | Default 20, max 100 (`?page_size=N`) |

---

## Environment Variables

Primary configuration via `.env` (see `.env.example` for full list):

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
| `QUEUE_CONNECTION` | `sync` | Queue driver |

---

## Development

```bash
# Start development server
php artisan serve

# Run queue worker (for async jobs)
php artisan queue:listen

# Run tests
php artisan test
```

---

## Deployment

Deployment guides:

- **Railway** — `docs/deployment/DEPLOYMENT.md`
- **Local/Manual** — `docs/deployment/DEVELOPMENT.md`

---

## Project Structure

```
├── app/
│   ├── Http/Controllers/Api/     API controllers (6)
│   ├── Http/Middleware/           Auth, role, tenant
│   ├── Models/                    Eloquent models (23)
│   ├── Providers/                 Service providers
│   └── Support/                   Compatibility layer
├── config/                        Application configuration (12 files)
├── database/
│   ├── migrations/                Database migrations (5)
│   └── seeders/                   Demo data seeders
├── docs/
│   ├── api/                       API specification and docs
│   ├── deployment/                Deployment guides
│   ├── handoff/                   Frontend integration handoffs
│   └── archive/                   Historical audit reports
├── public/                        Web server document root
├── routes/
│   ├── api.php                    API route definitions
│   ├── web.php                    Web routes
│   └── console.php                Artisan commands
├── storage/                       Logs, cache, uploaded files
├── tests/                         PHPUnit test suite
├── .env.example                   Environment template
├── composer.json                  PHP dependencies
├── railway.json                   Railway deployment config
└── Procfile                       Railway release commands
```

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## Contributing

For feature requests or bug reports, please open an issue on GitHub. For frontend (Next.js) integration, refer to `docs/handoff/FRONTEND_HANDOFF.md`.
