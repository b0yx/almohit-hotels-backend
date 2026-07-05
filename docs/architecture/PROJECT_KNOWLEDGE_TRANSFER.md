# Project Knowledge Transfer — Almohit Hotels Laravel Backend

**Date:** 2026-06-23
**Target Audience:** Senior Laravel Engineer
**Repository:** `almohit_hotels_laravel`

---

## 1. Project Overview

### 1.1 Purpose
RESTful API backend powering the Almohit Hotels booking platform. Serves as the sole data provider for a Next.js frontend (separate repository). Replaces an earlier Django backend — this Laravel version is a port.

### 1.2 Business Domain
Hotel booking / property management. Core entities: hotels (properties), room types, bookings (inquiries & confirmations), guest reviews, hotel services, and multi-role user management.

### 1.3 Main Features
- Email-based signup with OTP verification
- Role-based authentication (customer, staff, admin)
- Hotel/Property CRUD with publish/unpublish/archive workflow
- Room type management with seasonal pricing and availability blocks
- Booking inquiry → confirmation → cancellation flow
- Public guest reviews with staff moderation
- Image upload for hotels, room types, and services
- Subdomain-based hotel context resolution (multi-tenancy)
- Admin: user management, audit logs, contact message handling
- Staff hotel assignment scoping

### 1.4 User Roles
| Role | Identifier | Capabilities |
|------|-----------|--------------|
| **Customer** | `role='customer'` | Browse published hotels, submit reviews, create bookings, manage own profile |
| **Staff** | `role='staff' + is_active=true` | All customer + manage assigned hotels, bookings, reviews, rooms, services |
| **Admin** | `is_staff=true` or `role='admin'` | All permissions + user management, role changes, audit logs, system-wide access |

### 1.5 Authentication System
**Custom token-based auth** (NOT Laravel Sanctum/Passport).

- Login generates a 64-char random string (`Str::random(64)`)
- SHA-256 hash stored in `api_tokens` table
- Client receives plaintext token in login response (`access` field)
- All authenticated requests use `Authorization: Bearer <token>` header
- Token validated by `AuthenticateApiToken` middleware (no rejection — sets user if valid, proceeds anonymous if not)
- Logout deletes the token record

**Signup flow:**
1. `POST /api/auth/signup/` — creates user (inactive), generates 6-digit OTP
2. `POST /api/auth/verify-otp/` — verifies OTP, activates user
3. `POST /api/auth/login/` — returns token

In `APP_ENV=local`/`testing`, the OTP code is returned in signup response as `debug_code`. The default dev OTP is `123456`.

### 1.6 APIs Exposed
~70+ API endpoints under `/api/` prefix covering:
- Auth (signup, login, OTP, profile, admin management)
- Hotels/Properties (CRUD, publish, archive, readiness, workspace, availability, rates)
- Rooms (room types, prices, availability blocks, amenities)
- Bookings (inquiry, confirm, cancel, calendar)
- Reviews (public submission, staff moderation, summary stats)
- Images (upload for hotels, rooms, services)
- Admin utilities (users, audit logs, contact messages)
- Health check

### 1.7 Admin Capabilities
- List/create/update/delete users
- Activate/deactivate users
- Change user roles (`customer` ↔ `staff` ↔ `admin`)
- Reset user passwords
- View audit logs
- Manage contact messages
- System-wide hotel access (not scoped to assigned hotels)

---

## 2. Architecture Analysis

### 2.1 Laravel Version
**Laravel 13.8+** (confirmed by `composer.json`: `"laravel/framework": "^13.8"`). This is the latest Laravel version as of 2026.

### 2.2 PHP Version Requirements
**PHP 8.4+** (`composer.json`: `"php": "^8.4"`, platform config: `"php": "8.4"`)

### 2.3 Database Engine
**PostgreSQL 15+** (`DB_CONNECTION=pgsql` in `.env.example`, `config/database.php` supports SQLite/MySQL/MariaDB/PostgreSQL/SQL Server but only PostgreSQL is used in practice)

### 2.4 Queue System
**Database-driven queue** (`QUEUE_CONNECTION=database` default). Uses the `jobs` table. However, **no queue jobs are defined** — no Job classes exist in the codebase. The queue system is configured but unused.

### 2.5 Cache System
**Database-driven cache** (`CACHE_STORE=database` default). Uses the `cache` and `cache_locks` tables. No Redis dependency in production default.

### 2.6 Storage Strategy
- **Local development:** `FILESYSTEM_DISK=local`, images stored in `storage/app/public/`
- **Public access:** `public/storage` → `storage/app/public` symlink; custom `public/media` symlink also created
- **Production:** S3-compatible storage configured via `config/filesystems.php` (`s3` and `cloud` disks)
- Image paths stored as `/media/{dir}/{uuid}.{ext}` and served through symlink

### 2.7 Docker Usage
No Dockerfile for the Laravel app itself. PostgreSQL runs in Docker via `docker-compose.yml` (located in parent directory):
- `postgres:17` image
- Database: `almohit_hotels_laravel`
- Port: `5432` (container) / `5433` (host-mapped)
- Auth: `postgres` / `almohit123`

### 2.8 Railway Compatibility
✅ **Railway-ready.** `railway.json` configures:
- Build: `composer install --no-dev --optimize-autoloader`
- Healthcheck: `GET /api/health`
- Nixpacks builder (auto-detects Laravel)
- Requires `NIXPACKS_PHP_ROOT_DIR=/app/public` environment variable

### 2.9 Environment Variables Required
**Required:**
| Variable | Purpose |
|----------|---------|
| `APP_KEY` | 32-char base64 encryption key |
| `APP_ENV` | `production` / `local` / `testing` |
| `APP_DEBUG` | `true` / `false` |
| `APP_URL` | Application base URL |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Database host |
| `DB_PORT` | Database port |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

