# Authorization Audit — Almohit Hotels API

**Date:** 2026-06-23

---

## Role Model

| Role | Can Do | Cannot Do |
|------|--------|-----------|
| **Admin** | Full access — CRUD all resources, manage users, change roles, view audit logs | N/A |
| **Staff** | Manage assigned hotels, bookings, contact messages, reviews | Manage users, change roles, view audit logs, modify non-assigned hotels |
| **Customer** | Own bookings (view, cancel), submit reviews, manage profile | Access admin/staff endpoints, manage other users' data |
| **Anonymous** | Signup, login, view published hotels, submit booking inquiries, read reviews | Access authenticated endpoints |

---

## Authorization Matrix

### Auth Routes

| Endpoint | Anonymous | Customer | Staff | Admin |
|----------|:---------:|:--------:|:-----:|:-----:|
| `POST /auth/signup/` | ✅ | — | — | — |
| `POST /auth/verify-otp/` | ✅ | — | — | — |
| `POST /auth/resend-otp/` | ✅ | — | — | — |
| `POST /auth/login/` | ✅ | — | — | — |
| `POST /auth/admin/login/` | ✅ (must be admin) | — | — | — |
| `POST /auth/customer/login/` | ✅ (must be customer) | — | — | — |
| `GET /auth/me/` | 401 | ✅ | ✅ | ✅ |
| `PATCH /auth/me/` | 401 | ✅ | ✅ | ✅ |
| `POST /auth/logout/` | 204 | ✅ | ✅ | ✅ |
| `GET|POST /auth/users/*` | 401 | 403 | 403 | ✅ |
| `GET|POST /auth/admins/*` | 401 | 403 | 403 | ✅ |
| `POST /auth/users/{id}/activate/` | 401 | 403 | 403 | ✅ |
| `POST /auth/users/{id}/deactivate/` | 401 | 403 | 403 | ✅ |
| `POST /auth/users/{id}/change-role/` | 401 | 403 | 403 | ✅ |
| `POST /auth/users/{id}/reset-password/` | 401 | 403 | 403 | ✅ |

### Hotel Routes

| Endpoint | Anonymous | Customer | Staff | Admin |
|----------|:---------:|:--------:|:-----:|:-----:|
| `GET /properties/available/` | ✅ (published only) | ✅ (published only) | ✅ (assigned) | ✅ (all) |
| `GET /properties/{id}/` | ✅ (published only) | ✅ (published only) | ✅ (assigned) | ✅ (all) |
| `POST /properties/` | 401 | 403 | ✅ | ✅ |
| `PUT|PATCH /properties/{id}/` | 401 | 403 | ✅ (assigned) | ✅ |
| `DELETE /properties/{id}/` | 401 | 403 | 403 | ✅ |
| `POST /properties/{id}/publish/` | 401 | 403 | ✅ (assigned) | ✅ |
| `POST /properties/{id}/unpublish/` | 401 | 403 | ✅ (assigned) | ✅ |

### Booking Routes

| Endpoint | Anonymous | Customer | Staff | Admin |
|----------|:---------:|:--------:|:-----:|:-----:|
| `POST /bookings/inquiry/` | ✅ | ✅ | ✅ | ✅ |
| `POST /bookings/confirm/` | 401 | 403 | ✅ (assigned) | ✅ |
| `GET /bookings/` | 401 | ✅ (own) | ✅ (assigned) | ✅ (all) |
| `POST /bookings/` | 401 | ✅ | ✅ | ✅ |
| `DELETE /bookings/{id}/cancel/` | 401 | ✅ (own) | ✅ (assigned) | ✅ |

### Review Routes

| Endpoint | Anonymous | Customer | Staff | Admin |
|----------|:---------:|:--------:|:-----:|:-----:|
| `GET|POST /properties/{p}/reviews/` | ✅ | ✅ | ✅ | ✅ |
| `GET /properties/{p}/reviews/summary/` | ✅ | ✅ | ✅ | ✅ |
| `GET /reviews/` | 401 | 403 | ✅ | ✅ |
| `PUT|PATCH /reviews/{id}/` | 401 | 403 | ✅ | ✅ |
| `DELETE /reviews/{id}/` | 401 | 403 | ✅ | ✅ |

### Image Routes

| Endpoint | Anonymous | Customer | Staff | Admin |
|----------|:---------:|:--------:|:-----:|:-----:|
| `GET /{type}-images/` | 401 | 403 | ✅ (assigned) | ✅ |
| `POST /{type}-images/` | 401 | 403 | ✅ (assigned) | ✅ |
| `DELETE /{type}-images/{id}/` | 401 | 403 | ✅ (assigned) | ✅ |

---

## Audit Summary

- Fixed `User::isAdmin()` to only check role, eliminating staff-to-admin escalation
- Added defense-in-depth `is_active` check to `isAdmin()`
- Fixed booking `confirm` to require staff/admin with hotel assignment
- Fixed booking `cancel` to verify ownership or staff assignment
- Added ImageUploadController ownership checks for staff users
- All auth endpoints now have rate limiting
- OTP verification has attempt tracking (5 attempts per 15 min)
- Role-based route groups in `routes/api.php` are correctly structured
- CrudController access map correctly restricts access per model type

**No remaining authorization bypasses found.**
