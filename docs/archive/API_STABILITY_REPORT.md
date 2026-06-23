# API Stability Report

**Generated:** 2026-06-23  
**Codebase:** `/almohit_hotels_laravel` (Laravel API)  
**Scope:** All routes in `routes/api.php`, controllers in `app/Http/Controllers/Api/`, middleware, exception handling, and response support.

---

## 1. Response Consistency

### 1.1 List/Collection Endpoints

| Endpoint | Returns | Pagination Format | Consistent? |
|---|---|---|---|
| `CrudController::index` (all generic CRUD) | `CompatResponse::page()` | `{count, next, previous, results}` | ✅ Yes |
| `HotelController::index` | `CompatResponse::page()` | `{count, next, previous, results}` | ✅ Yes |
| `BookingController::index` | `CompatResponse::page()` | `{count, next, previous, results}` | ✅ Yes |
| `ReviewController::propertyReviews` (GET) | `CompatResponse::page()` | `{count, next, previous, results}` | ✅ Yes |
| `ImageUploadController::index` | Custom `{count, next, previous, results}` | Always `null` next/previous (no pagination) | ⚠️ Never paginates |
| `ReviewController::summary` | Custom shape | Not a list | N/A |

### 1.2 Single-Resource Endpoints

| Endpoint | Response Shape | Consistent? |
|---|---|---|
| `CrudController::show` | `CompatResponse::item($model)` → delegates to type-specific | ✅ Unifies via `item()` |
| `CrudController::update` | `CompatResponse::item($model->fresh())` | ✅ |
| `HotelController::store` | `CompatResponse::hotel()` | ✅ |
| `HotelController::publish/unpublish/archive/unarchive` | `CompatResponse::hotel()` | ✅ |
| `BookingController::store` | `CompatResponse::booking()` | ✅ |
| `BookingController::cancel` | `CompatResponse::booking()` | ✅ |
| `ReviewController::propertyReviews` (POST) | `CompatResponse::review()` | ✅ |
| `ImageUploadController::store` | `format()` → `toArray()` + renamed keys + `image_url` | ❌ Different from CompatResponse |
| `ImageUploadController::show/update` | `format()` | ❌ Different from CompatResponse |
| **`BookingController::inquiry`** | Flat custom: `{booking_id, customer_name, phone, ...}` | ❌ **Deviates from CompatResponse::booking** |
| **`BookingController::confirm`** | Flat custom: `{booking_id, status, confirmed_at, estimated_total}` | ❌ **Deviates from CompatResponse::booking** |
| **`HotelController::readiness`** | `{is_ready_to_publish: true, errors: []}` | ❌ **Hardcoded stub** |
| **`HotelController::setupStatus`** | `{completion_percentage: 0, last_completed_step: 1, autosaved_at: null}` | ❌ **Hardcoded stub** |
| **`HotelController::workspace`** | `{property: {...}, setup: {...}, recent_activity: [], ...}` | ❌ Wraps property under key |
| **`HotelController::autosave`** | `{property: {...}, setup: {...}}` | ❌ Wraps property under key |
| **`AuthController::me/updateMe`** | Raw `CompatResponse::user()` (no wrapper) | ❌ **Not consistent** with REST conventions |

### 1.3 Error Response Formats

| Scenario | Status | Body Format |
|---|---|---|
| **ValidationException** (default Laravel) | 422 | `{message: "...", errors: {...}}` |
| **ModelNotFoundException** (default Laravel) | 404 | `{message: "..."}` |
| **Missing auth** (`me`/`updateMe`) | 401 | `{detail: "Authentication credentials were not provided."}` |
| **Missing auth** (RequireRole middleware) | 401 | `{detail: "Authentication credentials were not provided."}` |
| **Forbidden** (RequireRole middleware) | 403 | `{detail: "You do not have permission to perform this action."}` |
| **Role mismatch** (`login` with `admin` check) | 403 | `{detail: "Admin account required."}` |
| **Role mismatch** (`login` with `customer` check) | 403 | `{detail: "Customer account required."}` |
| **Email not verified** (`login`) | 400 | `{code: "email_not_verified", detail: "...", email: "..."}` |
| **Invalid OTP** (`verifyOtp`) | 400 | `{detail: "Invalid verification code.", code: "invalid_otp"}` |
| **Inactive account** (`login`) | 422 | `ValidationException` with `{message, errors: {detail: [...]}}` |

