# Demo Accounts — Almohit Hotels

Run `php artisan db:seed` to create these accounts.

---

## Pre-Seeded Accounts

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| **Admin** | `admin@almohit.com` | `adminpass123` | Full access: manage users, hotels, bookings, reviews, settings |
| **Staff** | `staff@almohit.com` | `staffpass123` | Can manage assigned hotels, bookings, reviews |
| **Customer** | `customer@almohit.com` | `customerpass123` | Can browse, book, review |
| **Test** | `test@example.com` | `testpass123` | Generic test customer account |

---

## OTP Verification

In `APP_ENV=local` or `APP_ENV=testing`, the signup response includes a `debug_code` field with the OTP value for convenience:

```json
POST /api/auth/signup/
{
  "detail": "Account created. Please verify your email.",
  "email": "newuser@example.com",
  "debug_code": "483291"
}
```

---

## Auth Token

Login returns a Sanctum token:

```json
POST /api/auth/login/
{
  "token_type": "bearer",
  "access": "1|abc123...sanctum-token-hash...",
  "user": { "id": 1, "email": "admin@almohit.com", "role": "admin" }
}
```

Use the `access` value as the Bearer token for authenticated requests:

```
Authorization: Bearer 1|abc123...sanctum-token-hash...
```

---

## Using Demo Accounts

### Quick Start

```bash
# Login as admin
curl -X POST http://localhost:8000/api/auth/login/ \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@almohit.com","password":"adminpass123"}'

# Store the access token, then:
curl http://localhost:8000/api/properties/ \
  -H "Authorization: Bearer 1|abc123..."
```
