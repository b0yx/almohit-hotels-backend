# Audit Coverage Gap Analysis

**Date:** 2026-06-24  
**Scope:** Every Create, Update, Delete, Status Change, Booking Change, and User Change endpoint  
**Methodology:** Route inventory → controller method cross-reference → grep for `AuditLog::create()`  

---

## Summary

| Metric | Value |
|--------|-------|
| Total mutating endpoints | **34** |
| Endpoints with audit logging | **4** |
| Endpoints missing audit logging | **30** |
| **Coverage** | **11.8%** |
| Audit log write locations | `app/Http/Controllers/Api/AuthController.php:219,243,271,295` |

All 4 existing audit logs cover **user admin actions only** (activate, deactivate, change role, reset password). Every other write operation — property CRUD, bookings, reviews, images, room types, amenities, services, prices, availability blocks, contact messages, profile updates, OTP events — has **zero** audit logging.

---

## Audit Log Schema

`audit_logs` table columns available:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-generated |
| `action` | varchar | e.g. `activate`, `deactivate`, `change_role`, `reset_password` |
| `content_type` | varchar | e.g. `user` |
| `object_id` | varchar | String representation of the affected entity's PK |
| `object_repr` | varchar | Human-readable representation (e.g. email, name) |
| `actor_id` | bigint nullable | FK to `users.id` |
| `actor_email` | varchar | Denormalized for historical accuracy |
| `actor_name` | varchar | Denormalized for historical accuracy |
| `changes` | json nullable | Diff-style `{"field": {"old": ..., "new": ...}}` |
| `request_method` | varchar | e.g. `POST`, `PATCH`, `DELETE` |
| `request_path` | varchar | e.g. `api/auth/users/4/activate` |
| `ip_address` | inet nullable | Client IP |
| `created_at` | timestamp | When the event occurred |

---

## Endpoint Audit Coverage Table

### 1. User Changes (6 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 1 | `/api/auth/signup` | POST | `AuthController@signup` | Creates user account | ❌ | — |
| 2 | `PATCH /api/auth/me` | PATCH | `AuthController@updateMe` | Updates own profile | ❌ | — |
| 3 | `/api/auth/users` | POST | `CrudController@store` | Admin creates user | ❌ | — |
| 4 | `/api/auth/users/{id}` | PUT/PATCH | `CrudController@update` | Admin updates user | ❌ | — |
| 5 | `/api/auth/users/{id}` | DELETE | `CrudController@destroy` | Admin deletes user | ❌ | — |
| 6 | `/api/auth/logout` | POST | `AuthController@logout` | Revokes token session | ❌ | — |

### 2. User Status/Role/Password Changes (4 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 7 | `/api/auth/users/{id}/activate` | POST | `AuthController@activateUser` | Activates user | ✅ | `AuthController:219` |
| 8 | `/api/auth/users/{id}/deactivate` | POST | `AuthController@deactivateUser` | Deactivates user | ✅ | `AuthController:243` |
| 9 | `/api/auth/users/{id}/change-role` | POST | `AuthController@changeUserRole` | Changes user role | ✅ | `AuthController:271` |
| 10 | `/api/auth/users/{id}/reset-password` | POST | `AuthController@resetUserPassword` | Admin resets user password | ✅ | `AuthController:295` |

### 3. OTP / Password Flow (5 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 11 | `/api/auth/verify-otp` | POST | `AuthController@verifyOtp` | Verifies email OTP | ❌ | — |
| 12 | `/api/auth/resend-otp` | POST | `AuthController@resendOtp` | Resends OTP code | ❌ | — |
| 13 | `/api/auth/forgot-password` | POST | `AuthController@forgotPassword` | Requests password reset | ❌ | — |
| 14 | `/api/auth/reset-password` | POST | `AuthController@resetPassword` | Completes password reset | ❌ | — |

### 4. Property/Hotel Changes (10 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 15 | `/api/properties` | POST | `HotelController@store` | Creates hotel | ❌ | — |
| 16 | `/api/properties/{id}` | PUT/PATCH | `HotelController@update` | Updates hotel | ❌ | — |
| 17 | `/api/properties/{id}` | DELETE | `HotelController@destroy` | Deletes hotel | ❌ | — |
| 18 | `/api/properties/{id}/publish` | POST | `HotelController@publish` | Publishes hotel | ❌ | — |
| 19 | `/api/properties/{id}/unpublish` | POST | `HotelController@unpublish` | Unpublishes hotel | ❌ | — |
| 20 | `/api/properties/{id}/archive` | POST | `HotelController@archive` | Archives hotel | ❌ | — |
| 21 | `/api/properties/{id}/unarchive` | POST | `HotelController@unarchive` | Unarchives hotel | ❌ | — |
| 22 | `/api/properties/{id}/autosave` | PATCH | `HotelController@autosave` | Autosaves hotel draft | ❌ | — |
| 23 | `/api/auth/admins` | POST | `CrudController@store` | Creates admin user | ❌ | — |
| 24 | `/api/auth/admins/{id}` | PUT/PATCH | `CrudController@update` | Updates admin user | ❌ | — |
| 25 | `/api/auth/admins/{id}` | DELETE | `CrudController@destroy` | Deletes admin user | ❌ | — |

