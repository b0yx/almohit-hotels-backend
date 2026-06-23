# Docker Database Connection Report

> Generated: 2026-06-23
> Laravel repository: `/home/venom/mohit-hotels-project/almohit_hotels_laravel/`
> Django repository: `/home/venom/mohit-hotels-project/almohit_hotels_end/`

---

## 1. Architecture

```
 Host Machine (Ubuntu)
 ┌────────────────────────────────────────────────────┐
 │  Laravel (outside Docker)                          │
 │  ─────────────────────────────                     │
 │  Connects via 172.21.0.2:5432                      │
 │  (Docker bridge IP)                                │
 │                          ↑                         │
 │                          │                         │
 │  Docker Network: mohit-hotels-project_default      │
 │  ┌─────────────────────────────────────────────┐   │
 │  │  PostgreSQL Container (postgres:17)          │   │
 │  │  ─────────────────────────────────────      │   │
 │  │  Hostname (internal): db                     │   │
 │  │  Bridge IP:           172.21.0.2             │   │
 │  │  Port:                5432                   │   │
 │  │  Database:            almohit_hotels_db      │   │
 │  │  Database:            almohit_hotels_laravel │   │
 │  └─────────────────────────────────────────────┘   │
 │                                                    │
 │  Ubuntu PostgreSQL 16 (localhost:5432)             │
 │  ──── NOT used by Django ────                      │
 └────────────────────────────────────────────────────┘
```

---

## 2. Credentials Discovered

| Parameter       | Source                                  | Value                    |
|-----------------|-----------------------------------------|--------------------------|
| `POSTGRES_DB`   | `docker-compose.yml` + root `.env`      | `almohit_hotels_db`      |
| `POSTGRES_USER` | `docker-compose.yml` + root `.env`      | `postgres`               |
| `POSTGRES_PASSWORD` | Container env `POSTGRES_PASSWORD`   | `almohot123` → **correct**|
|                 | root `.env`                             | `change-me-to-a-strong-password` → **stale placeholder** |
| Container IP    | `docker inspect`                        | `172.21.0.2`             |
| Internal DNS    | Docker Compose service name             | `db` (Docker network only)|

**Key discovery:** The root `.env` file has a placeholder password (`change-me-to-a-strong-password`), but the running Docker container was started with `almohit123`. The container's environment variables are the source of truth.

---

## 3. Laravel Connection Configuration

### Laravel `.env` (updated)

```
DB_CONNECTION=pgsql
DB_HOST=172.21.0.2
DB_PORT=5432
DB_DATABASE=almohit_hotels_laravel
DB_USERNAME=postgres
DB_PASSWORD=almohit123
```

### Strategy

| Aspect              | Decision                              | Rationale                                      |
|---------------------|---------------------------------------|------------------------------------------------|
| **Host**            | `172.21.0.2` (Docker bridge IP)       | Reachable from host; stable while container exists |
| **Port**            | `5432`                                | Matches PostgreSQL inside container            |
| **Database**        | `almohit_hotels_laravel` (new DB)     | Separate from `almohit_hotels_db` → Django safe |
| **User**            | `postgres`                            | Superuser (same as Django uses)                |
| **Password**        | `almohit123`                          | Retrieved from running container env vars      |

### Why not...

| Rejected approach                        | Reason                                                        |
|------------------------------------------|---------------------------------------------------------------|
| Connect via `localhost:5432`             | That's the **Ubuntu local PostgreSQL 16**, not the Docker one |
| Connect via Docker container name `db`   | Name resolves only inside Docker network, not from host       |
| Publish Docker port to host              | Would require modifying `docker-compose.yml` and restarting the DB container — unnecessary risk |
| Use root `.env` password                 | The `.env` value is a stale placeholder, not the actual password |

---

## 4. Verification Results

### `php artisan migrate:status` (after migrate)

| Migration                                          | Batch | Status |
|----------------------------------------------------|:-----:|:------:|
| `0001_01_01_000000_create_users_table`             |   1   | ✅ Ran |
| `0001_01_01_000001_create_cache_table`             |   1   | ✅ Ran |
| `0001_01_01_000002_create_jobs_table`              |   1   | ✅ Ran |
| `2026_06_23_000000_create_almohit_domain_tables`   |   1   | ✅ Ran |

All **4 migrations** executed successfully against Docker PostgreSQL `almohit_hotels_laravel`.

### Connection test

```sql
SELECT 1 AS connected;
-- Returns: 1
```

✅ Laravel ↔ Docker PostgreSQL connection confirmed.

---

## 5. Safety Confirmation

| Check                                              | Status      |
|----------------------------------------------------|:-----------:|
| Django database (`almohit_hotels_db`) tables       | 35 — ✅ Unchanged |
| Laravel database (`almohit_hotels_laravel`) tables | 34 — ✅ Created separately |
| Django Git HEAD                                    | `0da44e1` — ✅ Unchanged |
| Django Git branch                                  | `cleanup/production-deploy-prep` — ✅ Unchanged |
| Docker PostgreSQL password changed?                | ❌ No — unchanged |
| Docker PostgreSQL data modified?                   | ❌ No — only new `almohit_hotels_laravel` DB created |
| Django `.env` modified?                            | ❌ No |
| `docker-compose.yml` modified?                     | ❌ No |
| Django source files modified?                      | ❌ No |

---

## 6. Exact Connection Values

```
DB_HOST     = 172.21.0.2
DB_PORT     = 5432
DB_DATABASE = almohit_hotels_laravel
DB_USERNAME = postgres
DB_PASSWORD = almohit123
```

> **Note:** The Docker bridge IP (`172.21.0.2`) persists for the container's lifetime. If the container is ever removed and recreated, re-run `docker inspect <db-container> --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'` to get the new IP.