**Production-essential:**
| Variable | Default | Notes |
|----------|---------|-------|
| `CACHE_STORE` | `database` | Switch to `redis` for performance |
| `SESSION_DRIVER` | `array` | API-only; no sessions needed |
| `QUEUE_CONNECTION` | `database` | For async jobs |
| `FILESYSTEM_DISK` | `local` | Switch to `s3` in production |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:3000` | Frontend URL |
| `NIXPACKS_PHP_ROOT_DIR` | — | Railway requirement: `/app/public` |
| `PUBLIC_BASE_DOMAIN` | `almohit.com` | Subdomain hotel resolution |

---

## 3. Folder Structure Analysis

### 3.1 `app/` — Application Core

**`app/Http/Controllers/Api/` — 6 Controllers:**

| Controller | Responsibility | Lines |
|-----------|---------------|-------|
| `AuthController` | Signup, login, OTP, profile, admin user management | 208 |
| `HotelController` | Property CRUD, publish/archive, workspace, availability, rates | 240 |
| `BookingController` | Booking CRUD, inquiry, confirm, cancel | 176 |
| `ReviewController` | Review CRUD, property reviews, summary stats | 97 |
| `CrudController` | Generic CRUD for 10+ models with auth, filtering, aliasing | 221 |
| `ImageUploadController` | Multi-endpoint image upload for hotels, rooms, services | 199 |
| `Controller` (base) | Abstract base class | 8 |

**`app/Http/Middleware/` — 3 Middleware:**

| Middleware | Purpose |
|-----------|---------|
| `AuthenticateApiToken` | Validates Bearer/Token auth header, SHA-256 matching against `api_tokens` table |
| `RequireRole` | Role-based gate: `admin`, `staff`, `customer` |
| `ResolvePublicHotel` | Extracts subdomain from host header or `X-Hotel-Subdomain` header; sets `public_hotel` on request |

**`app/Models/` — 23 Models:**

| Model | Table | Relations |
|-------|-------|-----------|
| `User` | `users` | `apiTokens`, `assignedHotels` (belongsToMany) |
| `ApiToken` | `api_tokens` | `user` (belongsTo) |
| `EmailOTP` | `email_otps` | None defined |
| `Hotel` | `hotels` | `amenities` (BTM), `assignedStaff` (BTM), `images`, `roomTypes`, `services`, `reviews`, `policy`, `socialMedia`, `contacts`, `setupStatus` |
| `HotelAmenity` | `hotel_amenities` | None |
| `HotelImage` | `hotel_images` | `hotel` (belongsTo) |
| `HotelPolicy` | `hotel_policies` | `hotel` (belongsTo) |
| `HotelService` | `hotel_services` | `hotel`, `category`, `images` |
| `RoomType` | `room_types` | `hotel`, `images`, `prices` |
| `RoomPrice` | `room_prices` | None |
| `RoomTypeImage` | `room_type_images` | `roomType` |
| `AvailabilityBlock` | `availability_blocks` | None |
| `BookingInquiry` | `booking_inquiries` | `hotel`, `roomType`, `guests` |
| `BookingGuest` | `booking_guests` | None |
| `Review` | `reviews` | None |
| `ServiceCategory` | `service_categories` | None |
| `ServiceImage` | `service_images` | `service` |
| `PropertyContacts` | `property_contacts` | `hotel` |
| `PropertySocialMedia` | `property_social_media` | `hotel` |
| `PropertySetupStatus` | `property_setup_statuses` | `hotel` |
| `ChannelManagerConnection` | `channel_manager_connections` | None |
| `ContactMessage` | `contact_messages` | None |
| `AuditLog` | `audit_logs` | None |

**`app/Providers/` — 1 Provider:**
- `AppServiceProvider` — Empty (no custom registrations or boot logic)

**`app/Support/` — 1 Support Class:**
- `CompatResponse` — Response formatter ensuring frontend-compatible JSON shapes (DRF-compatible pagination, snake_case keys, field aliases)

### 3.2 `bootstrap/`
- `app.php` — Application config: middleware aliases, API exception JSON rendering
- `providers.php` — Single provider registered
- `cache/.gitignore` — Keep empty directory

### 3.3 `config/` — 12 Files
Standard Laravel config with notable customizations:
- `almohit.php` — Custom app config (domains, frontend URLs, reserved subdomains)
- `cors.php` — CORS with env-based origins, custom headers (`X-Hotel-Subdomain`)
- `session.php` — Default `database` driver (API uses `array` in production)
- `cache.php` — Default `database` driver
- `queue.php` — Default `database` driver

### 3.4 `database/`
- `migrations/` — 5 migration files (3 Laravel framework, 2 custom domain)
- `factories/UserFactory.php` — User factory for tests
- `seeders/DatabaseSeeder.php` — Seeds 4 demo accounts

### 3.5 `public/`
Standard Laravel web root: `index.php`, `.htaccess`, `favicon.ico`, `robots.txt`, empty `media/` dir

### 3.6 `resources/`
Minimal: `css/app.css`, `js/app.js`, `views/welcome.blade.php` (default Laravel welcome page — not used, API-only)

### 3.7 `routes/`
- `api.php` — All ~70+ API endpoints (the core of the application)
- `web.php` — Single route to welcome view
- `console.php` — Default inspire command

### 3.8 `storage/`
Standard Laravel storage directory structure with `.gitignore` files preserving empty directories.

### 3.9 `tests/`
- `TestCase.php` — Base test case (empty)
- `Feature/ApiCompatibilityTest.php` — 4 tests verifying Django-compatible response shapes
- `Feature/ExampleTest.php`, `Unit/ExampleTest.php` — Default Laravel examples

### 3.10 Notable Design Patterns
- **Single CrudController pattern** — One controller (`CrudController`) handles CRUD for 10+ models via service container binding in routes. Route name prefix determines model class.
- **CompatResponse formatter** — All responses funnel through `CompatResponse::item()`/`CompatResponse::page()` which uses `match` on model type to dispatch to type-specific formatters.
- **ImageUploadController** — Single controller handling 3 distinct image types by inspecting the URL segment (`property-images`, `room-type-images`, `service-images`).

### 3.11 Missing Framework Features
- **Form Requests** — Zero Form Request classes exist. All validation is inline in controllers.
- **Policies** — Zero Policy classes. Authorization is handled by `CrudController::authorizeAction()` and `RequireRole` middleware.
- **Gates** — Zero Gate definitions.
- **Events** — Zero Event classes.
- **Listeners** — Zero Listener classes.
- **Jobs** — Zero Job classes.
- **Notifications** — Zero Notification classes (not even the OTP email).
- **Scheduled Tasks** — Zero entries in `routes/console.php` beyond the default `inspire` command.
- **Mailables** — Zero Mailable classes.

---

## 4. Laravel Analysis

### 4.1 Controllers (6)
| Controller | Extends | Endpoints |
|-----------|---------|-----------|
| `AuthController` | `Controller` | `signup`, `verifyOtp`, `resendOtp`, `login`, `adminLogin`, `customerLogin`, `me`, `updateMe`, `logout`, `activateUser`, `deactivateUser`, `changeUserRole`, `resetUserPassword` |
| `HotelController` | `CrudController` | Overrides `index`, `store`; adds `publish`, `unpublish`, `archive`, `unarchive`, `readiness`, `setupStatus`, `autosave`, `workspace`, `roomsSearch`, `availability`, `rates`, `publicContext` |
| `BookingController` | `CrudController` | Overrides `index`, `store`; adds `inquiry`, `confirm`, `cancel`, `calendar` |
| `ReviewController` | `CrudController` | Adds `propertyReviews` (handles GET + POST), `summary` |
| `CrudController` | `Controller` | Generic `index`, `store`, `show`, `update`, `destroy` for 10+ models |
| `ImageUploadController` | `Controller` | `index`, `store`, `show`, `update`, `destroy` for 3 image types |

### 4.2 Models (23)
**Relationships:**
- `User` `hasMany` → `ApiToken`
- `User` `belongsToMany` → `Hotel` (via `hotel_user_assignments`)
- `Hotel` `belongsToMany` → `HotelAmenity` (via `hotel_amenity_hotel`)
- `Hotel` `belongsToMany` → `User` (via `hotel_user_assignments`)
- `Hotel` `hasMany` → `HotelImage`, `RoomType`, `HotelService`, `Review`
- `Hotel` `hasOne` → `HotelPolicy`, `PropertySocialMedia`, `PropertyContacts`, `PropertySetupStatus`
- `RoomType` `belongsTo` → `Hotel`
- `RoomType` `hasMany` → `RoomTypeImage`, `RoomPrice`
- `BookingInquiry` `belongsTo` → `Hotel`, `RoomType`
- `BookingInquiry` `hasMany` → `BookingGuest`
- `HotelService` `belongsTo` → `Hotel`, `ServiceCategory`
- `HotelService` `hasMany` → `ServiceImage`
- All image models `belongsTo` their respective parent

### 4.3 Middleware (3)
Registered in `bootstrap/app.php` with aliases:
- `api.token` → `AuthenticateApiToken`
- `tenant.context` → `ResolvePublicHotel`
- `role` → `RequireRole`

Applied globally to all API routes except `health` endpoint.

### 4.4 Service Providers (1)
`AppServiceProvider` — Empty. No custom services, no repository bindings, no event registrations.

### 4.5 Custom Services
None. All business logic lives in controllers. There is no Service/Repository layer.

### 4.6 Form Requests
None. Validation is inline in every controller method via `$request->validate([...])`.

### 4.7 Policies and Gates
None. Authorization uses:
1. `CrudController::authorizeAction()` — access map based on model class
2. `RequireRole` middleware — role checks on route groups

### 4.8 Events, Listeners, Jobs, Scheduled Tasks
**None found.** The application is fully synchronous with no async processing.

---

## 5. Database Analysis

### 5.1 Complete Table Listing (34 tables)

**Laravel Framework Tables (3 migrations):**
| Table | Columns | Purpose |
|-------|---------|---------|
| `users` | `id`, `full_name`, `email` (unique), `phone`, `role` (idx), `is_active`, `email_verified`, `is_staff`, `is_superuser`, `failed_login_attempts`, `last_failed_login`, `lockout_until`, `last_login`, `password`, `remember_token`, `created_at`, `updated_at` | User accounts; `full_name` replaces Laravel's default `name` |
| `password_reset_tokens` | `email` (PK), `token`, `created_at` | Standard Laravel |
| `sessions` | `id` (PK), `user_id` (idx), `ip_address`, `user_agent`, `payload`, `last_activity` (idx) | Standard Laravel |
| `cache` | `key` (PK), `value`, `expiration` | Standard Laravel |
| `cache_locks` | `key` (PK), `owner`, `expiration` | Standard Laravel |
| `jobs` | `id`, `queue` (idx), `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` | Standard Laravel |
| `job_batches` | `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at` | Standard Laravel |
| `failed_jobs` | `id`, `uuid` (unique), `connection`, `queue`, `payload`, `exception`, `failed_at` | Standard Laravel |

**Custom Domain Tables (migration `2026_06_23_000000`):**

| Table | Foreign Keys | Purpose |
|-------|-------------|---------|
| `api_tokens` | `user_id` → `users` | Token-based auth storage |
| `email_otps` | `user_id` → `users` | OTP verification codes |
| `hotel_amenities` | — | Amenity definitions (WiFi, pool, etc.) |
| `hotels` | `owner_id` → `users`, `created_by` → `users`, `updated_by` → `users` | Core property entity |
| `hotel_amenity_hotel` | `hotel_id`, `hotel_amenity_id` (composite PK) | Pivot: hotel ↔ amenity |
| `hotel_user_assignments` | `user_id`, `hotel_id` (composite PK) | Pivot: staff ↔ hotel |
| `hotel_images` | `hotel_id` → `hotels` | Property images |
| `hotel_policies` | `hotel_id` (unique) → `hotels` | Per-property policies |
| `property_social_media` | `hotel_id` (unique) → `hotels` | Social media links |
| `property_contacts` | `hotel_id` (unique) → `hotels` | Contact information |
| `property_setup_statuses` | `hotel_id` (unique) → `hotels` | Onboarding progress |
| `channel_manager_connections` | `hotel_id` (unique) → `hotels` | OTA integration (disabled for MVP) |
| `room_types` | `hotel_id` → `hotels` | Room categories |
| `hotel_amenity_room_type` | `room_type_id`, `hotel_amenity_id` (composite PK) | Pivot: room ↔ amenity |
| `availability_blocks` | `room_type_id` → `room_types` | Blocked dates |
| `room_type_images` | `room_type_id` → `room_types` | Room type images |
| `room_prices` | `room_type_id` → `room_types` | Seasonal pricing |
| `service_categories` | — | Service categories (spa, dining) |
| `hotel_services` | `hotel_id` → `hotels`, `service_category_id` → `service_categories` | Hotel services |
| `service_images` | `hotel_service_id` → `hotel_services` | Service images |
| `booking_inquiries` | `customer_id` → `users`, `hotel_id` → `hotels`, `room_type_id` → `room_types` | Booking requests |
| `booking_guests` | `booking_inquiry_id` → `booking_inquiries` | Guest details per booking |
| `reviews` | `hotel_id` → `hotels`, `user_id` → `users` | Guest reviews |
| `audit_logs` | `actor_id` → `users` | Admin action audit trail |
| `contact_messages` | `hotel_id` → `hotels`, `handled_by` → `users` | Contact form submissions |

**Performance Indexes (migration `2026_06_23_000001`):**
Additional indexes on: `reviews` (hotel_id+is_active), `booking_inquiries` (status, customer_id+status, hotel_id+status), `room_type_images` (room_type_id+is_active), `service_images` (hotel_service_id+is_active), `contact_messages` (status), `booking_guests` (booking_inquiry_id), `hotels` (country+city), `room_types` (hotel_id+is_active), `hotel_services` (hotel_id+is_active+is_featured)

### 5.2 Business Flow Behind Schema
1. **Hotel onboarding:** `hotels` → `property_setup_statuses` tracks wizard completion → `hotel_policies`, `property_contacts`, `property_social_media` are filled → `hotel_images` uploaded → `hotel_amenities` linked → `room_types` created with `room_prices` and `room_type_images` → hotel is published
2. **Booking:** Guest browses available hotels → checks `availability_blocks` per room type → submits `booking_inquiry` → staff confirms (status='confirmed') → `booking_guests` list submitted → guest cancels (status='cancelled')
3. **Review:** Guest posts `review` with rating + category scores → staff moderates (is_active flag) → aggregated stats computed in `summary` endpoint
4. **Admin:** Admin manages users via dedicated auth endpoints, views `audit_logs`, handles `contact_messages`

---

## 6. API Analysis

### 6.1 Complete Endpoint List

**Health & Public**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET | `/api/health/` | No | Health check (DB + cache) |
| GET | `/api/public/hotel-context/` | No | Current subdomain hotel context |

**Auth — Public**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| POST | `/api/auth/signup/` | No | Create account |
| POST | `/api/auth/verify-otp/` | No | Verify email with OTP |
| POST | `/api/auth/resend-otp/` | No | Resend verification code |
| POST | `/api/auth/login/` | No | Login (any role) |
| POST | `/api/auth/admin/login/` | No | Admin-only login (rejects non-admin) |
| POST | `/api/auth/customer/login/` | No | Customer-only login (rejects admin) |

**Auth — Authenticated**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET | `/api/auth/me/` | Yes | Current user profile |
| PATCH | `/api/auth/me/` | Yes | Update profile |
| POST | `/api/auth/logout/` | Yes | Logout (delete token) |

**Auth — Admin Only (`role:admin`)**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET/POST | `/api/auth/users/` | Admin | List/create users |
| GET/PUT/PATCH/DELETE | `/api/auth/users/{id}/` | Admin | Show/update/delete user |
| GET/POST | `/api/auth/admins/` | Admin | List/create admins |
| POST | `/api/auth/users/{id}/activate/` | Admin | Activate user |
| POST | `/api/auth/users/{id}/deactivate/` | Admin | Deactivate user |
| POST | `/api/auth/users/{id}/change-role/` | Admin | Change user role |
| POST | `/api/auth/users/{id}/reset-password/` | Admin | Reset user password |

**Properties (Hotels)**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET | `/api/properties/available/` | Public | List published hotels |
| GET/POST | `/api/properties/` | Mixed | List/create properties |
| GET/PUT/PATCH/DELETE | `/api/properties/{id}/` | Mixed | Show/update/delete property |
| POST | `/api/properties/{id}/publish/` | Staff | Publish property |
| POST | `/api/properties/{id}/unpublish/` | Staff | Unpublish → draft |
| POST | `/api/properties/{id}/archive/` | Staff | Archive property |
| POST | `/api/properties/{id}/unarchive/` | Staff | Unarchive → draft |
| GET | `/api/properties/{id}/readiness/` | Staff | Check publish readiness |
| GET | `/api/properties/{id}/setup-status/` | Staff | Onboarding progress |
| PATCH | `/api/properties/{id}/autosave/` | Staff | Autosave + progress |
| GET | `/api/properties/{id}/workspace/` | Staff | Owner workspace data |
| GET | `/api/properties/{id}/rooms/search/` | Public | Search active room types |
| GET | `/api/properties/{id}/availability/` | Public | Room availability by dates |
| GET | `/api/properties/{id}/rates/` | Public | Room rates with seasonals |
| GET/POST | `/api/properties/{property}/reviews/` | Public | List/submit reviews |
| GET | `/api/properties/{property}/reviews/summary/` | Public | Rating summary & breakdown |

**Bookings**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET/POST | `/api/bookings/` | Yes | List/create bookings |
| GET/PUT/PATCH/DELETE | `/api/bookings/{id}/` | Yes | Show/update/delete booking |
| POST | `/api/bookings/inquiry/` | No | Submit public inquiry |
| POST | `/api/bookings/confirm/` | Staff | Confirm booking |
| POST | `/api/bookings/{id}/cancel/` | Yes | Cancel booking |

**Reviews**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET/POST | `/api/reviews/` | Staff | List/create all reviews |
| GET/PUT/PATCH/DELETE | `/api/reviews/{id}/` | Staff | Show/update/delete review |

**CRUD Resources (via CrudController)**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET/POST | `/api/property-amenities/` | Mixed | List/create amenities |
| GET/PUT/PATCH/DELETE | `/api/property-amenities/{id}/` | Mixed | CRUD amenity |
| GET/POST | `/api/room-types/` | Mixed | List/create room types |
| GET/PUT/PATCH/DELETE | `/api/room-types/{id}/` | Mixed | CRUD room type |
| GET | `/api/room-types/{id}/rates/` | Mixed | Room rates (returns `[]`) |
| GET/POST | `/api/room-prices/` | Mixed | List/create room prices |
| GET/PUT/PATCH/DELETE | `/api/room-prices/{id}/` | Mixed | CRUD room price |
| GET/POST | `/api/room-amenities/` | Mixed | List/create room amenity links |
| GET/PUT/PATCH/DELETE | `/api/room-amenities/{id}/` | Mixed | CRUD room amenity |
| GET/POST | `/api/availability-blocks/` | Mixed | List/create availability blocks |
| GET/PUT/PATCH/DELETE | `/api/availability-blocks/{id}/` | Mixed | CRUD availability block |
| GET/POST | `/api/service-categories/` | Mixed | List/create service categories |
| GET/PUT/PATCH/DELETE | `/api/service-categories/{id}/` | Mixed | CRUD service category |
| GET/POST | `/api/property-services/` | Mixed | List/create property services |
| GET/PUT/PATCH/DELETE | `/api/property-services/{id}/` | Mixed | CRUD property service |
| GET/POST | `/api/audit-logs/` | Admin | List audit logs |
| GET | `/api/audit-logs/{id}/` | Admin | Show audit log |
| GET/POST | `/api/contact-messages/` | Staff | List/create contact messages |
| GET/PUT/PATCH/DELETE | `/api/contact-messages/{id}/` | Staff | CRUD contact message |

**Image Uploads (via ImageUploadController)**
| Method | URL | Auth | Purpose |
|--------|-----|------|---------|
| GET/POST | `/api/property-images/` | Staff | List/upload property images |
| GET/PATCH/DELETE | `/api/property-images/{id}/` | Staff | Show/update/delete image |
| GET/POST | `/api/room-type-images/` | Staff | List/upload room type images |
| GET/PATCH/DELETE | `/api/room-type-images/{id}/` | Staff | Show/update/delete image |
| GET/POST | `/api/service-images/` | Staff | List/upload service images |
| GET/PATCH/DELETE | `/api/service-images/{id}/` | Staff | Show/update/delete image |

### 6.2 Request/Response Patterns
- **Pagination:** DRF-compatible `{ count, next, previous, results }`
- **Errors:** `{ detail: string\|object, code: string }`
- **Auth:** `Authorization: Bearer <token>` header
- **Content-Type:** JSON (except image uploads: `multipart/form-data`)
- **Trailing slash required** on all endpoints
- **snake_case** response keys

---

## 7. Frontend Analysis

### 7.1 Frontend Stack
Not served by this repository. The frontend is a **separate Next.js application** consuming this API.

### 7.2 Blade/Livewire/Inertia/Vue/React/Alpine
- **Blade:** One welcome view (`resources/views/welcome.blade.php`) — not used in practice
- **Livewire:** Not used
- **Inertia:** Not used
- **Vue:** Not used
- **React:** Not used
- **Alpine.js:** Not used
- **Vite:** Configured (`vite.config.js`, `package.json`) for asset building but no frontend assets are developed in this repository

### 7.3 Frontend Documentation
Comprehensive frontend handoff documentation exists at:
- `docs/handoff/FRONTEND_HANDOFF.md` (523 lines covering auth, booking, image upload, pagination, error handling, response shapes)
- `docs/handoff/HANDOFF_PACKAGE.md` (handoff summary)
- `docs/handoff/NEXTJS.env.example` (Next.js environment template)
- `docs/api/OPENAPI_SPEC.yaml` / `.json` (full OpenAPI spec)
- `docs/api/POSTMAN_COLLECTION.json` (84 endpoints)

---

## 8. Railway Deployment Analysis

### 8.1 Railway-Ready Status
✅ **Partially ready.**

**Present:**
- `railway.json` with build/deploy config
- Health check endpoint (`/api/health/`)
- Nixpacks builder auto-detection

**Missing:**
- No `Procfile` for release commands (migrations)
- No runtime/start command configuration (Nixpacks handles this, but undocumented)
- No environment variable documentation for Railway-specific settings

### 8.2 Build Command
`composer install --no-dev --optimize-autoloader`

### 8.3 Start Command
Nixpacks auto-generates Nginx + PHP-FPM config. Requires:
- `NIXPACKS_PHP_ROOT_DIR=/app/public` in Railway env vars

### 8.4 Required Environment Variables on Railway
| Variable | Notes |
|----------|-------|
| `APP_KEY` | Must generate locally: `php artisan key:generate --show` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Railway-generated URL |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Railway-provided PG host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Railway-provided |
| `DB_USERNAME` | Railway-provided |
| `DB_PASSWORD` | Railway-provided |
| `CACHE_STORE` | `database` (or `redis` if provisioned) |
| `SESSION_DRIVER` | `array` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `s3` (for production images) |
| `NIXPACKS_PHP_ROOT_DIR` | `/app/public` |

### 8.5 Migration Requirements
- Must run `php artisan migrate --force` during release phase
- No current `Procfile` automates this
- Suggested `Procfile`: `release: php artisan migrate --force`

### 8.6 Queue Requirements
- If using queue: add a Railway service running `php artisan queue:work`
- No jobs currently exist, so queue is not strictly required

### 8.7 Storage Requirements
- Local storage works on Railway (ephemeral) — images will be lost on restart
- Must configure S3 for persistent image storage in production
- Set `FILESYSTEM_DISK=s3` and provide `AWS_*` env vars

### 8.8 Potential Deployment Issues
1. **No Procfile** — migrations won't auto-run on deploy
2. **APP_KEY** must be manually generated and set
3. **Symlinks** — `storage:link` must run on each deploy (or in release phase)
4. **Image storage** — local storage is ephemeral on Railway
5. **Health check path** — `/api/health` (without trailing slash) — while routes require trailing slash, Railway health checks typically don't include them
6. **Database URL** — Railway provides `DATABASE_URL` but Laravel config uses individual `DB_*` vars
7. **Nixpacks PHP root** — must manually set `NIXPACKS_PHP_ROOT_DIR=/app/public`

---

## 9. Security Audit

### 9.1 Hardcoded Secrets
- **None found** — all credentials use `env()` from `.gitignore`-d `.env`

### 9.2 Exposed Credentials
- Demo account passwords (see `README.md` §Seed Data): `adminpass123`, `staffpass123`, `customerpass123`, `testpass123`
- Test fixture passwords in `tests/Feature/ApiCompatibilityTest.php`: `password123`
- Example passwords in `README.md` and `FRONTEND_HANDOFF.md`: `SecurePass123!`, `adminpass123`
- **Risk: Low** — these are clearly demo/test credentials and `db:seed` must be run to create them

### 9.3 Debug Mode Issues
- `.env.example` has `APP_DEBUG=true` — correct for local development
- `APP_ENV=local` returns OTP `debug_code` in signup response — acceptable for development

### 9.4 APP_KEY Issues
- `.env.example` has `APP_KEY=` empty — user must generate
- No `.env` committed (properly gitignored)

### 9.5 CORS Issues
- `config/cors.php` allows origins via `CORS_ALLOWED_ORIGINS` env var
- Default: `http://localhost:3000`
- Custom header `X-Hotel-Subdomain` allowed
- No wildcard origins — correctly scoped

