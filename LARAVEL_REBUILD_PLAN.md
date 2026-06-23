# Laravel Rebuild Plan

## Principle

Build Laravel beside Django, run it as a shadow backend, and switch traffic endpoint-by-endpoint only after compatibility checks pass. Do not delete or modify `almohit_hotels_end` or `almohit_hotels` during the rebuild.

New backend folder: `almohit_hotels_laravel`.

## Phase 0: Baseline And Safety

1. Keep Django running as the production reference implementation.
2. Record current APIs and response shapes in `API_COMPATIBILITY_MAP.md`.
3. Add Laravel with its own `.env.example`, routes, migrations, tests, and storage.
4. Use a separate database first, or separate schema/database name, until parity is proven.
5. Only after endpoint parity is tested should React `REACT_APP_API_URL` be pointed at Laravel in a staging environment.

Verification:
- Confirm Django still starts.
- Confirm React files remain unchanged.
- Confirm Laravel routes load.

## Phase 1: Laravel Project Setup

1. Create `almohit_hotels_laravel` beside `almohit_hotels_end`.
2. Install Laravel dependencies through Composer.
3. Add API route group with `/api` prefix.
4. Add health endpoint returning `{"status":"ok"}`.
5. Configure testing with SQLite or a dedicated PostgreSQL test database.

Verification:
- `php artisan about`
- `php artisan route:list`
- `php artisan test`
- `GET /api/health`

## Phase 2: PostgreSQL Connection

1. Configure `.env.example` for PostgreSQL.
2. Mirror Django database env names where practical:
   - `DB_CONNECTION=pgsql`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
   - `DB_HOST`
   - `DB_PORT`
3. Add docs for mapping Django env variables to Laravel env variables.
4. Do not point Laravel at production Django tables until migrations and model parity are reviewed.

Verification:
- `php artisan migrate:status`
- `php artisan migrate --pretend`

## Phase 3: Auth System

1. Use Laravel Sanctum for API token auth.
2. Keep response compatibility with Django login:
   - `token`
   - `access`
   - `token_type`
   - `role`
   - `redirect_url`
   - `user`
3. Implement email-based login.
4. Implement signup, OTP verification, resend OTP, me, patch me, logout.
5. Add compatibility middleware that accepts `Authorization: Bearer <token>`.

Verification:
- Signup creates inactive customer and OTP.
- OTP activates account.
- Login returns Django-compatible response.
- `/api/auth/me/` returns user shape expected by React.

## Phase 4: Roles And RBAC

Roles:
- `admin`: full admin access.
- `staff`: assigned-property operational access.
- `customer`: own bookings/profile access.

Implementation:
- Add role enum/check methods to `User`.
- Add `assigned_hotels` pivot table.
- Add policies/middleware equivalent to:
  - staff or read-only
  - admin/staff assigned write
  - booking inquiry permissions
  - tenant write permissions

Verification:
- Admin can manage all hotels.
- Staff can only mutate assigned hotel resources.
- Customer cannot read other customer bookings.
- Public users can read published active inventory.

## Phase 5: Hotel/Tenant Models

1. Create migrations/models for hotels, amenities, images, policies, social media, contacts, setup status, channel-manager connections.
2. Preserve key field names in JSON responses:
   - `property` for hotel foreign keys.
   - `property_name` where Django exposes it.
   - `cover_image_url`, `is_active`, `publishing_status`.
3. Implement slug and subdomain validation.
4. Implement publish/readiness/setup endpoints.

Verification:
- Property CRUD route parity.
- Public list only shows active/published hotels.
- Staff list only shows assigned hotels.

## Phase 6: Subdomain Tenant Resolution

1. Add middleware equivalent to `PublicHotelContextMiddleware`.
2. Resolve `{subdomain}.{PUBLIC_BASE_DOMAIN}` and `X-Hotel-Subdomain`.
3. Reject reserved/invalid labels.
4. Scope public hotel querysets to the resolved hotel.

Verification:
- Header `X-Hotel-Subdomain: demo` scopes public properties, rooms, services, reviews, bookings.
- Invalid/unavailable subdomains return empty or validation errors matching Django behavior.

## Phase 7: Rooms

1. Create room types, images, prices, availability blocks.
2. Port availability helpers:
   - available units
   - blocked units
   - confirmed/new/contacted booking overlap
   - date range validation
3. Preserve `/api/properties/{id}/rooms/search/`, `/availability/`, and `/rates/`.

Verification:
- Room CRUD parity.
- Date overlap tests.
- Availability response includes `property_id`, `property`, `is_available`, `available_rooms`, `units`.

## Phase 8: Services

1. Create service categories, property services, service images.
2. Preserve filters by property, category, pricing type, currency, active/featured, min/max price.
3. Preserve image response fields and cover-image behavior.

Verification:
- Guest sees active services for published hotels.
- Staff can mutate only assigned hotel services.

## Phase 9: Bookings

1. Create booking inquiries and booking guests.
2. Port booking total calculation and status transitions.
3. Preserve public inquiry response with `booking_id`, `property_id`, `room_type_id`, `estimated_total`, `status`.
4. Preserve calendar and confirm/cancel endpoints.

Verification:
- Public inquiry works without auth.
- Authenticated customer booking is attached to user.
- Staff/admin confirmation is permission checked.
- Double-booking availability is tested.

## Phase 10: Reviews

1. Create reviews table/model.
2. Preserve public property reviews and summary endpoints.
3. Preserve moderation endpoint `/api/reviews/{id}/`.

Verification:
- Public review create/list works.
- Guest email is not exposed in public list.
- Staff/admin moderation obeys assignment.

## Phase 11: Admin APIs And Audit

1. Add admin users/admins endpoints.
2. Add audit log model and mutation hooks.
3. Add contact message endpoints.
4. Add route/resource tests for admin pages used by React.

Verification:
- Admin dashboard APIs respond.
- Audit logs are created on create/update/delete.
- Contact message public create and admin handling work.

## Cutover Strategy

1. Run Django and Laravel side by side.
2. Compare responses for critical GET endpoints with seeded data.
3. Run React against Laravel in staging by changing only environment configuration.
4. Cut over public read endpoints first, then auth, then bookings/admin mutations.
5. Keep Django available for rollback until Laravel has passed production soak.

## Do Not Do Yet

- Do not remove Django code.
- Do not edit React API clients until Laravel parity gaps are explicitly identified.
- Do not reuse the production database before migration scripts are reviewed.
- Do not change Git history.