### 5. Booking Changes (6 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 26 | `/api/bookings` | POST | `BookingController@store` | Creates booking | ❌ | — |
| 27 | `/api/bookings/{id}` | PUT/PATCH | `BookingController@update` | Updates booking | ❌ | — |
| 28 | `/api/bookings/{id}` | DELETE | `BookingController@destroy` | Deletes booking | ❌ | — |
| 29 | `/api/bookings/inquiry` | POST | `BookingController@inquiry` | Creates booking inquiry | ❌ | — |
| 30 | `/api/bookings/confirm` | POST | `BookingController@confirm` | Confirms booking (status -> confirmed) | ❌ | — |
| 31 | `/api/bookings/{id}/cancel` | POST | `BookingController@cancel` | Cancels booking (status -> cancelled) | ❌ | — |

### 6. Image Changes (9 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 32 | `/api/property-images` | POST | `ImageUploadController@store` | Uploads property image | ❌ | — |
| 33 | `/api/property-images/{id}` | PATCH | `ImageUploadController@update` | Updates property image | ❌ | — |
| 34 | `/api/property-images/{id}` | DELETE | `ImageUploadController@destroy` | Deletes property image | ❌ | — |
| 35 | `/api/room-type-images` | POST | `ImageUploadController@store` | Uploads room type image | ❌ | — |
| 36 | `/api/room-type-images/{id}` | PATCH | `ImageUploadController@update` | Updates room type image | ❌ | — |
| 37 | `/api/room-type-images/{id}` | DELETE | `ImageUploadController@destroy` | Deletes room type image | ❌ | — |
| 38 | `/api/service-images` | POST | `ImageUploadController@store` | Uploads service image | ❌ | — |
| 39 | `/api/service-images/{id}` | PATCH | `ImageUploadController@update` | Updates service image | ❌ | — |
| 40 | `/api/service-images/{id}` | DELETE | `ImageUploadController@destroy` | Deletes service image | ❌ | — |

### 7. CRUD Resource Changes (30 endpoints)

All resources below use `CrudController` — **none** create audit logs.

| Resource | Create (POST) | Update (PUT/PATCH) | Delete (DELETE) | Audit? |
|----------|--------------|-------------------|-----------------|--------|
| `/api/room-types` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/room-prices` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/room-amenities` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/property-amenities` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/property-services` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/service-categories` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/availability-blocks` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |
| `/api/contact-messages` | `CrudController@store` | `CrudController@update` | `CrudController@destroy` | ❌ |

### 8. Review Changes (2 endpoints)

| # | Endpoint | Method | Controller | Action | Audit? | Location |
|---|----------|--------|------------|--------|--------|----------|
| 41 | `POST /api/properties/{property}/reviews` | POST (via GET\|POST) | `ReviewController@propertyReviews` | Creates review | ❌ | — |
| 42 | `/api/reviews/{id}` | PUT/PATCH | `ReviewController@update` *(inherited from CrudController)* | Updates review | ❌ | — |
| 43 | `/api/reviews/{id}` | DELETE | `ReviewController@destroy` *(inherited from CrudController)* | Deletes review | ❌ | — |

---

## Coverage Calculation

### Count

| Category | Total | Audited | Missing | Coverage |
|----------|-------|---------|---------|----------|
| User admin actions (activate/deactivate/role/reset) | 4 | 4 | 0 | **100%** |
| User CRUD (signup, create, update, delete, profile) | 5 | 0 | 5 | **0%** |
| Property/Hotel CRUD + status changes | 9 | 0 | 9 | **0%** |
| Booking CRUD + status changes | 6 | 0 | 6 | **0%** |
| Image upload CRUD | 9 | 0 | 9 | **0%** |
| CRUD resources (8 resources × 3 ops) | 24 | 0 | 24 | **0%** |
| Auth/OTP/Password events | 5 | 0 | 5 | **0%** |
| Review CRUD | 3 | 0 | 3 | **0%** |
| **Total** | **65** | **4** | **61** | **6.2%** |

**Endpoint-level coverage: 4 / 65 = 6.2%**

*(If counting by unique endpoint URIs instead: 4 / 34 = 11.8%)*

---

## All Existing Audit Log Creations

| File | Line | Action | Content Type |
|------|------|--------|-------------|
| `AuthController.php` | 219 | `activate` | `user` |
| `AuthController.php` | 243 | `deactivate` | `user` |
| `AuthController.php` | 271 | `change_role` | `user` |
| `AuthController.php` | 295 | `reset_password` | `user` |

**Only 1 content type (`user`) is audited. 0 content types for hotel, booking, review, image, room_type, amenity, service, price, availability, contact.**

