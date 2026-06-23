# Backend Migration Audit

## Scope

Current backend: `almohit_hotels_end` Django REST Framework application.

Frontend: `almohit_hotels` React application. This audit does not require frontend changes.

Database target: PostgreSQL. Django falls back to SQLite for local debug/testing when PostgreSQL variables are absent.

Important repository note: the root `.git` directory is currently empty, so `git status` cannot run from this checkout. No Git history changes are required or attempted by this migration.

## Django Project Structure

- `config/settings.py`: environment loading, installed apps, middleware, database, CORS, JWT/token auth, email, logging, public subdomain settings.
- `config/urls.py`: root route, health check, admin, API route registration, schema/docs, media serving.
- `apps/user`: custom user model, OTP verification, login/logout, admin/user management, custom JWT.
- `apps/hotels`: properties/hotels, amenities, images, policies, setup metadata, channel manager connection, public subdomains.
- `apps/rooms`: room types, seasonal prices, availability blocks, room images.
- `apps/bookings`: booking inquiries, guests, availability/total calculations, status transitions.
- `apps/services`: service categories, property services, service images.
- `apps/reviews`: public reviews and staff moderation.
- `apps/common`: base model, audit logs, contact messages, tenant/public hotel helpers, permissions, throttling, pagination, logging middleware.

## Models

Common:
- `BaseModel`: `created_at`, `updated_at`.
- `AuditLog`: action, content type, object id/repr, actor snapshot, changes JSON, request method/path, IP, created timestamp.
- `ContactMessage`: optional hotel, sender fields, subject/message, status, handler, handled timestamp.

User/auth:
- `User`: email login, full name, phone, role `customer|staff|admin`, active/staff/superuser flags, email verification, assigned hotels, failed login/lockout fields.
- `EmailOTP`: hashed OTP, expiry, verification timestamp, attempt/resend counters.

Hotels/tenants:
- `Hotel`: name, slug, subdomain, property type, location/contact fields, stars, descriptions, timezone, languages, transport/parking flags, years, media URLs, amenities, status, owner, coordinates, audit user fields.
- `ChannelManagerConnection`: one-to-one hotel provider state and sync timestamps.
- `PropertySocialMedia`: one-to-one hotel social/channel links.
- `PropertyContacts`: one-to-one hotel contact details.
- `PropertySetupStatus`: one-to-one hotel onboarding completion data.
- `HotelAmenity`: name, icon, active flag.
- `HotelImage`: hotel image, thumbnail, caption, alt text, display order, cover/active flags.
- `HotelPolicy`: one-to-one hotel policy fields.

Rooms:
- `RoomType`: hotel, name/description, room size, bed/smoking, capacities, units, base/weekend price, pricing mode, currency, extra bed, breakfast, amenities, active flag.
- `AvailabilityBlock`: room type, start/end dates, blocked units, reason, notes, active flag.
- `RoomTypeImage`: room type image, thumbnail, caption, alt text, display order, cover/active flags.
- `RoomPrice`: room type seasonal date range and nightly price.

Bookings:
- `BookingInquiry`: customer contact, optional customer user, hotel, room type, stay dates, guest counts, extra bed, estimated total, status.
- `BookingGuest`: booking guest details and primary guest flag.

Services:
- `ServiceCategory`: name, slug, description, icon, active flag.
- `HotelService`: hotel, category, name/slug, descriptions, price/currency/pricing type, duration/availability window, booking requirement, featured/active flags.
- `ServiceImage`: service image, thumbnail, caption, alt text, display order, cover/active flags.

Reviews:
- `Review`: hotel, optional user, public guest fields, rating, title/comment, category ratings, active flag.

## APIs

Root/API:
- `GET /api/health/`
- `GET /api/public/hotel-context/`
- `GET /api/schema/`, `/api/docs/`, `/api/redoc/`

Auth:
- `POST /api/auth/signup/`
- `POST /api/auth/verify-otp/`
- `POST /api/auth/resend-otp/`
- `POST /api/auth/login/`
- `POST /api/auth/admin/login/`
- `POST /api/auth/customer/login/`
- `GET/PATCH /api/auth/me/`
- `POST /api/auth/logout/`
- REST routes under `/api/auth/admins/`
- REST routes under `/api/auth/users/`
- User actions: `activate`, `deactivate`, `change-role`, `reset-password`.

Properties:
- REST routes under `/api/properties/`
- Property actions: `publish`, `unpublish`, `archive`, `unarchive`, `readiness`, `setup-status`, `autosave`, `workspace`, `available`, `rooms/search`, `availability`, `rates`, `reviews`, `reviews/summary`.
- REST routes under `/api/property-images/`
- REST routes under `/api/property-amenities/`
- REST routes under `/api/channel-manager-connections/`

Rooms:
- REST routes under `/api/room-types/`
- `GET /api/room-types/{id}/rates/`
- REST routes under `/api/room-type-images/`
- REST routes under `/api/room-prices/`
- REST routes under `/api/room-amenities/`
- REST routes under `/api/availability-blocks/`

Services:
- REST routes under `/api/service-categories/`
- REST routes under `/api/property-services/`
- REST routes under `/api/service-images/`

Bookings:
- REST routes under `/api/bookings/`
- `GET /api/bookings/calendar/`
- `POST /api/bookings/inquiry/`
- `POST /api/bookings/confirm/`
- `POST /api/bookings/{id}/cancel/`

