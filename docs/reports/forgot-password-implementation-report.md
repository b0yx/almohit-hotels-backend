# Forgot / Reset Password Implementation Report

## Summary

Implemented a production-quality OTP-based forgot password and reset password flow for the Almohit Hotels Laravel backend. The system uses a dedicated `password_reset_otps` table, separate from the existing signup OTP infrastructure.

---

## Architecture Decision: Dedicated Table

Created a new `password_reset_otps` table instead of reusing `email_otps` or the unused `password_reset_tokens` table:

| Table | Status | Reason |
|-------|--------|--------|
| `email_otps` | Untouched | Used for signup email verification; adding a `type` column would risk breaking existing flow |
| `password_reset_tokens` | Untouched | Built for token-based (URL) flow, not OTP; would require awkward schema changes |
| `password_reset_otps` | **New** | Clean separation, no risk to existing auth, tailored schema |

---

## Database Schema

```sql
CREATE TABLE password_reset_otps (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL,          -- user email (not user_id, to avoid early user resolution)
    otp_hash    VARCHAR(128) NOT NULL,          -- bcrypt hash of 6-digit code
    attempts    TINYINT UNSIGNED DEFAULT 0,     -- failed attempt counter
    used_at     TIMESTAMP NULL,                 -- null = valid, non-null = consumed/invalidated
    expires_at  TIMESTAMP NOT NULL,             -- 10 minutes from creation
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX (email)
);
```

---

## API Endpoints

### POST `/api/auth/forgot-password/`

**Rate limit:** 3 requests per 60 seconds

**Request:**
```json
{
    "email": "user@example.com"
}
```

**Response (always the same regardless of whether email exists):**
```json
{
    "detail": "If the email exists, an OTP has been sent."
}
```

In `local`/`testing` environments, `debug_code` is included if the email exists.

**Behavior:**
1. Validates email format
2. Looks up user by email
3. If user exists:
   - Invalidates all previous unused OTPs for that email (`used_at = now()`)
   - Generates secure 6-digit code via `random_int(100000, 999999)`
   - Stores bcrypt hash in `password_reset_otps`
   - Sets 10-minute expiration
   - Sends email via `PasswordResetOtpMail` mailable
4. If user does not exist: no OTP created, no hint in response
5. Returns identical response in both cases

### POST `/api/auth/reset-password/`

**Rate limit:** 10 requests per 15 seconds

**Request:**
```json
{
    "email": "user@example.com",
    "code": "123456",
    "password": "NewStr0ng!Pass",
    "password_confirmation": "NewStr0ng!Pass"
}
```

**Success response (200):**
```json
{
    "detail": "Password has been reset successfully."
}
```

**Error responses:**

| Status | Body | When |
|--------|------|------|
| 400 | `{"detail": "Invalid or expired OTP.", "code": "invalid_otp"}` | Wrong code |
| 400 | `{"detail": "Invalid or expired OTP."}` | No valid OTP / expired / already used / email doesn't exist |
| 429 | `{"detail": "Too many attempts. Please request a new OTP.", "code": "otp_locked"}` | 5+ failed attempts on same OTP |
| 422 | Validation error | Missing/invalid fields |

**Behavior:**
1. Validates: `email` (required), `code` (required, size:6), `password` (required, min:8, confirmed)
2. Looks up user by email — if not found, returns generic error
3. Fetches latest valid OTP (`used_at IS NULL` AND `expires_at > now()`)
4. Checks attempt count — if >= 5, returns locked error
5. Verifies OTP with `Hash::check()` — if fails, increments `attempts`
6. In a database transaction: marks OTP as used + updates user password
7. Password is auto-hashed by Laravel's `hashed` cast on the User model

---

## Security Measures

| Measure | Implementation |
|---------|---------------|
| OTP storage | bcrypt hash via `Hash::make()` |
| Email enumeration | Generic response regardless of email existence |
| Expiry | 10-minute `expires_at` column |
| Attempt lockout | 5 failed attempts lock the OTP (check on `attempts >= 5`) |
| Replay prevention | `used_at` set after successful use; previous OTPs invalidated on new request |
| Rate limiting | Laravel `throttle` middleware (3/60s forgot, 10/15s reset) |
| No auto-login | Password reset does NOT create a session or token |
| Atomic update | Password update wrapped in `DB::transaction()` |
| Password hashing | Laravel `hashed` cast on User model |

---

## Files Created

```
database/migrations/2026_06_24_000001_create_password_reset_otps_table.php
app/Models/PasswordResetOtp.php
app/Mail/PasswordResetOtpMail.php
resources/views/emails/password-reset-otp.blade.php
tests/Feature/PasswordResetTest.php
```

## Files Modified

```
app/Http/Controllers/Api/AuthController.php    (+82 lines, added 2 methods)
routes/api.php                                  (+2 lines, added 2 routes)
app/OpenApi/OpenApiSpec.php                     (+96 lines, added OpenAPI annotations)
```

---

## Email Template

Subject: **Reset Your Password**

Plain-text body:
```
We received a request to reset your password.

Your verification code is: {{ $code }}

This code will expire in 10 minutes.

If you did not request a password reset, please ignore this email.
```

The mailable class `App\Mail\PasswordResetOtpMail` has a public `$code` property and uses the plain-text view above. To use HTML, replace the blade template or switch the `content()` method to use `html:` instead of `view:`.

---

## Test Coverage (17 tests, 39 assertions)

| # | Test | What it verifies |
|---|------|-----------------|
| 1 | existing email returns generic response | No email enumeration |
| 2 | non-existing email returns same response | Same shape/status for both cases |
| 3 | OTP created for existing email | DB row with hash, expiry, 0 attempts |
| 4 | no OTP for non-existing email | Zero rows in table |
| 5 | previous OTPs invalidated on re-request | Old OTPs get `used_at` set |
| 6 | successful full flow reset | Password changes, OTP marked used |
| 7 | invalid OTP returns error | 400 with `invalid_otp` code |
| 8 | expired OTP returns error | Past `expires_at` rejected |
| 9 | used OTP returns error | Already consumed OTP rejected |
| 10 | locks after 5 failed attempts | 5 wrong codes → 429 with `otp_locked` |
| 11 | password confirmation required | Mismatch → 422 |
| 12 | non-existing email in reset returns generic error | No hint about email existence |
| 13 | password min length enforced | 5 chars → 422 |
| 14 | rate limiting on forgot-password | 4th request in burst → 429 |
| 15 | rate limiting on reset-password | 11th request in burst → 429 |
| 16 | email sent for existing user | `Mail::assertSent` |
| 17 | no email sent for non-existing user | `Mail::assertNothingSent` |

---

## Existing Auth Flow Compatibility

- Signup OTP (`email_otps` table): **untouched**
- Login (email + password): **untouched**
- Admin-driven password reset (`POST /api/auth/users/{id}/reset-password/`): **untouched** — this endpoint remains for admin force-resets
- Sanctum token auth: **untouched**
- All 42 pre-existing tests: **still pass**

---

## Production Readiness Checklist

- [ ] Set `MAIL_MAILER` to `smtp` (or `ses`/`postmark`/`resend`) in `.env`
- [ ] Configure SMTP credentials in `.env`
- [ ] Remove `debug_code` from response in production (already conditional on `local`/`testing` env)
- [ ] Customize email template HTML if needed
- [ ] Run `php artisan migrate` on production database