### 9.6 Authorization Weaknesses
1. **Token auth is optional** — `AuthenticateApiToken` does NOT reject unauthenticated requests. It only sets `$request->user()` if a valid token is found. Controllers must check `$request->user()` manually. Some endpoints (like `availability`, `rates`, `rooms/search`) rely on CrudController scope filtering rather than explicit auth checks.
2. **No rate limiting** — auth endpoints lack rate limiting, making them vulnerable to brute force
3. **No CSRF protection** — acceptable for token-based API
4. **Role checking is lax** — `isAdmin()` returns `true` if either `is_staff=true` OR `role='admin'`. This means any user with `is_staff=true` gets admin privileges.
5. **`forceFill` usage** — `HotelController::publish()`, `unpublish()`, `archive()`, `unarchive()` use `forceFill` which bypasses mass-assignment protection

### 9.7 Validation Weaknesses
1. **No HTML/script sanitization** — user inputs like `comment`, `message`, `description` are stored as-is (potential XSS)
2. **Inconsistent validation** — `inquiry` endpoint has full validation; `store` on same model has minimal validation
3. **No `max` on string fields** — several `string` validation rules lack `max:N` to limit input length
4. **Extra bed price overflow** — `extra_bed_price` is `Decimal(10,2)` with no validation limit beyond type
5. **OTP attempt tracking** — `attempt_count` column exists but is never incremented or checked in `verifyOtp()`