---

## Recommended Missing Audit Events (by priority)

### P0 — Critical (financial/legal/compliance impact)

| Priority | Endpoint | Suggested `action` | Suggested `content_type` |
|----------|----------|-------------------|------------------------|
| P0 | `POST /api/bookings` (store) | `created` | `booking` |
| P0 | `POST /api/bookings/{id}/cancel` | `cancelled` | `booking` |
| P0 | `POST /api/bookings/confirm` | `confirmed` | `booking` |
| P0 | `PUT/PATCH /api/bookings/{id}` | `updated` | `booking` |
| P0 | `DELETE /api/bookings/{id}` | `deleted` | `booking` |

### P1 — High (property management integrity)

| Priority | Endpoint | Suggested `action` | Suggested `content_type` |
|----------|----------|-------------------|------------------------|
| P1 | `POST /api/properties` | `created` | `hotel` |
| P1 | `PUT/PATCH /api/properties/{id}` | `updated` | `hotel` |
| P1 | `DELETE /api/properties/{id}` | `deleted` | `hotel` |
| P1 | `POST /api/properties/{id}/publish` | `published` | `hotel` |
| P1 | `POST /api/properties/{id}/unpublish` | `unpublished` | `hotel` |
| P1 | `POST /api/properties/{id}/archive` | `archived` | `hotel` |
| P1 | `POST /api/properties/{id}/unarchive` | `unarchived` | `hotel` |

### P2 — Medium (content/data integrity)

| Priority | Endpoint | Suggested `action` | Suggested `content_type` |
|----------|----------|-------------------|------------------------|
| P2 | `POST /api/room-types` | `created` | `room_type` |
| P2 | `PUT/PATCH /api/room-types/{id}` | `updated` | `room_type` |
| P2 | `DELETE /api/room-types/{id}` | `deleted` | `room_type` |
| P2 | `POST /api/room-prices` | `created` | `room_price` |
| P2 | `PUT/PATCH /api/room-prices/{id}` | `updated` | `room_price` |
| P2 | `POST /api/properties/{property}/reviews` | `created` | `review` |
| P2 | `DELETE /api/reviews/{id}` | `deleted` | `review` |

### P3 — Low (user management)

| Priority | Endpoint | Suggested `action` | Suggested `content_type` |
|----------|----------|-------------------|------------------------|
| P3 | `POST /api/auth/signup` | `registered` | `user` |
| P3 | `PATCH /api/auth/me` | `profile_updated` | `user` |
| P3 | `POST /api/auth/logout` | `logout` | `session` |
| P3 | `POST /api/auth/verify-otp` | `otp_verified` | `user` |
| P3 | `POST /api/auth/reset-password` | `password_reset` | `user` |
| P3 | `POST /api/auth/forgot-password` | `password_reset_requested` | `user` |

### P4 — Lowest (image/media)

| Priority | Endpoint | Suggested `action` | Suggested `content_type` |
|----------|----------|-------------------|------------------------|
| P4 | `POST /api/property-images` | `uploaded` | `hotel_image` |
| P4 | `DELETE /api/property-images/{id}` | `deleted` | `hotel_image` |
| P4 | (same for room-type-images and service-images) | | |

---

## Implementation Notes

1. **CrudController is the key leverage point.** Since 24 of the 61 missing endpoints route through `CrudController::store()`, `CrudController::update()`, and `CrudController::destroy()`, adding a single `AuditLog::create()` call in each of these 3 methods would cover **all 8 CRUD resources** at once.

2. **Model-level auditing (e.g. Eloquent events/boot)** would be more robust than controller-level. Using `Model::created()`, `Model::updated()`, `Model::deleted()` boot methods would guarantee no endpoint ever misses an audit, even if routes change.

3. **AuditLog model already supports the full schema.** No schema changes needed — just start writing rows.

4. **Existing pattern to follow.** The `activateUser` block in `AuthController:219-232` is the canonical pattern:
   ```php
   AuditLog::query()->create([
       'action' => 'activate',
       'content_type' => 'user',
       'object_id' => (string) $user->id,
       'object_repr' => $user->email,
       'actor_id' => request()->user()?->id,
       'actor_email' => request()->user()?->email ?? '',
       'actor_name' => request()->user()?->full_name ?? '',
       'changes' => ['is_active' => ['old' => $wasActive, 'new' => true]],
       'request_method' => request()->method(),
       'request_path' => request()->path(),
       'ip_address' => request()->ip(),
       'created_at' => now(),
   ]);
   ```

---

## Coverage Target

If all P0 and P1 items are implemented:

- 4 existing + 12 new = 16 audited endpoints
- Coverage = 16/65 = **24.6%**

If all P0–P3 items are implemented:

- 4 existing + 30 new = 34 audited endpoints
- Coverage = 34/65 = **52.3%**

If all items (P0–P4) are implemented + CrudController model-level auditing:

- Coverage would approach **100%** — every write operation recorded.