Reviews:
- REST moderation routes under `/api/reviews/` with `GET`, `PATCH`, `DELETE`.
- Public review listing/creation is exposed through `/api/properties/{id}/reviews/`.

Common/admin:
- REST routes under `/api/audit-logs/`
- REST routes under `/api/contact-messages/`

## Permission Model

- Default DRF permission: authenticated requests.
- Public endpoints explicitly use `AllowAny`, including signup/login/OTP, health, public hotel context, public property data, and public review creation.
- `IsStaffOrReadOnly`: read is public, mutation requires Django `is_staff`.
- `IsStaffOrAssignedOrReadOnly`: read is public; mutation requires admin or role `staff`; object mutation for staff requires assignment to the related hotel.
- `IsTenantWriteAllowed`: mutation requires admin or assigned staff.
- `BookingInquiryPermission`: public booking inquiry creation; authenticated customer can read/cancel own bookings; assigned staff can read/update/cancel bookings for assigned hotels; admin can manage all.
- Django `IsAdminUser` protects admin/user account management, audit logs, and parts of contact-message administration.

## Middleware

Configured middleware:
- Django security, WhiteNoise static files, CORS, sessions, common middleware, CSRF, auth, messages, clickjacking protection.
- `PublicHotelContextMiddleware`: resolves hotel context from subdomain or `X-Hotel-Subdomain`.
- `APILoggingMiddleware`: logs method, path, redacted query string, user, IP, status, and duration.

## Tenant Logic

- `PUBLIC_BASE_DOMAIN` defines the base public domain, default `almohit.com`.
- `PUBLIC_RESERVED_SUBDOMAINS` blocks labels such as `admin`, `api`, `www`, `mail`, `media`, `static`, `support`.
- Public hotel context is resolved from `{subdomain}.{PUBLIC_BASE_DOMAIN}` or the `X-Hotel-Subdomain` header.
- Invalid/unpublished/inactive subdomains set request context to `invalid` or `unavailable`.
- Public querysets are scoped to the resolved hotel unless the authenticated user is privileged.
- Assigned staff are scoped by the `User.assigned_hotels` many-to-many relationship.
- Tenant relation validation rejects cross-property mutations for staff and public hotel contexts.

## Auth Logic

- Custom email-based `User` model.
- Public signup creates inactive, unverified `customer` users and sends a 6-digit OTP email.
- OTP verification activates the user and marks email verified.
- Resend OTP is rate-limited and capped per window.
- Login returns both legacy DRF token and custom JWT-compatible fields:
  - `token`
  - `access`
  - `token_type: "Bearer"`
  - `role`
  - `redirect_url`
  - `user`
- Authentication accepts custom JWT bearer tokens and DRF token auth.
- Admin login requires `is_staff`; customer login rejects staff accounts.
- Logout deletes DRF tokens.

## Environment Variables

Django/security:
- `DJANGO_SECRET_KEY`
- `JWT_SECRET_KEY`
- `DJANGO_DEBUG`
- `DEVELOPMENT_MODE`
- `DJANGO_ALLOWED_HOSTS`
- `RAILWAY_PUBLIC_DOMAIN`
- `DJANGO_SECURE_SSL_REDIRECT`
- `DJANGO_SECURE_HSTS_SECONDS`
- `DJANGO_SECURE_HSTS_INCLUDE_SUBDOMAINS`
- `DJANGO_SECURE_HSTS_PRELOAD`
- `DJANGO_CSRF_COOKIE_HTTPONLY`
- `DJANGO_SESSION_COOKIE_SAMESITE`
- `DJANGO_CSRF_COOKIE_SAMESITE`
- `DJANGO_SECURE_REFERRER_POLICY`
- `DJANGO_X_FRAME_OPTIONS`

Tenant/frontend:
- `PUBLIC_BASE_DOMAIN`
- `PUBLIC_RESERVED_SUBDOMAINS`
- `FRONTEND_HOME_URL`
- `FRONTEND_ADMIN_URL`
- `FRONTEND_CUSTOMER_URL`
- `FRONTEND_OWNER_URL`
- `FRONTEND_URL`

Database:
- `DATABASE_URL`
- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `POSTGRES_HOST`
- `POSTGRES_PORT`

Media/static:
- `RAILWAY_VOLUME_MOUNT_PATH`

CORS/CSRF:
- `CORS_ALLOWED_ORIGINS`
- `CORS_ALLOWED_ORIGIN_REGEXES`
- `CORS_ALLOW_ALL_ORIGINS`
- `CORS_ALLOW_CREDENTIALS`
- `CSRF_TRUSTED_ORIGINS`

Email:
- `EMAIL_BACKEND`
- `EMAIL_HOST`
- `EMAIL_PORT`
- `EMAIL_HOST_USER`
- `EMAIL_HOST_PASSWORD`
- `EMAIL_USE_TLS`
- `DEFAULT_FROM_EMAIL`
- `EMAIL_VERIFICATION_TIMEOUT`

Docker/deploy variables observed:
- `GUNICORN_WORKERS`
- `FRONTEND_PORT`

## Migration Risks

- Password hashes and token semantics differ between Django and Laravel; use a transition login strategy before cutting over.
- DRF pagination and validation error shapes must be preserved for React compatibility.
- File upload paths and generated thumbnails must be handled deliberately.
- Tenant scoping is security-sensitive and must be covered by tests before production traffic moves.
- Booking availability calculations are business-critical and should be ported with parity tests.
- Current checkout has no usable Git metadata, so external version control verification is recommended before major deployment work.