---

## 10. Production Readiness Audit

### 10.1 Architecture Score: 6/10
| Strength | Weakness |
|----------|----------|
| Clean controller separation | No Service/Repository layer |
| DRY CrudController pattern | Business logic mixed in controllers |
| CompatResponse ensures consistent output | No Form Requests for validation |
| Middleware stack is clean | No Events/Listeners/Jobs/Notifications |
| Single-responsibility routes | Inline validation everywhere |

### 10.2 Security Score: 7/10
| Strength | Weakness |
|----------|----------|
| No hardcoded secrets | No rate limiting on auth |
| Proper CORS configuration | OTP attempts not tracked |
| `.env` properly gitignored | `forceFill` bypasses protection |
| Token hashing (SHA-256) | XSS vulnerability in user content |
| | No CSRF (acceptable for API) |

### 10.3 Scalability Score: 5/10
| Strength | Weakness |
|----------|----------|
| Database-driven queue available | No jobs/async processing defined |
| Pagination on all list endpoints | No caching strategy on read-heavy endpoints |
| Indexes on common query patterns | No Redis for cache/queue in default config |
| | No read replicas configuration |
| | Synchronous image upload (blocks request) |

### 10.4 Maintainability Score: 6/10
| Strength | Weakness |
|----------|----------|
| Consistent response format | Zero tests for business logic (only 4 compatibility tests) |
| Clean folder structure | No type hints on many model attributes |
| Self-documenting route structure | No API resource classes |
| Good middleware separation | No Service/Repository layer |
| | Missing Form Requests |

