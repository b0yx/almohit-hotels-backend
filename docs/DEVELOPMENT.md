# Backend Developer Guide — Almohit Hotels (Laravel)

## 1. Local Setup

**Requirements:**
- PHP 8.3+ (project requires `^8.3` per `composer.json`)
- Composer
- PostgreSQL 17 (Docker container on bridge IP)
- Node.js & npm (for Vite asset compilation)

**Setup steps:**
```bash
git clone <repo-url> almohit_hotels_laravel
cd almohit_hotels_laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan db:seed
php artisan serve --host=0.0.0.0 --port=9090
```

**Quick dev command** (runs serve + queue + logs + Vite concurrently):
```bash
npm install && composer run dev
```

## 2. Environment Variables

All variables read from `.env` (see `.env.example`):

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `"Almohit Hotels Laravel"` | Application name used in notifications, UI |
| `APP_ENV` | `local` | Environment: `local`, `production`, `testing` |
| `APP_DEBUG` | `true` | Enable/disable verbose error pages |
| `APP_URL` | `http://localhost` | Base URL for the app |
| `DB_CONNECTION` | `pgsql` | Database driver (`pgsql`) |
| `DB_HOST` | `172.21.0.2` | PostgreSQL host (Docker bridge IP) |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_DATABASE` | `almohit_hotels_laravel` | Database name (NOT `almohit_hotels_db`) |
| `DB_USERNAME` | `postgres` | Database user |
| `DB_PASSWORD` | `almohit123` | Database password |
| `FILESYSTEM_DISK` | `local` | Default storage disk (`local` or `public`) |
| `PUBLIC_BASE_DOMAIN` | `almohit.com` | Base domain for subdomain hotel resolution |
| `PUBLIC_RESERVED_SUBDOMAINS` | `admin,api,www,mail,media,static,support` | Subdomains not treated as hotel slugs |
| `FRONTEND_HOME_URL` | `http://localhost:3000/` | Frontend home URL |
| `FRONTEND_ADMIN_URL` | `http://localhost:3000/admin/` | Admin redirect after login |
| `FRONTEND_CUSTOMER_URL` | `http://localhost:3000/profile/` | Customer redirect after login |
| `FRONTEND_OWNER_URL` | `http://localhost:3000/owner/` | Owner dashboard URL |
| `SESSION_DRIVER` | `database` | Session storage driver |
| `SESSION_DOMAIN` | `null` | Session cookie domain |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `CACHE_STORE` | `database` | Cache driver |
| `MAIL_MAILER` | `log` | Mail driver (`log` writes to storage/logs) |
| `BCRYPT_ROUNDS` | `12` | Bcrypt hashing rounds |

**Note:** There is no `SANCTUM_STATEFUL_DOMAINS` — this project does **not** use Laravel Sanctum. Auth is via a custom `api_tokens` table (see Section 5).

## 3. Database Setup

**Docker PostgreSQL:**
- Bridge IP: `172.21.0.2` (port `5432`)
- Database: `almohit_hotels_laravel`
- User: `postgres` / Password: `almohit123`

**33 tables** across 4 migration files:

**Laravel framework tables** (`0001_01_01_*`):
- `users`, `password_reset_tokens`, `sessions`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`

**Domain tables** (`2026_06_23_000000_create_almohit_domain_tables.php`):
| Table | Purpose |
|---|---|
| `api_tokens` | Custom token-based auth (SHA-256 hashed tokens) |
| `email_otps` | Email OTP verification codes |
| `hotels` | Properties (the core entity) |
| `hotel_amenities` | Amenity definitions (pool, wifi, etc.) |
| `hotel_amenity_hotel` | Pivot: hotel ↔ amenity |
| `hotel_amenity_room_type` | Pivot: room_type ↔ amenity |
| `hotel_user_assignments` | Pivot: staff ↔ hotel assignments |
| `hotel_images` | Property images |
| `hotel_policies` | Per-property policies (cancellation, check-in, etc.) |
| `property_social_media` | Per-property social links |
| `property_contacts` | Per-property contact info |
| `property_setup_statuses` | Onboarding completion tracker |
| `channel_manager_connections` | OTA/channel manager integrations |
| `room_types` | Room categories under a hotel |
| `availability_blocks` | Blocked dates for room types |
| `room_type_images` | Room type images |
| `room_prices` | Seasonal pricing for room types |
| `service_categories` | Service categories (spa, dining, etc.) |
| `hotel_services` | Services offered by a hotel |
| `service_images` | Service images |
| `booking_inquiries` | Booking requests/inquiries |
| `booking_guests` | Guest details per booking |
| `reviews` | Guest reviews & ratings |
| `audit_logs` | Admin action audit trail |
| `contact_messages` | Contact form submissions |

Run migrations:
```bash
php artisan migrate
```

## 4. Storage Setup

**Symlinks:**
```bash
php artisan storage:link
# Creates: public/storage → storage/app/public
```

**Additional symlink** (for frontend proxy compatibility):
```bash
ln -sfn storage/app/public public/media
# Creates: public/media → storage/app/public
```

**Image upload directories** (auto-created on first upload):
- `storage/app/public/hotels/` — property images
- `storage/app/public/room-types/` — room type images
- `storage/app/public/services/` — service images

**Naming convention:** UUID filenames — `Str::uuid()->toString() . '.' . $ext`

**URL pattern:** Images stored as `/media/{dir}/{uuid}.{ext}` and served via the `public/media` symlink.

**Disk config** (`config/filesystems.php`):
- `public` disk root: `storage/app/public`
- URL: `{APP_URL}/storage` (but frontend uses `/media/` path)

## 5. Auth Flow

**Custom token-based auth** (NOT Laravel Sanctum or Passport).

**Token storage (`api_tokens` table):**
- Plaintext token (64-char `Str::random(64)`) returned to client on login
- SHA-256 hash stored in DB: `hash('sha256', $plain)`

**Middleware:** `AuthenticateApiToken` (`app/Http/Middleware/AuthenticateApiToken.php`)
- Reads `Authorization` header
- Accepts both `Bearer <token>` and `Token <token>` schemes
- Matches `hash('sha256', $token)` against `api_tokens.token`
- Sets user on request if valid token found

**Roles:**
- `admin` — `is_staff = true` OR `role = 'admin'`
- `staff` — `role = 'staff'` AND `is_active = true`
- `customer` — `role = 'customer'` (default)

**Signup flow:**
1. `POST /api/auth/signup/` — creates user with `is_active=false`, `role=customer`, generates an OTP with code `"123456"` (bcrypt hashed)
2. `POST /api/auth/verify-otp/` — verifies OTP code (always `"123456"` in dev), sets `email_verified=true`, `is_active=true`
3. `POST /api/auth/login/` — returns `{ token, access, token_type, role, redirect_url, user }`

**Login response:**
```json
{
  "token": "64-char-random-string",
  "access": "64-char-random-string",
  "token_type": "Bearer",
  "role": "admin|customer|staff",
  "redirect_url": "http://localhost:3000/admin/",
  "user": { ... }
}
```

**Specialized login endpoints:**
- `POST /api/auth/admin/login/` — rejects non-admin users (403)
- `POST /api/auth/customer/login/` — rejects admin users (403)

**Logout:** `POST /api/auth/logout/` — deletes the token record from `api_tokens`.

**Role-check middleware** (`RequireRole`): usage `->middleware('role:admin')` — supports `admin`, `staff`, `customer`.

## 6. Image Upload Flow

**Three endpoint groups** (all handled by `ImageUploadController`):
| Prefix | Model | FK Column | Request Key | Directory |
|---|---|---|---|---|
| `/api/property-images/` | `HotelImage` | `hotel_id` | `property` | `hotels/` |
| `/api/room-type-images/` | `RoomTypeImage` | `room_type_id` | `room_type` | `room-types/` |
| `/api/service-images/` | `ServiceImage` | `hotel_service_id` | `service` | `services/` |

**Request formats:**
- `multipart/form-data` — file upload via `image` field
- `application/json` — URL string via `image` field

**Boolean normalization:** FormData sends `'true'`/`'false'` as strings. `ImageUploadController::normalizeBooleans()` converts these back to booleans for `is_cover` and `is_active`.

**Response format** (includes `image_url` alias alongside `image`):
```json
{
  "id": 1,
  "property": 1,
  "image": "/media/hotels/uuid.jpg",
  "image_url": "/media/hotels/uuid.jpg",
  "caption": "...",
  "is_cover": true,
  "is_active": true
}
```

**Foreign key wrapping:** Request body uses `property`/`room_type`/`service` keys; controller maps to `hotel_id`/`room_type_id`/`hotel_service_id` internally.

**File validation:** `mimes:jpeg,png,jpg,gif,webp`, max 10MB (10240 KB).

**Update/Destroy:** Old files are deleted from storage when replaced or removed.

## 7. Seeder Usage

```bash
php artisan db:seed
```

Creates **4 accounts** (all with pre-verified OTP `"123456"`):

| Email | Password | Role | Notes |
|---|---|---|---|
| `admin@almohit.com` | `adminpass123` | admin | `is_staff=true`, `is_superuser=true` |
| `staff@almohit.com` | `staffpass123` | staff | `is_staff=true` |
| `customer@almohit.com` | `customerpass123` | customer | Standard customer |
| `test@example.com` | `testpass123` | customer | Test customer |

Each seed account gets an `EmailOTP` record with `hashed_code = bcrypt('123456')` and a 10-minute expiry.

## 8. CrudController Binding

**Single controller for multiple models** — routes mapped via service container binding in `routes/api.php:97-123`:

```php
app()->bind(CrudController::class, function ($app, array $params = []) {
    $routeName = request()->route()?->getName() ?? '';
    $map = [
        'users' => User::class,
        'admins' => User::class,
        'auth.users' => User::class,
        'auth.admins' => User::class,
        'property-amenities' => HotelAmenity::class,
        'channel-manager-connections' => ChannelManagerConnection::class,
        'room-types' => RoomType::class,
        'room-prices' => RoomPrice::class,
        'room-amenities' => HotelAmenity::class,
        'availability-blocks' => AvailabilityBlock::class,
        'service-categories' => ServiceCategory::class,
        'property-services' => HotelService::class,
        'audit-logs' => AuditLog::class,
        'contact-messages' => ContactMessage::class,
    ];
    // ...
});
```

**Field aliases** (converted in `normalizeInput()`):
| API field | DB column |
|---|---|
| `property` | `hotel_id` |
| `room_type` | `room_type_id` |
| `service` | `hotel_service_id` |
| `category` | `service_category_id` |
| `customer` | `customer_id` |

**Filter params** (converted in `applyFilters()`):
| Query param | DB column | Type |
|---|---|---|
| `property` | `hotel_id` | integer |
| `room_type` | `room_type_id` | integer |
| `service` | `hotel_service_id` | integer |
| `category` | `service_category_id` | integer |
| `status` | `status` | string |
| `is_active` | `is_active` | boolean |
| `is_featured` | `is_featured` | boolean |
| `pricing_type` | `pricing_type` | string |
| `currency` | `currency` | string |
| `reason` | `reason` | string |

**Search param:** `?search=keyword` — searches against `name`, `full_name`, `email`, `customer_name`, `subject` columns.

## 9. CompatResponse

`App\Support\CompatResponse` formats all API responses for frontend compatibility.

**Methods:**

| Method | Input | Output behavior |
|---|---|---|
| `item($model)` | Any Model | Dispatches to type-specific formatter or `generic()` |
| `page($paginator)` | `LengthAwarePaginator` | `{ count, next, previous, results }` with mapped items |
| `user($user)` | User | Role mapping, `is_staff`, `is_active`, `email_verified` |
| `hotel($hotel)` | Hotel | Wraps with `cover_image_url`, `amenities`, `images`, `average_rating`, `total_reviews`, `publishing_status` |
| `roomType($room)` | RoomType | `property` alias for `hotel_id`, `cover_image_url`, nested `images`, `prices`, `amenity_details` |
| `service($service)` | HotelService | `property`/`category` aliases, `property_name`, `category_name` |
| `booking($booking)` | BookingInquiry | `property`/`room_type`/`customer` aliases, `property_name`, `room_type_name`, `nights`, `guests` |
| `review($review)` | Review | `property` alias for `hotel_id` |
| `generic($model)` | Any Model | `toArray()` minus FK columns (`hotel_id`, `service_category_id`, `hotel_service_id`) |
| `genericAlias($model, $aliases)` | Model + map | Renames DB columns to API-public keys |

## 10. Filtering & Pagination

**Default pagination:** `paginate(20)` on all list endpoints.

**Override:** `?page_size=50`

**Paginated response shape:**
```json
{
  "count": 42,
  "next": "http://.../?page=3&page_size=20",
  "previous": "http://.../?page=1&page_size=20",
  "results": [ ... ]
}
```

**Foreign key filters:** `?property=1`, `?room_type=1`, `?service=1`, `?category=1`

**Boolean filters:** `?is_active=true`, `?is_featured=true`

**Exact match filters:** `?status=new`, `?pricing_type=on_request`, `?currency=USD`, `?reason=maintenance`

**Text search:** `?search=keyword` — searches `name`, `full_name`, `email`, `customer_name`, `subject` via `LIKE %keyword%`

**Hotel-specific filters** (on `GET /api/properties/`): `?country=`, `?city=`, `?property_type=`, `?stars=`

**Booking-specific filters:** Staff see assigned hotels' bookings; customers see only their own bookings.

## 11. Common Troubleshooting

| Error / Symptom | Cause & Fix |
|---|---|
| `"null value in column"` | Model needs `$attributes` default or request must include field. Add defaults in model or ensure request body includes the column. |
| `"The is cover field must be true or false"` | FormData sends `'true'`/`'false'` as strings. Use `normalizeBooleans()` or send as JSON. |
| `"Token not provided"` / 401 | Check auth header format. Must be: `Authorization: Bearer <token>` or `Authorization: Token <token>`. |
| `"Invalid email or password."` | Wrong credentials. Check `.env` seeding ran. For seeded accounts, passwords are: `adminpass123`, `staffpass123`, `customerpass123`, `testpass123`. |
| `"Email not verified"` | OTP not verified. Use code `"123456"` via `POST /api/auth/verify-otp/`. |
| Docker PostgreSQL connection refused | Check container is running: `docker ps`. Verify bridge IP `172.21.0.2:5432`. Try `docker inspect <container> \| grep IPAddress`. |
| Storage not accessible (404 on images) | Verify `public/media` symlink exists: `ls -la public/media`. Run: `ln -sfn storage/app/public public/media`. |
| `"Route not found"` for auth/users routes | Auth routes are under `tenant.context` + `api.token` middleware and admin routes have `role:admin`. Ensure you're authenticated as admin. |
| `"Relation "images" not found"` in hotel response | Eager loading missing. HotelController loads with `['amenities', 'images']` — if creating custom queries, add `->with(['amenities', 'images'])`. |

## 12. Routes Reference

All routes are defined in `routes/api.php`. Total of **~60+ API endpoints**, all under `/api` prefix.

**Middleware stack on all routes:** `tenant.context` + `api.token` (except noted).

**Public endpoints** (no auth required):
| Method | Path | Description |
|---|---|---|
| `GET` | `/api/health/` | Health check — returns `{"status":"ok"}` |
| `GET` | `/api/public/hotel-context/` | Current subdomain hotel context |

**Auth endpoints** (`/api/auth/*`):
| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/api/auth/signup/` | No | Create account (role=customer) |
| `POST` | `/api/auth/verify-otp/` | No | Verify email with OTP code |
| `POST` | `/api/auth/resend-otp/` | No | Resend OTP |
| `POST` | `/api/auth/login/` | No | Login |
| `POST` | `/api/auth/admin/login/` | No | Admin-only login |
| `POST` | `/api/auth/customer/login/` | No | Customer-only login |
| `GET` | `/api/auth/me/` | Yes | Current user profile |
| `PATCH` | `/api/auth/me/` | Yes | Update profile (full_name, phone) |
| `POST` | `/api/auth/logout/` | Yes | Logout (delete token) |

**Admin-only auth endpoints** (`role:admin`):
| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/api/auth/users/` | List/create users |
| `GET/PUT/PATCH/DELETE` | `/api/auth/users/{id}/` | Show/update/delete user |
| `GET/POST` | `/api/auth/admins/` | List/create admins |
| `POST` | `/api/auth/users/{id}/activate/` | Activate user |
| `POST` | `/api/auth/users/{id}/deactivate/` | Deactivate user |
| `POST` | `/api/auth/users/{id}/change-role/` | Change user role |
| `POST` | `/api/auth/users/{id}/reset-password/` | Reset user password |

**Properties** (`/api/properties/*`):
| Method | Path | Description |
|---|---|---|
| `GET` | `/api/properties/available/` | List available properties |
| `GET/POST` | `/api/properties/` | List/create properties |
| `GET/PUT/PATCH/DELETE` | `/api/properties/{id}/` | CRUD individual property |
| `POST` | `/api/properties/{id}/publish/` | Publish property |
| `POST` | `/api/properties/{id}/unpublish/` | Unpublish property |
| `POST` | `/api/properties/{id}/archive/` | Archive property |
| `POST` | `/api/properties/{id}/unarchive/` | Unarchive property |
| `GET` | `/api/properties/{id}/readiness/` | Check publish readiness |
| `GET` | `/api/properties/{id}/setup-status/` | Onboarding setup status |
| `PATCH` | `/api/properties/{id}/autosave/` | Autosave property + setup step |
| `GET` | `/api/properties/{id}/workspace/` | Workspace dashboard data |
| `GET` | `/api/properties/{id}/rooms/search/` | Search active room types |
| `GET` | `/api/properties/{id}/availability/` | Room availability |
| `GET` | `/api/properties/{id}/rates/` | Room rates + seasonal prices |
| `GET/POST` | `/api/properties/{property}/reviews/` | List/create reviews for property |
| `GET` | `/api/properties/{property}/reviews/summary/` | Rating summary & breakdown |

**Bookings** (`/api/bookings/*`):
| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/api/bookings/` | List/create bookings |
| `GET/PUT/PATCH/DELETE` | `/api/bookings/{id}/` | Show/update/delete booking |
| `GET` | `/api/bookings/calendar/` | Booking calendar (same as index) |
| `POST` | `/api/bookings/inquiry/` | Submit booking inquiry |
| `POST` | `/api/bookings/confirm/` | Confirm booking |
| `POST` | `/api/bookings/{id}/cancel/` | Cancel booking |

**Reviews** (`/api/reviews/*`):
| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/api/reviews/` | List/create reviews |
| `GET/PUT/PATCH/DELETE` | `/api/reviews/{id}/` | CRUD review |

**CRUD resource routes** (all support `GET/POST /` and `GET/PUT/PATCH/DELETE /{id}`):
| Prefix | Model |
|---|---|
| `/api/property-amenities/` | HotelAmenity |
| `/api/channel-manager-connections/` | ChannelManagerConnection |
| `/api/room-types/` | RoomType |
| `/api/room-prices/` | RoomPrice |
| `/api/room-amenities/` | HotelAmenity |
| `/api/availability-blocks/` | AvailabilityBlock |
| `/api/service-categories/` | ServiceCategory |
| `/api/property-services/` | HotelService |
| `/api/audit-logs/` | AuditLog (index/show only) |
| `/api/contact-messages/` | ContactMessage |

**Image routes** (all support `GET/POST /`, `GET/PATCH/DELETE /{id}`):
| Prefix | Model |
|---|---|
| `/api/property-images/` | HotelImage |
| `/api/room-type-images/` | RoomTypeImage |
| `/api/service-images/` | ServiceImage |

## 13. Middleware Stack

**Middleware aliases** (registered in `bootstrap/app.php`):

| Alias | Class | Purpose |
|---|---|---|
| `tenant.context` | `ResolvePublicHotel` | Extracts subdomain from host header or `X-Hotel-Subdomain` header; sets `public_hotel` on request attributes |
| `api.token` | `AuthenticateApiToken` | Validates `Bearer <token>` / `Token <token>` Authorization header; loads user from `api_tokens` table |
| `role:admin` | `RequireRole` | Checks authenticated user has required role (`admin`, `staff`, or `customer`) |

**Middleware application:**
- All API routes (except health) use `['tenant.context', 'api.token']`
- Admin sub-routes add `->middleware('role:admin')`
- All API exceptions render as JSON automatically (configured in `bootstrap/app.php`)

**`ResolvePublicHotel` behavior:**
- If no valid subdomain found: `public_hotel_status = 'bypass'`
- If subdomain doesn't match a hotel: `public_hotel_status = 'invalid'`
- If hotel is inactive/unpublished: `public_hotel_status = 'unavailable'`
- If valid: `public_hotel_status = 'available'`, `public_hotel` set to Hotel model

**`AuthenticateApiToken` behavior:**
- If no Authorization header or invalid token: request proceeds with **no user** (not a 401 rejection)
- If valid token + active user: `$request->user()` returns the User model
- Role enforcement happens downstream via `RequireRole` or controller logic

## 14. Key Files Reference

| File | Purpose |
|---|---|
| `routes/api.php` | All API route definitions + CrudController binding |
| `bootstrap/app.php` | Middleware registration, exception handling |
| `app/Http/Controllers/Api/AuthController.php` | Signup, login, OTP verify, logout, user management |
| `app/Http/Controllers/Api/HotelController.php` | Property CRUD, publish/archive, workspace, availability |
| `app/Http/Controllers/Api/BookingController.php` | Booking CRUD, inquiry, confirm, cancel |
| `app/Http/Controllers/Api/ReviewController.php` | Review CRUD, property reviews, summary |
| `app/Http/Controllers/Api/CrudController.php` | Generic CRUD with filtering, aliasing, pagination |
| `app/Http/Controllers/Api/ImageUploadController.php` | Image upload for hotels, room types, services |
| `app/Http/Middleware/AuthenticateApiToken.php` | Custom token auth middleware |
| `app/Http/Middleware/ResolvePublicHotel.php` | Subdomain hotel context middleware |
| `app/Http/Middleware/RequireRole.php` | Role-based access control middleware |
| `app/Models/*.php` | 19 Eloquent models |
| `app/Support/CompatResponse.php` | Response formatter for frontend compatibility |
| `config/almohit.php` | App-specific config (domains, frontend URLs) |
| `config/filesystems.php` | Storage disk config + symlink definitions |
| `database/migrations/0001_01_01_000000_create_users_table.php` | Users + sessions migration |
| `database/migrations/2026_06_23_000000_create_almohit_domain_tables.php` | All domain tables migration |
| `database/seeders/DatabaseSeeder.php` | Seed 4 user accounts |
| `.env.example` | All environment variables documented |
