# 🐳 Production Docker Architecture — Almohit Hotels Laravel Backend

This document details the complete Docker environment and deployment architecture for the **Almohit Hotels Laravel Backend**.

---

## 🏗️ Architecture Overview

The containerized environment consists of 4 isolated services running on a dedicated internal Docker bridge network (`almohit_network`):

1. **`backend`** (`php:8.4-fpm-alpine`): Multi-stage production build running PHP-FPM, pre-configured with required extensions (`pdo_pgsql`, `redis`, `bcmath`, `intl`, `opcache`, `gd`, `zip`).
2. **`nginx`** (`nginx:alpine`): High-performance HTTP server performing SSL/TLS termination, static asset caching, Gzip compression, security header enforcement, and FastCGI proxying to PHP-FPM.
3. **`postgres`** (`postgres:16-alpine`): PostgreSQL relational database server with persistent volume storage (`postgres_data`).
4. **`redis`** (`redis:alpine`): In-memory cache and session management store with persistent volume storage (`redis_data`).

---

## 📋 Prerequisites

* **Docker Engine**: `v24.0.0+`
* **Docker Compose**: `v2.20.0+`

---

## 🚀 Quick Start (Development & Production)

### 1. Clone & Setup Environment
Copy the Docker-ready environment file template:
```bash
cp .env.example .env
```

### 2. Build & Start Containers
Launch all services in detached mode:
```bash
docker compose up --build -d
```

Once launched, verify container health status:
```bash
docker compose ps
```

The API and documentation will be available immediately at:
* 📚 **Swagger API Documentation**: `http://localhost:8020/api/documentation`
* 💚 **System Health Check**: `http://localhost:8020/api/health`
* 🏨 **Properties API**: `http://localhost:8020/api/properties`

---

## 🛑 Stopping & Managing Containers

* **Stop services (preserving data)**:
  ```bash
  docker compose stop
  ```
* **Down services (stop & remove containers)**:
  ```bash
  docker compose down
  ```
* **Down services and remove persistent volumes (destructive)**:
  ```bash
  docker compose down -v
  ```

---

## 🔑 Environment Variables & Configuration

Key Docker environment variables configurable in `.env`:

| Variable | Default Value | Description |
| :--- | :--- | :--- |
| `HTTP_PORT` | `8020` | Host port mapped to Nginx web server. |
| `DB_HOST` | `postgres` | Internal Docker hostname for database service. |
| `DB_PORT` | `5432` | Database port. |
| `DB_DATABASE` | `almohit_hotels` | Database name. |
| `DB_USERNAME` | `postgres` | Database user. |
| `DB_PASSWORD` | `postgres_password` | Database password. |
| `REDIS_HOST` | `redis` | Internal Docker hostname for Redis service. |
| `RUN_MIGRATIONS` | `true` | When `true`, automatically executes database migrations on container boot. |
| `ENABLE_DOCKER_CACHE` | `false` | When `true` (or `APP_ENV=production`), caches config, routes, and views. |

---

## 🛠️ Database Migrations & Artisan Commands

Execute Artisan commands directly inside the running `backend` container:

* **Run Database Migrations**:
  ```bash
  docker compose exec backend php artisan migrate
  ```
* **Seed Database**:
  ```bash
  docker compose exec backend php artisan db:seed
  ```
* **Clear Caches**:
  ```bash
  docker compose exec backend php artisan config:clear
  docker compose exec backend php artisan cache:clear
  ```

---

## 🧪 Running Automated Tests Inside Container

Run the PHPUnit feature test suite inside the container environment to verify runtime integrity:

```bash
docker compose exec backend php artisan test
```

---

## 🔍 Health Checks & Monitoring

Each container includes automated health checks:

* **Backend (PHP-FPM)**: Verifies TCP socket availability on port 9000 (`nc -z 127.0.0.1 9000`).
* **Nginx**: Verifies HTTP response on `http://localhost/nginx-health`.
* **PostgreSQL**: Executes `pg_isready`.
* **Redis**: Executes `redis-cli ping`.

To view container logs in real time:
```bash
docker compose logs -f backend
```

---

## 🏭 Production Deployment Notes

1. **Security**: Ensure strong random passwords for `DB_PASSWORD` and `REDIS_PASSWORD` in your production environment.
2. **Reverse Proxy / SSL**: In production, place a reverse proxy (e.g., Cloudflare, Traefik, AWS ALB) in front of Nginx to terminate HTTPS/SSL certificates on port 443.
3. **Storage Persistence**: The Docker compose configuration defines named volumes (`app_storage`, `postgres_data`, `redis_data`) to ensure persistent file storage across container restarts and redeployments.