### 10.5 Railway Readiness Score: 5/10
| Strength | Weakness |
|----------|----------|
| `railway.json` configured | No `Procfile` for release commands |
| Health check endpoint exists | Nixpacks root dir not auto-detected |
| | Migrations won't auto-run |
| | Storage link required on each deploy |
| | No env var documentation for Railway |
| | `DATABASE_URL` vs `DB_*` mismatch |

---

## 11. Change Detection

### 11.1 What Was Modified (vs Standard Laravel)

| File | Change | Why |
|------|--------|-----|
| `users` migration | `name` → `full_name`, added `role`, `is_staff`, `is_superuser`, `is_active`, `email_verified`, `failed_login_attempts`, `lockout_until`, `last_login` fields | Django compatibility — original database was ported from Django. The role/permission system replaces Laravel's default `$fillable` + `$hidden` approach. |
| `bootstrap/app.php` | Custom middleware aliases, API JSON exception rendering | No Sanctum/Passport — custom token auth. All API errors must return JSON, not HTML. |
| `config/database.php` | Default connection changed from `mysql` to `sqlite` in framework default — `.env` overrides to `pgsql` | PostgreSQL for production compatibility. |
| `config/session.php` | `driver` default changed from `file` to `database` | API is stateless but wants session persistence for admin web views. |
| `.env.example` | `DB_CONNECTION=pgsql`, custom frontend URL vars, `CORS_ALLOWED_ORIGINS` | PostgreSQL project, cross-origin frontend. |