**Issues:**
- Error bodies use **`detail`** in some places and **`message`+`errors`** in others
- The `login` `inactive` case throws `ValidationException` with a 422 status — 403 would be more appropriate
- `verifyOtp` returns 400 instead of the more standard 422 for validation-type failures
- `email_not_verified` case returns 400 rather than 403

### 1.4 CompatResponse Inconsistencies

- `CompatResponse::generic()` strips `hotel_id`, `service_category_id`, `hotel_service_id` from raw model output; `genericAlias` renames them instead
- `CompatResponse::booking()` renames `hotel_id`→`property`, `room_type_id`→`room_type`, `customer_id`→`customer`; does NOT strip `id` from these (raw ID is kept)
- `ImageUploadController::format()` does its own renaming (`hotel_id`→`property`, etc.) with `image_url` duplicated from `image` — inconsistent with CompatResponse
- `CompatResponse::hotel()` includes `readiness_errors` and `is_ready_to_publish` as computed fields not present on the model — mixed concerns

---

## 2. Validation Consistency

### 2.1 Endpoints WITH Validation

| Endpoint | Fields Validated | Rules |
|---|---|---|
| `AuthController::signup` | email, first_name, last_name, full_name, password, password_confirm | ✅ Full validation + manual `full_name` check |
| `AuthController::verifyOtp` | email, code | ✅ |
| `AuthController::resendOtp` | email | ✅ |
| `AuthController::login` | email, password | ✅ |
| `AuthController::updateMe` | full_name, phone | ✅ |
| `AuthController::changeUserRole` | role | `in:customer,staff,admin` |
| `AuthController::resetUserPassword` | password | `min:8` |
| `BookingController::store` | guest_name, guest_email, guest_phone, property, room_type, check_in, check_out, adults, children, total_guests, status | ✅ |
| `BookingController::inquiry` | customer_name, phone, email, property, room_type, check_in, check_out, adults, children, infants, extra_bed_needed, extra_bed_count, guests | ✅ |
| `BookingController::confirm` | booking_id | `exists:booking_inquiries,id` |
| `ReviewController::propertyReviews` (POST) | guest_name, guest_email, rating, title, comment, cleanliness, location, staff, comfort, value_for_money | ✅ |
| `ImageUploadController::store` | _request_key_, caption, alt_text, display_order, is_cover, is_active, image | ✅ |
| `ImageUploadController::update` | caption, alt_text, display_order, is_cover, is_active, image | ✅ |

### 2.2 Endpoints WITHOUT Validation

| Endpoint | Risk |
|---|---|
| **`HotelController::store`** | ❌ **No validation rules** — any arbitrary data accepted |
| **`CrudController::store`** (ALL generic CRUD: users, admins, room-types, room-prices, amenities, services, availability-blocks, service-categories, contact-messages, channel-manager-connections) | ❌ **No validation rules** — `normalizeInput()` only renames fields, does not validate |
| **`CrudController::update`** (ALL generic CRUD) | ❌ **No validation rules** — identical issue |
| **`HotelController::autosave`** (delegates to `CrudController::update`) | ❌ **No validation rules** — inherits from parent |
| **`ReviewController::store`/`update`** (inherited from CrudController) | ❌ **No validation rules** — `propertyReviews` (POST) has validation, but `reviews.store` does not |
| **`HotelController::publish/unpublish/archive/unarchive`** | ⚠️ No input validation (only `int $id`) |
| **`AuthController::activateUser/deactivateUser`** | ⚠️ No input validation (only `int $id`) |

### 2.3 Boolean Validation Inconsistency

| Location | Approach |
|---|---|
| `ImageUploadController::store/update` | `boolean` rule + `normalizeBooleans()` converts string `"true"/"1"/"yes"` |
| `BookingController::inquiry` (`extra_bed_needed`) | `nullable`, `boolean` rule only |
| `CrudController::applyFilters` | Manual `$value === 'true'` string check |
| `Hotel model` (`parking_available`, etc.) | Cast as `boolean` at model level |

**Issue:** Image upload controller manually pre-processes booleans, while other endpoints rely on Laravel's automatic conversion. This means `"true"` as a string may pass in some places but not others depending on whether `normalizeBooleans()` is called.

### 2.4 CSRF Protection

- **CSRF is NOT and SHOULD NOT be applied** — API routes in `routes/api.php` use `api` guard via `bootstrap/app.php`, so CSRF middleware is not applied. ✅ Correct.

---

