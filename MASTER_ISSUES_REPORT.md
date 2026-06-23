# Master Issues Report — Almohit Hotels API

**Date:** 2026-06-23
**Inspector:** Laravel Architect & Security Audit

---

## Critical Issues

### C1 — User::isAdmin() grants admin access to staff users

| Field | Value |
|-------|-------|
| **File** | `app/Models/User.php:51-54` |
| **Code** | `return (bool) $this->is_staff \|\| $this->role === self::ROLE_ADMIN;` |
| **Impact** | Staff users (`role='staff'`, `is_staff=true`) pass `isAdmin()` checks, giving them access to all admin-only routes |
| **Risk** | Staff can list/manage users, change user roles, activate/deactivate accounts, reset passwords |
| **Fix** | Change to `return $this->role === self::ROLE_ADMIN;` |

### C2 — No rate limiting on auth endpoints

| Field | Value |
|-------|-------|
| **File** | `routes/api.php:66-84` |
| **Impact** | Login, signup, OTP verification, OTP resend have no throttling |
| **Risk** | Brute-force password attacks, DDoS account creation, OTP brute force |
| **Fix** | Add Laravel `throttle` middleware to auth routes |

---

## High Issues

### H1 — OTP brute-force vulnerability

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/AuthController.php:59-73` |
| **Impact** | 6-digit OTP is verifiable without attempt tracking. Combined with no rate limiting, all 1M combinations can be tried |
| **Risk** | Account takeover via OTP brute force |
| **Fix** | Track OTP attempts, lock after 5 failures, add throttle |

### H2 — OTP resend lacks throttle

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/AuthController.php:75-89` |
| **Impact** | No cooldown on OTP resend |
| **Risk** | SMS/email spam attack, mailbox flooding |
| **Fix** | Add minimum 30-second cooldown between resends |

### H3 — Staff users can access admin routes

| Field | Value |
|-------|-------|
| **File** | `routes/api.php:76-83`, `app/Http/Middleware/RequireRole.php:18` |
| **Impact** | `role:admin` middleware uses `isAdmin()` which returns true for staff users |
| **Risk** | Staff can manage users, change roles, activate/deactivate accounts |
| **Fix** | Fix `isAdmin()` (see C1), which fixes this transitively |

### H4 — ImageUploadController lacks ownership verification

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/ImageUploadController.php:69-108` |
| **Impact** | Any staff user can upload images to any property, regardless of assignment |
| **Risk** | Staff can modify images for properties they don't manage |
| **Fix** | Verify property ownership or staff assignment for all image operations |

---

## Medium Issues

### M1 — forceFill bypasses mass assignment protection

| Field | Value |
|-------|-------|
| **File** | Multiple — AuthController, HotelController, BookingController, ImageUploadController |
| **Impact** | `forceFill` is used to bypass `$guarded`/`$fillable` on models |
| **Risk** | If any new fields are added to guarded arrays, forceFill will still set them |
| **Fix** | Replace `forceFill` with `fill` + explicit field setting where possible |

### M2 — resetUserPassword lacks current password verification

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/AuthController.php:200-207` |
| **Impact** | Admin can reset any user's password without confirming the user's existing password |
| **Risk** | Low for admin-initiated resets (by design), but should log the action |
| **Fix** | Add audit logging for password resets |

### M3 — Booking confirm endpoint lacks authorization

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/BookingController.php:155-167` |
| **Impact** | Any authenticated user can confirm any booking by ID |
| **Risk** | Unauthorized booking confirmation |
| **Fix** | Check that user is staff/admin for the booking's hotel |

### M4 — Booking cancel endpoint lacks ownership check

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/BookingController.php:169-175` |
| **Impact** | Any authenticated user can cancel any booking by ID |
| **Fix** | Check user owns the booking or is staff for the hotel |

### M5 — changeUserRole allows setting admin role

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/AuthController.php:188-198` |
| **Impact** | Admin can elevate any user to admin — this is acceptable by design but should be audited |
| **Fix** | Add audit log entry when role is changed |

### M6 — ResolvePublicHotel doesn't verify owner_id for staff operations

| Field | Value |
|-------|-------|
| **File** | `app/Http/Middleware/ResolvePublicHotel.php` |
| **Impact** | The middleware resolves hotels for public context but doesn't enforce which staff can access which hotel |
| **Risk** | Staff could potentially view/edit non-assigned hotels through some endpoints |
| **Fix** | Add staff-hotel assignment checks in HotelController where needed |

---

## Low Issues

### L1 — Dead code: ensureSlug() in CrudController

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/CrudController.php:213-220` |
| **Fix** | Remove unused method |

### L2 — Dead code: calendar() aliases index() in BookingController

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/BookingController.php:150-153` |
| **Fix** | Remove or implement properly |

### L3 — /room-types/{id}/rates/ returns hardcoded empty data

| Field | Value |
|-------|-------|
| **File** | `routes/api.php:115` |
| **Fix** | Route returns `['room_type' => $id, 'seasonal_prices' => []]` — placeholder stub |

### L4 — ContactMessage has no public submission endpoint

| Field | Value |
|-------|-------|
| **File** | `routes/api.php:124` |
| **Impact** | Contact messages can only be viewed by staff, never submitted via API |
| **Fix** | Add a public contact form endpoint (future feature) |

### L5 — workspace() returns hardcoded empty data

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/HotelController.php:151-162` |
| **Impact** | Returns empty arrays for recent_activity, missing_tasks, recommended_next_action |
| **Fix** | Implement properly or remove |

### L6 — CrudController binding in routes/api.php is unconventional

| Field | Value |
|-------|-------|
| **File** | `routes/api.php:127-153` |
| **Impact** | Model-to-route mapping is done in routes file instead of a service provider |
| **Fix** | Move to AppServiceProvider or dedicated service provider (refactor, not now) |

### L7 — normalizeInput strips amenity_ids, amenities, guests from all requests

| Field | Value |
|-------|-------|
| **File** | `app/Http/Controllers/Api/CrudController.php:167` |
| **Impact** | These keys are unconditionally removed from all model inputs |
| **Fix** | Only strip for models that don't use them |

---

## Summary

| Severity | Count |
|----------|:-----:|
| Critical | 2 |
| High | 4 |
| Medium | 6 |
| Low | 7 |
| **Total** | **19** |