### 11.2 What Was Added

| Addition | Location | Why |
|----------|----------|-----|
| `CrudController` | `app/Http/Controllers/Api/` | Generic CRUD to reduce boilerplate for 10+ resource endpoints |
| `CompatResponse` | `app/Support/` | Ensure response shapes match what the frontend expects (Django DRF-compatible) |
| `AuthenticateApiToken` middleware | `app/Http/Middleware/` | Custom token auth instead of Sanctum |
| `ResolvePublicHotel` middleware | `app/Http/Middleware/` | Subdomain-based hotel resolution (multi-tenancy) |
| `RequireRole` middleware | `app/Http/Middleware/` | Role-based authorization |
| `ImageUploadController` | `app/Http/Controllers/Api/` | Single controller for 3 image types |
| `config/almohit.php` | `config/` | App-specific config (domains, frontend URLs) |
| `config/cors.php` | `config/` | Custom CORS with X-Hotel-Subdomain header |
| `railway.json` | Root | Railway deployment config |

### 11.3 What Was Removed (vs Standard Laravel)
- `app/Http/Controllers/Auth/` — All default auth controllers removed (replaced by custom `AuthController`)
- Sanctum/Passport packages — Not installed; custom token auth used instead
- `resources/views/` — All Blade views except welcome page (this is an API-only app)
- `lang/` — Localization files not present
- `database/migrations/0001_01_01_000000_create_users_table.php` — Modified from default (custom columns)
- `routes/web.php` — Stripped to single welcome route