## 3. Exception Handling

### 3.1 Current Setup (bootstrap/app.php)

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*'),
    );
})->create();
```

- **No custom `app/Exceptions/Handler.php` exists** — Laravel 11 uses `bootstrap/app.php` for exception config.
- All API requests are rendered as JSON by default.

### 3.2 Exception Handling by Type

| Exception Type | Status | Response Format | Handled Where? |
|---|---|---|---|
| `ValidationException` | 422 | `{message, errors}` | Laravel default |
| `ModelNotFoundException` | 404 | `{message}` | Laravel default |
| `AuthenticationException` | 401 | `{message}` | Laravel default |
| `AuthorizationException` | 403 | `{message}` | Laravel default |
| `NotFoundHttpException` (404 route) | 404 | `{message}` | Laravel default |
| `ThrottleRequestsException` | 429 | `{message}` | Laravel default |
| Custom inline errors | 400, 401, 403 | `{detail}`, `{code, detail}`, etc. | Manual in controllers |

### 3.3 Issues

- **No centralized error rendering** — errors are thrown inline with different structures (`detail` vs `message`+`errors`)
- **No custom exception classes** for domain-specific errors (e.g., `InactiveAccountException`, `EmailNotVerifiedException`, `InvalidOTPException`)
- **HTTP status codes for validation-style errors are inconsistent** (400 vs 422)
- **`ValidationException::withMessages(['detail' => '...'])`** used in `login` and `signup` — because the key is `detail`, the Laravel default `{message, errors: {detail: [...]}}` shape looks unusual

---

## 4. HTTP Status Codes

### 4.1 Status Code Usage

| Status Code | Used In | Count |
|---|---|---|
| **200 OK** | Most GET, PATCH, POST actions (list, show, update, me, publish, cancel, etc.) | Default |
| **201 Created** | `signup`, `HotelController::store`, `BookingController::store`, `BookingController::inquiry`, `ReviewController::propertyReviews` (POST), `ImageUploadController::store`, `CrudController::store` | All store/create |
| **204 No Content** | `logout`, `CrudController::destroy`, `ImageUploadController::destroy` | All delete |
| **400 Bad Request** | `verifyOtp` (invalid_code), `login` (email_not_verified) | 2 places |
| **401 Unauthorized** | `me`, `updateMe`, `RequireRole` middleware (no user) | 3 places |
| **403 Forbidden** | `login` (role check), `RequireRole` middleware (wrong role) | 3 places |
| **404 Not Found** | `findOrFail`, `abort(404)` in ImageUploadController | Default Laravel |
| **422 Unprocessable Entity** | All `ValidationException` throws | Default Laravel |
| **500 Server Error** | Uncaught PHP errors | Not explicitly handled |

### 4.2 Inconsistencies

- `verifyOtp`: returns **400** for invalid code — should arguably be **422** (validation error) or **401** (auth error)
- `login` (email not verified): returns **400** — should arguably be **403** (forbidden until verified)
- `login` (role check): returns **403** ✅ correct
- `logout`: returns **204** with null body — this is correct for a delete action, but some APIs use 200 with a success message
- `BookingController::confirm`: returns **200** (not 201) — acceptable since it's updating, not creating

---

## 5. Authentication & Authorization

### 5.1 Auth Middleware Stack

All routes are wrapped in:

```php
Route::middleware(['tenant.context', 'api.token'])->group(function () { ... });
```

- **`api.token`** (`AuthenticateApiToken`): **OPTIONAL** auth — resolves user from token if present, but does NOT block unauthenticated requests
- **`role:admin`** (`RequireRole`): blocks non-admin users; applied to auth admin sub-routes

### 5.2 Endpoint Auth Requirements

| Endpoint | Auth Required? | Role Required? | Notes |
|---|---|---|---|
| `GET /health/` | ❌ No | — | Public |
| `GET /public/hotel-context/` | ❌ No | — | Public |
| All `/auth/` routes | ❌ No (except `me`, `updateMe`, `logout`) | — | Signup/login must be public |
| `GET /auth/me/` | ✅ Yes (checked inline) | — | Returns 401 if no user |
| `PATCH /auth/me/` | ✅ Yes (checked inline) | — | Returns 401 if no user |
| `POST /auth/logout/` | ✅ Yes (inline check not present but no-op without token) | — | Silently succeeds even without auth |
| `auth/users/*`, `auth/admins/*` | ✅ Yes | `admin` | Via `role:admin` middleware |
| `auth/users/{id}/activate/` etc. | ✅ Yes | `admin` | Via `role:admin` middleware |
| `GET /properties/available/` | ❌ No | — | Public listing |
| `GET /properties/{id}` | ❌ No | — | Public read |
| `POST /properties` | ⚠️ No (api.token but no role check) | — | **Any auth user can create hotels** |
| `PATCH /properties/{id}` | ⚠️ No role check | — | **Any auth user can update any hotel** |
| `DELETE /properties/{id}` | ⚠️ No role check | — | **Any auth user can delete any hotel** |
| `POST /properties/{id}/publish/` etc. | ⚠️ No role check | — | **Any auth user can publish/archive** |
| `GET /bookings` | ⚠️ No (but filters by user) | — | Returns empty if no auth |
| `POST /bookings` | ❌ No | — | No auth required to create booking |
| `PATCH/DELETE /bookings/{id}` | ⚠️ No role check | — | Any auth user can modify any booking |
| All CRUD (room-types, services, etc.) | ⚠️ No role check | — | Any auth user can CRUD |
| Image uploads | ⚠️ No role check | — | Any auth user can upload/delete images |
| `POST /bookings/inquiry/` | ❌ No | — | Public inquiry |
| `POST /bookings/confirm/` | ❌ No | — | No auth needed to confirm |

### 5.3 Critical Auth Issues

1. **No endpoint-level auth enforcement** — `api.token` middleware is optional; most endpoints rely on `$request->user()` being null but never block the action
2. **`HotelController::store`** is accessible to any authenticated user — no admin check
3. **`HotelController::publish/unpublish/archive`** are not role-restricted — any user can change publishing status
4. **`CrudController::store/update/destroy`** for `room-types`, `room-prices`, `service-categories`, etc. — no role check
5. **`ImageUploadController`** CRUD has no role check — any user can upload/delete images
6. **`BookingController::destroy`** (inherited from CrudController) allows any auth user to delete any booking

---

## 6. Endpoint Completeness

### 6.1 Complete Route-to-Controller Map

#### Auth Routes (`/auth/*`)

| Method | URI | Controller Method | Status |
|---|---|---|---|
| POST | `/auth/signup/` | `AuthController::signup()` | ✅ Functional |
| POST | `/auth/verify-otp/` | `AuthController::verifyOtp()` | ✅ Functional |
| POST | `/auth/resend-otp/` | `AuthController::resendOtp()` | ✅ Functional |
| POST | `/auth/login/` | `AuthController::login()` | ✅ Functional |
| POST | `/auth/admin/login/` | `AuthController::adminLogin()` | ✅ Functional |
| POST | `/auth/customer/login/` | `AuthController::customerLogin()` | ✅ Functional |
| GET | `/auth/me/` | `AuthController::me()` | ✅ Functional |
| PATCH | `/auth/me/` | `AuthController::updateMe()` | ✅ Functional |
| POST | `/auth/logout/` | `AuthController::logout()` | ✅ Functional |
| GET | `/auth/users` | `CrudController::index()` | ✅ Functional |
| POST | `/auth/users` | `CrudController::store()` | ✅ Functional |
| GET | `/auth/users/{id}` | `CrudController::show()` | ✅ Functional |
| PATCH | `/auth/users/{id}` | `CrudController::update()` | ✅ Functional |
| DELETE | `/auth/users/{id}` | `CrudController::destroy()` | ✅ Functional |
| GET | `/auth/admins` | `CrudController::index()` | ✅ Functional |
| POST | `/auth/admins` | `CrudController::store()` | ✅ Functional |
| GET | `/auth/admins/{id}` | `CrudController::show()` | ✅ Functional |
| PATCH | `/auth/admins/{id}` | `CrudController::update()` | ✅ Functional |
| DELETE | `/auth/admins/{id}` | `CrudController::destroy()` | ✅ Functional |
| POST | `/auth/users/{id}/activate/` | `AuthController::activateUser()` | ✅ Functional |
| POST | `/auth/users/{id}/deactivate/` | `AuthController::deactivateUser()` | ✅ Functional |
| POST | `/auth/users/{id}/change-role/` | `AuthController::changeUserRole()` | ✅ Functional |
| POST | `/auth/users/{id}/reset-password/` | `AuthController::resetUserPassword()` | ✅ Functional |

#### Hotel Routes (`/properties/*`)

| Method | URI | Controller Method | Status |
|---|---|---|---|
| GET | `/properties/available/` | `HotelController::index()` | ✅ Functional |
| GET | `/properties` | `CrudController::index()` → `HotelController::index()` | ✅ Overridden |
| POST | `/properties` | `CrudController::store()` → `HotelController::store()` | ✅ Overridden |
| GET | `/properties/{id}` | `CrudController::show()` (inherited) | ✅ Functional |
| PATCH | `/properties/{id}` | `CrudController::update()` (inherited) | ✅ Functional |
| DELETE | `/properties/{id}` | `CrudController::destroy()` (inherited) | ✅ Functional |
| POST | `/properties/{id}/publish/` | `HotelController::publish()` | ✅ Functional |
| POST | `/properties/{id}/unpublish/` | `HotelController::unpublish()` | ✅ Functional |
| POST | `/properties/{id}/archive/` | `HotelController::archive()` | ✅ Functional |
| POST | `/properties/{id}/unarchive/` | `HotelController::unarchive()` | ✅ Functional |
| GET | `/properties/{id}/readiness/` | `HotelController::readiness()` | ⚠️ **Stub — hardcoded response** |
| GET | `/properties/{id}/setup-status/` | `HotelController::setupStatus()` | ⚠️ **Stub — hardcoded response** |
| PATCH | `/properties/{id}/autosave/` | `HotelController::autosave()` | ✅ Functional |
| GET | `/properties/{id}/workspace/` | `HotelController::workspace()` | ✅ Functional |
| GET | `/properties/{id}/rooms/search/` | `HotelController::roomsSearch()` | ✅ Functional |
| GET | `/properties/{id}/availability/` | `HotelController::availability()` | ✅ Functional |
| GET | `/properties/{id}/rates/` | `HotelController::rates()` | ✅ Functional |
| GET/POST | `/properties/{property}/reviews/` | `ReviewController::propertyReviews()` | ✅ Functional |
| GET | `/properties/{property}/reviews/summary/` | `ReviewController::summary()` | ✅ Functional |
| GET | `/public/hotel-context/` | `HotelController::publicContext()` | ✅ Functional |

#### Booking Routes (`/bookings/*`)

| Method | URI | Controller Method | Status |
|---|---|---|---|
| GET | `/bookings` | `BookingController::index()` | ✅ Functional |
| POST | `/bookings` | `BookingController::store()` | ✅ Functional |
| GET | `/bookings/{id}` | `CrudController::show()` (inherited) | ✅ Functional |
| PATCH | `/bookings/{id}` | `CrudController::update()` (inherited) | ✅ Functional |
| DELETE | `/bookings/{id}` | `CrudController::destroy()` (inherited) | ✅ Functional |
| GET | `/bookings/calendar/` | `BookingController::calendar()` | ⚠️ **Delegates to `index()` — same as list** |
| POST | `/bookings/inquiry/` | `BookingController::inquiry()` | ✅ Functional |
| POST | `/bookings/confirm/` | `BookingController::confirm()` | ✅ Functional |
| POST | `/bookings/{id}/cancel/` | `BookingController::cancel()` | ✅ Functional |

#### Review Routes (`/reviews/*`)

| Method | URI | Controller Method | Status |
|---|---|---|---|
| GET | `/reviews` | `ReviewController::index()` (inherited) | ✅ Functional |
| GET | `/reviews/{id}` | `ReviewController::show()` (inherited) | ✅ Functional |
| PATCH | `/reviews/{id}` | `ReviewController::update()` (inherited) | ✅ Functional |
| DELETE | `/reviews/{id}` | `ReviewController::destroy()` (inherited) | ✅ Functional |

#### Generic CRUD Routes

| Method | URI | Controller Method | Status |
|---|---|---|---|
| GET/POST | `/property-amenities[/{id}]` | `CrudController` bound to `HotelAmenity` | ✅ Functional |
| GET/POST | `/channel-manager-connections[/{id}]` | `CrudController` bound to `ChannelManagerConnection` | ✅ Functional |
| GET/POST | `/room-types[/{id}]` | `CrudController` bound to `RoomType` | ✅ Functional |
| GET | `/room-types/{id}/rates/` | Closure — **stub** | ❌ **Stub — returns `{room_type: $id, seasonal_prices: []}`** |
| GET/POST | `/room-prices[/{id}]` | `CrudController` bound to `RoomPrice` | ✅ Functional |
| GET/POST | `/room-amenities[/{id}]` | `CrudController` bound to `HotelAmenity` | ✅ Functional |
| GET/POST | `/availability-blocks[/{id}]` | `CrudController` bound to `AvailabilityBlock` | ✅ Functional |
| GET/POST | `/service-categories[/{id}]` | `CrudController` bound to `ServiceCategory` | ✅ Functional |
| GET/POST | `/property-services[/{id}]` | `CrudController` bound to `HotelService` | ✅ Functional |
| GET/POST | `/audit-logs[/{id}]` | `CrudController` bound to `AuditLog` (index, show only) | ✅ Functional |
| GET/POST | `/contact-messages[/{id}]` | `CrudController` bound to `ContactMessage` | ✅ Functional |

#### Image Routes

| Method | URI | Controller Method | Status |
|---|---|---|---|
| GET/POST/DELETE | `/property-images[/{id}]` | `ImageUploadController` | ✅ Functional |
| GET/POST/DELETE | `/room-type-images[/{id}]` | `ImageUploadController` | ✅ Functional |
| GET/POST/DELETE | `/service-images[/{id}]` | `ImageUploadController` | ✅ Functional |

### 6.2 Stubs & Suspicious Routes

| Route | Issue |
|---|---|
| `GET /properties/{id}/readiness/` | **Stub** — always returns `{is_ready_to_publish: true, errors: []}` |
| `GET /properties/{id}/setup-status/` | **Stub** — always returns `{completion_percentage: 0, ...}` |
| `GET /room-types/{id}/rates/` | **Stub** — closure returns hardcoded `{room_type: $id, seasonal_prices: []}` |
| `GET /bookings/calendar/` | **Suspicious** — delegates to `index()` with same logic, no calendar-specific behavior |
| `HotelController::readiness` + `setupStatus` + `workspace` | **Stub data** — `setup.completion_percentage` always 0, `recent_activity` always empty |

### 6.3 Potential 500-Level Errors

| Location | Risk |
|---|---|
| `HotelController::publish/unpublish/archive/unarchive` | PHP type hint `int $id` but route has **no `whereNumber` constraint** — passing a non-numeric value will cause a TypeError |
| `ImageUploadController::show/update/destroy` | Route has `whereNumber('id')` — safe despite `string $id` parameter |
| `CrudController` binding at bottom of `routes/api.php` | `request()->route()?->getName()` may return `null` if route name is not set — falls through to default `HotelAmenity` |
| `CompatResponse::hotel()` → `$hotel->reviews()` | Called without eager-loading — N+1 query, but won't crash |
| `BookingController::inquiry` → `$booking->check_in->format(...)` | If `check_in` is null (shouldn't be given required validation), would crash |

---

## 7. Summary of Critical Issues

### 🔴 High Severity

1. **No input validation on `CrudController::store` and `CrudController::update`** — used by 10+ resource types including users, room-types, room-prices, and contact-messages. Accepts arbitrary data.
2. **No input validation on `HotelController::store`** — accepts any fields without validation rules.
3. **No role-based access control** on hotel mutations, generic CRUD, booking modifications, or image operations — any authenticated user can create/update/delete any resource.
4. **Publish/unpublish/archive endpoints lack auth scoping** — any user can change hotel publishing status.

### 🟡 Medium Severity

5. **Inconsistent error response formats** — some use `{detail}`, some use `{message, errors}`.
6. **Mixed HTTP status codes** for similar error types (400 vs 422 vs 403).
7. **Three hardcoded stubs** (`readiness`, `setupStatus`, `/room-types/{id}/rates/`) return fake data.
8. **Response shape inconsistency** — `BookingController::inquiry` and `confirm` return custom flat responses instead of `CompatResponse::booking()`.
9. **`BookingController::calendar` is a no-op alias** of `index()`.
10. **Booleans handled inconsistently** across controllers (manual conversion vs `boolean` rule).

### 🟢 Low Severity

11. `HotelController::publish` and friends lack `whereNumber` ID constraint.
12. `ImageUploadController::index` never paginates (always returns `next: null, previous: null`).
13. `CompatResponse::item()` always calls `$hotel->reviews()` — potential performance issue.
14. `logout` silently succeeds even without a valid token.

---

*Report generated from static analysis of `routes/api.php`, all `app/Http/Controllers/Api/*` controllers, `app/Support/CompatResponse.php`, `app/Http/Middleware/*`, and `bootstrap/app.php`.*
