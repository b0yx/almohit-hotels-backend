# Authentication Verification Report

> Generated: 2026-06-23
> Laravel API: `http://127.0.0.1:9091/api`
> Tested via curl against running Laravel dev server

---

## Test Accounts

| Role     | Email                 | Password         | Created Via |
|----------|-----------------------|------------------|-------------|
| Admin    | `admin@almohit.com`   | `adminpass123`   | API signup + OTP verify + DB role promotion |
| Staff    | `staff@almohit.com`   | `staffpass123`   | API signup + OTP verify + DB role promotion |
| Customer | `customer@almohit.com`| `customerpass123`| API signup + OTP verify |
| Customer | `test@example.com`    | `testpass123`    | API signup + OTP verify |

---

## Auth Flow Test Results

### 1. Signup `POST /api/auth/signup/`

**Request:**
```json
{
  "email": "test@example.com",
  "first_name": "Test",
  "last_name": "User",
  "password": "testpass123",
  "password_confirm": "testpass123"
}
```

**Response:** `201 Created`
```json
{
  "detail": "Account created. Please check your email for the verification code."
}
```

**Result:** ✅ PASS — Account created, user in `is_active=false` state until OTP verified.

---

### 2. Verify OTP `POST /api/auth/verify-otp/`

**Request:**
```json
{
  "email": "test@example.com",
  "code": "123456"
}
```

**Response:** `200 OK`
```json
{
  "detail": "Email verified successfully."
}
```

**Result:** ✅ PASS — OTP verified, user set to `is_active=true`, `email_verified=true`.

---

### 3. Resend OTP `POST /api/auth/resend-otp/`

**Request:**
```json
{
  "email": "test@example.com"
}
```

**Response:** `200 OK`
```json
{
  "detail": "A new verification code has been sent."
}
```

**Result:** ✅ PASS — New OTP created.

---

### 4. Unified Login `POST /api/auth/login/`

**Request:**
```json
{
  "email": "test@example.com",
  "password": "testpass123"
}
```

**Response:** `200 OK`
```json
{
  "token": "<64-char-random>",
  "access": "<64-char-random>",
  "token_type": "Bearer",
  "role": "customer",
  "redirect_url": "http://localhost:3000/profile/",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "full_name": "Test User",
    "phone": "",
    "role": "customer",
    "is_staff": false,
    "is_active": true,
    "email_verified": true,
    "created_at": "2026-06-23T05:57:48.000000Z"
  }
}
```

**Result:** ✅ PASS — Token + full user object returned. Both `token` and `access` fields provided for frontend compatibility.

---

### 5. Admin Login `POST /api/auth/admin/login/`

**Request:**
```json
{
  "email": "admin@almohit.com",
  "password": "adminpass123"
}
```

**Response:** `200 OK` — returns admin-specific `redirect_url: "http://localhost:3000/admin/"`

**Result:** ✅ PASS — Admin users can authenticate. Customer users get `403` on this endpoint.

---

### 6. Customer Login `POST /api/auth/customer/login/`

**Request:**
```json
{
  "email": "customer@almohit.com",
  "password": "customerpass123"
}
```

**Response:** `200 OK` — returns customer-specific `redirect_url: "http://localhost:3000/profile/"`

**Result:** ✅ PASS — Customer role enforced. Admin users get `403` on this endpoint.

---

### 7. Get Current User `GET /api/auth/me/`

**Headers:** `Authorization: Token <token>` or `Authorization: Bearer <token>`

**Response:** `200 OK`
```json
{
  "id": 1,
  "email": "test@example.com",
  "full_name": "Test User",
  "phone": "",
  "role": "customer",
  "is_staff": false,
  "is_active": true,
  "email_verified": true,
  "created_at": "2026-06-23T05:57:48.000000Z"
}
```

**Result:** ✅ PASS — Both `Token` and `Bearer` auth schemes accepted. Returns user fields matching frontend expectations.

---

### 8. Update Current User `PATCH /api/auth/me/`

**Headers:** `Authorization: Token <token>`
**Body:** `{ "full_name": "Updated Name", "phone": "+1234567890" }`

**Response:** `200 OK` — returns updated user object.

**Result:** ✅ PASS

---

### 9. Logout `POST /api/auth/logout/`

**Headers:** `Authorization: Token <token>`

**Response:** `204 No Content`

**Result:** ✅ PASS — Token deleted from `api_tokens` table. Subsequent `GET /auth/me/` returns `401`.

---

### 10. Auth Guard (Unauthenticated)

**Request:** `GET /api/auth/me/` (no token)

**Response:** `401 Unauthorized`
```json
{
  "detail": "Authentication credentials were not provided."
}
```

**Result:** ✅ PASS

---

### 11. Admin User Management

| Endpoint | Test | Result |
|----------|------|:------:|
| `GET /auth/users/` | List all users | ✅ PASS — 4 users returned |
| `POST /auth/users/{id}/activate/` | Activate user | ✅ PASS |
| `POST /auth/users/{id}/deactivate/` | Deactivate user | ✅ PASS |
| `POST /auth/users/{id}/change-role/` | Change user role | ✅ PASS — accepts `customer`, `staff`, `admin` |
| `POST /auth/users/{id}/reset-password/` | Reset user password | ✅ PASS |

---

## Auth Header Compatibility

| Scheme | Test | Result |
|--------|------|:------:|
| `Authorization: Token <token>` | Login + Me + Logout | ✅ PASS |
| `Authorization: Bearer <token>` | Login + Me + Logout | ✅ PASS |

**Laravel middleware** accepts both `Bearer` and `Token` schemes via regex: `^(?:Bearer|Token)\s+(.+)$`.

**Frontend default** sends `Token` scheme (`REACT_APP_AUTH_SCHEME=Token`) — fully compatible.

---

## Connection Details

| Parameter | Value |
|-----------|-------|
| API Base URL | `http://127.0.0.1:9091/api` |
| DB Host | `172.21.0.2` (Docker bridge IP) |
| DB Port | `5432` |
| DB Name | `almohit_hotels_laravel` |
| DB User | `postgres` |
| DB Password | `almohit123` |
| Auth Token Type | `Token` (frontend default) / `Bearer` (both accepted) |

---

## Safety Confirmation

| Check | Status |
|-------|:------:|
| Django source code modified? | ❌ No |
| Django database (`almohit_hotels_db`) untouched? | ✅ 35 tables unchanged |
| Django Git history preserved? | ✅ HEAD at `0da44e1` |
| Docker PostgreSQL password unchanged? | ✅ `almohit123` |
| Laravel DB created separately? | ✅ `almohit_hotels_laravel` |

---

## Seeder

Test accounts can be recreated at any time via:
```bash
php artisan db:seed
```

Or individually via the API:
```bash
curl -X POST /api/auth/signup/ -d '{"email":"...","first_name":"...","last_name":"...","password":"...","password_confirm":"..."}'
curl -X POST /api/auth/verify-otp/ -d '{"email":"...","code":"123456"}'
```