### 11.4 Custom Architecture Decisions
1. **Route-name-based model resolution** — CrudController determines model class by matching route name prefix against a map. This is unconventional and fragile — route name changes break the binding.
2. **Segment-based image routing** — ImageUploadController inspects `request()->segment(2)` to determine which model/table to use. Works but couples controller to URL structure.
3. **CompatResponse type dispatch** — Uses `match (true)` with `instanceof` checks. Clean pattern but creates a single point of knowledge for all response shapes.
4. **Empty AppServiceProvider** — No boot/register logic. All configuration is in `bootstrap/app.php` and config files.
5. **No Mailable/Notification classes** — OTP verification is stored-only; no actual email sending implementation despite mail config being present.

### 11.5 Why These Changes Were Made
The project is a **Django-to-Laravel port**. The schema, API response shapes, and auth system were designed to match the existing Django API's contract so the Next.js frontend could switch backends without changes. Key indicators:
- `CompatResponse` produces Django REST Framework compatible pagination `{ count, next, previous, results }`
- `snake_case` response keys (Laravel defaults to `snake_case` but the explicit mapping confirms deliberate Django compatibility)
- Custom `is_staff`/`is_superuser` fields mirror Django's user model
- `audit_logs` table structure mirrors Django's `LogEntry`
- Demo accounts use same emails as the Django version

---

## 12. Environment Analysis

### 12.1 `.env.example`
- PostgreSQL connection defaults
- Custom app config (domains, frontend URLs)
- Session/cache/queue default to database driver
- AWS S3 config stubs
- Mail config stubs

### 12.2 Docker Configuration
**No Dockerfile for the Laravel app.** Only PostgreSQL via `docker-compose.yml`:
```yaml
db:
  image: postgres:17
  ports: ["5433:5432"]
  environment:
    POSTGRES_DB: almohit_hotels_db
    POSTGRES_USER: postgres
    POSTGRES_PASSWORD: almohit123
```

The Docker Compose file also defines a Django backend and Next.js frontend — this Laravel project is meant to be run locally with `php artisan serve`, not in Docker.

### 12.3 Nginx Configuration
Nginx config is auto-generated by Nixpacks on Railway. No custom nginx config exists in the repository.

### 12.4 Supervisor Configuration
Not present. Queue workers would need to be configured manually or via Railway process types.

### 12.5 Queue Configuration
- Driver: `database` (default)
- Table: `jobs`
- No queue worker config provided
- No job classes defined

### 12.6 Current Deployment Workflow
1. Clone/pull repository
2. `composer install`
3. `cp .env.example .env` → edit credentials
4. `php artisan key:generate`
5. `php artisan storage:link`
6. `php artisan migrate`
7. `php artisan db:seed` (optional)
8. `php artisan serve` (local) or push to Railway

---

## 13. Developer Handover

### 13.1 How to Run Locally

**Prerequisites:**
```bash
php -v # Must be 8.4+
composer --version
psql --version # PostgreSQL 15+
```

**Setup:**
```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env

# 3. Generate key & storage
php artisan key:generate
php artisan storage:link

# 4. Create database & run migrations
php artisan migrate

# 5. Seed demo data
php artisan db:seed

# 6. Start server
php artisan serve
# API at http://localhost:8000/api/
```

### 13.2 How to Run with Docker
```bash
# Start PostgreSQL container
docker compose up -d db

# Configure .env for Docker PostgreSQL:
# DB_HOST=127.0.0.1
# DB_PORT=5433
# DB_DATABASE=almohit_hotels_laravel
# DB_USERNAME=postgres
# DB_PASSWORD=almohit123

# Then run Laravel natively:
php artisan serve
```

### 13.3 How to Run Migrations
```bash
php artisan migrate
php artisan migrate:status  # Check status
php artisan migrate:fresh   # Drop all tables and re-run (dev only)
```

### 13.4 How to Seed Data
```bash
php artisan db:seed
```

Creates 4 accounts:
- `admin@almohit.com` / `adminpass123` (admin)
- `staff@almohit.com` / `staffpass123` (staff)
- `customer@almohit.com` / `customerpass123` (customer)
- `test@example.com` / `testpass123` (customer)

OTP code for local dev: `123456`

