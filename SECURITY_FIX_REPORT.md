# Security Fix Report — Almohit Hotels API

**Date:** 2026-06-23

---

## Fixes Applied

### C1 — User::isAdmin() grants admin access to staff users

**File:** `app/Models/User.php:51-54`

**Before:** `return (bool) $this->is_staff || $this->role === self::ROLE_ADMIN;`

This caused staff users (`role='staff'`, `is_staff=true`) to pass all admin checks. Staff could manage users, change roles, activate/deactivate accounts, and reset passwords.

**After:** `return $this->role === self::ROLE_ADMIN;`

Now only users with `role='admin'` pass admin authorization checks.

### C2 — No rate limiting on auth endpoints

**File:** `routes/api.php`

Added Laravel `throttle` middleware to all auth endpoints:

| Endpoint | Limit | Window |
|----------|-------|--------|
| `POST /signup/` | 5 req | 30 min |
| `POST /verify-otp/` | 10 req | 15 min |
| `POST /resend-otp/` | 3 req | 60 min |
| `POST /login/` | 10 req | 15 min |
| `POST /admin/login/` | 10 req | 15 min |
| `POST /customer/login/` | 10 req | 15 min |

### H1 — OTP brute-force vulnerability

**File:** `app/Http/Controllers/Api/AuthController.php:59-83`

Before: OTP verification had no attempt tracking. Combined with no rate limiting, an attacker could brute-force the 6-digit code (1M combinations).

After: Added `RateLimiter`-based attempt tracking — 5 failed attempts locks OTP for 15 minutes per email. Successful verification clears the attempt counter.

### H2 — OTP resend lacks throttle

**File:** `app/Http/Controllers/Api/AuthController.php:85-114`

Before: No cooldown on OTP resend — could flood user's email/SMS.

After: Added `RateLimiter`-based cooldown — 3 resends per 60 seconds per email.

### H4 — ImageUploadController lacks ownership verification

**File:** `app/Http/Controllers/Api/ImageUploadController.php`

Before: Any staff user could upload images to any property.

After: Staff users are now verified against the hotel's `assignedStaff` relationship. For room-type and service images, the check traverses through the parent hotel's staff assignments. Admin users are exempt from this check.

### M3 — Booking confirm lacks authorization

**File:** `app/Http/Controllers/Api/BookingController.php:155-184`

Before: Any authenticated user could confirm any booking.

After: Only staff/admin users can confirm. Staff must be assigned to the booking's hotel.

### M4 — Booking cancel lacks ownership check

**File:** `app/Http/Controllers/Api/BookingController.php:186-207`

Before: Any authenticated user could cancel any booking.

After: Only the booking owner (customer), assigned staff, or admin can cancel a booking.

---

## Risk Remediation Summary

| Issue | Severity | Fixed | Risk After Fix |
|-------|----------|:-----:|----------------|
| Staff-as-admin escalation | Critical | ✅ | Eliminated |
| Auth endpoint abuse | Critical | ✅ | Throttled |
| OTP brute force | High | ✅ | 5 attempts per 15 min |
| OTP resend spam | High | ✅ | 3 attempts per 60 sec |
| Image upload bypass | High | ✅ | Staff assignment enforced |
| Booking confirm bypass | Medium | ✅ | Staff/admin only |
| Booking cancel bypass | Medium | ✅ | Owner/staff/admin only |

## Remaining Security Gaps

| Issue | Priority | Notes |
|-------|----------|-------|
| No security headers (CSP, HSTS) | Medium | Add via middleware |
| No token expiry/rotation | Low | Custom token auth limitation |
| Debug mode in `.env.example` | Low | Documented as dev-only |