### 13.5 How to Deploy on Railway
1. Push to GitHub
2. Connect repo to Railway (New Project → Deploy from GitHub repo)
3. Set env vars (see section 8.4)
4. Generate APP_KEY locally: `php artisan key:generate --show`
5. Set `NIXPACKS_PHP_ROOT_DIR=/app/public`
6. Add `Procfile` with: `release: php artisan migrate --force`
7. Deploy

### 13.6 How to Debug Common Issues

| Problem | Likely Cause | Fix |
|---------|-------------|-----|
| `"Connection refused"` on migrate | Wrong DB_HOST/PORT | Verify PostgreSQL is running and .env is correct |
| `"Role does not exist"` | Wrong DB_USERNAME | Create user: `CREATE USER username WITH PASSWORD 'pass';` |
| `"Database does not exist"` | Wrong DB_DATABASE | Create database: `CREATE DATABASE dbname;` |
| `401 Unauthorized` | Invalid/missing token | Check `Authorization: Bearer <token>` header format |
| `"Token not provided"` | Missing auth header | Add `Authorization: Bearer <token>` to request |
| `"Invalid email or password"` | Wrong credentials | For seeded accounts: `adminpass123` / `staffpass123` / etc. |
| `"Email not verified"` | OTP not verified | Use code `123456` via `POST /api/auth/verify-otp/` |
| Images returning 404 | Missing symlink | Run: `php artisan storage:link && ln -sfn storage/app/public public/media` |
| `"Route not found"` | Missing trailing slash | Add `/` to endpoint URL |
| `500` on health check | Missing APP_KEY | Generate: `php artisan key:generate` |
| Queue not processing | Worker not running | Start: `php artisan queue:listen` |

### 13.7 How to Add New Features Safely

1. **Model changes:**
   - Create a new migration
   - Add or extend a Model class in `app/Models/`
   - If the model needs CRUD via API: add it to the route bind map in `routes/api.php:127-144`
   - If special auth: add to `CrudController::$accessMap`

2. **New endpoint:**
   - Add route in `routes/api.php` within the existing middleware group
   - If new controller, extend `Controller` or `CrudController`
   - If using CrudController: register in the bind map
   - Add response formatting in `CompatResponse` if new model type

3. **Validation:**
   - Consider creating Form Request classes for complex validation
   - At minimum, add `$request->validate([...])` rules

4. **Testing:**
   - Add PHPUnit tests in `tests/Feature/`
   - Use `RefreshDatabase` trait
   - Match response shapes against frontend expectations

5. **Documentation:**
   - Update `docs/api/OPENAPI_SPEC.yaml`
   - Update `docs/handoff/FRONTEND_HANDOFF.md` if endpoint is public-facing

---

## 14. Executive Summary

### 14.1 Biggest Strengths
1. **Clean API design** — Consistent response shapes, DRF-compatible pagination, snake_case keys
2. **Comprehensive documentation** — OpenAPI spec, Postman collection, 523-line frontend handoff guide
3. **Generic CrudController** — Reduces boilerplate significantly for standard CRUD resources
4. **Subdomain multi-tenancy** — Built-in hotel resolution via subdomain or header
5. **Django-compatible** — Frontend can switch backends without changes
6. **No hardcoded secrets** — All credentials properly use environment variables

### 14.2 Biggest Weaknesses
1. **Zero business logic tests** — Only 4 API compatibility tests exist. No coverage for auth flows, booking logic, or error scenarios.
2. **No Service/Repository layer** — Business logic is mixed into controllers, making them hard to test and maintain at scale.
3. **No async processing** — No jobs, events, or notifications. Image uploads block the request. OTP emails would block (if implemented).
4. **Inline validation everywhere** — No Form Request classes. Validation rules are duplicated and inconsistent.
5. **No rate limiting** — Auth endpoints are vulnerable to brute force attacks.
6. **Missing Procfile** — Railway deployment won't auto-run migrations.
7. **forceFill bypasses mass-assignment** — Several controllers use `forceFill` which defeats Laravel's mass-assignment protection.

### 14.3 Critical Deployment Blockers
1. **No Procfile** — `php artisan migrate --force` must run on deploy. Without a Procfile's `release:` command, the database won't migrate.
2. **No storage link automation** — `php artisan storage:link` must run on each deploy (or symlinks won't exist).
3. **Ephemeral storage** — Local storage on Railway is lost on restart. S3 must be configured before production.
4. **Nixpacks root dir** — Must set `NIXPACKS_PHP_ROOT_DIR=/app/public` manually (easy to forget).

### 14.4 Production Risks
| Risk | Severity | Mitigation |
|------|----------|------------|
| No rate limiting on auth | High | Add `throttle` middleware to auth routes |
| XSS in reviews/contact messages | Medium | Sanitize HTML on output or strip on input |
| OTP brute force (no attempt tracking) | Medium | Implement `attempt_count` check in `verifyOtp()` |
| `forceFill` bypasses fillable protection | Medium | Replace with `fill()` + explicit field whitelisting |
| No async email delivery | Medium | Mails sent synchronously would block the request |
| No queue worker in production | Low | Not urgent since no jobs exist yet |

### 14.5 Recommended Next Steps

**Before Production:**
1. Add `Procfile` with `release: php artisan migrate --force`
2. Add rate limiting to auth routes
3. Implement OTP attempt tracking
4. Replace `forceFill` with `fill()` where possible
5. Configure S3 storage and set `FILESYSTEM_DISK=s3`

**Before Feature Expansion:**
1. Add Form Request classes for validation
2. Extract business logic into Service classes
3. Write PHPUnit tests for critical flows (auth, booking, hotel CRUD)
4. Add Job classes for async image processing and email delivery
5. Implement Notifications/Mailables for OTP delivery

**Technical Debt:**
1. Add type hints to model relationships and casts
2. Replace `segment(2)` routing with explicit route-to-controller mapping
3. Move CrudController binding from `routes/api.php` to a dedicated ServiceProvider
4. Add `max:N` validation constraints to string fields
5. Add CSRF protection exemption for API routes in `bootstrap/app.php`
