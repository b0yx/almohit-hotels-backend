# Next.js MVP Handoff — Almohot Hotels

This document contains **only** the APIs required for the MVP. All Channel Manager endpoints are excluded.

---

## Basics

| Property | Value |
|----------|-------|
| Base URL | `http://localhost:8000` (dev) |
| API Prefix | `/api/` |
| Auth | `Authorization: Bearer <token>` |
| Response Format | JSON, **snake_case** |
| Pagination | `{ count, next, previous, results }` |
| Errors | `{ detail, code }` |
| Page Size | Default 20, max 100, via `?page_size=N` |
| Trailing Slash | **Required** on all endpoints |

---

## 1. Authentication

### Signup
```
POST /api/auth/signup/
Body: { "email": "user@example.com", "password": "SecurePass123!", "full_name": "John Doe" }
201: { "detail": "Account created...", "email": "user@example.com" }
```

### Verify OTP
```
POST /api/auth/verify-otp/
Body: { "email": "user@example.com", "otp": "123456" }
200: { "detail": "Email verified successfully.", "email": "user@example.com" }
```

### Resend OTP
```
POST /api/auth/resend-otp/
Body: { "email": "user@example.com" }
```

### Login
```
POST /api/auth/login/
Body: { "email": "user@example.com", "password": "SecurePass123!" }
200: { "token_type": "bearer", "access": "1|abc...token...", "user": { ... } }
```

Store `access` as the auth token. Prepend `Bearer ` on all authenticated requests.

### Customer-Only Login
```
POST /api/auth/customer/login/
Body: { "email": "...", "password": "..." }
200: Same shape as above (rejects admin accounts)
```

### Get / Update Profile
```
GET  /api/auth/me/          → current user object
PATCH /api/auth/me/         → update full_name, phone, etc.
```

### Logout
```
POST /api/auth/logout/
```

---

## 2. Hotels

### Browse Available Hotels
```
GET /api/properties/available/
```
Returns publicly available (published, non-archived) hotels. Supports `?search=`, `?page=`, `?page_size=`.

### List All Hotels
```
GET /api/properties/
```
Full list (admin/staff see all; customer sees published).

### Hotel Detail
```
GET /api/properties/{id}/
```
Returns hotel with nested: `amenities`, `images`, `reviews_summary`, `policy`, `social_media`, `contacts`.

### Public Hotel Context
```
GET /api/public/hotel-context/
```
Returns resolved hotel context based on `X-Hotel-Subdomain` header or hostname.

### Check Readiness
```
GET /api/properties/{id}/readiness/
```
Returns `{ "is_ready_to_publish": bool, "errors": { ... } }`. Useful for setup validation UI.

### Setup Status
```
GET /api/properties/{id}/setup-status/
```
Returns `{ "completion_percentage": int, "steps": { ... } }`. Wizard progress.

### Auto-Save Setup
```
PATCH /api/properties/{id}/autosave/
Body: { "step": "basic_info", "data": { ... } }
```
Persists wizard progress.

### Workspace
```
GET /api/properties/{id}/workspace/
```
Dashboard data for hotel owner (bookings, reviews, stats).

### Search Rooms
```
GET /api/properties/{id}/rooms/search/?check_in=2026-07-01&check_out=2026-07-05&guests=2
```
Returns active room types with pricing for the hotel.

### Check Availability
```
GET /api/properties/{id}/availability/?check_in=2026-07-01&check_out=2026-07-05
```
Returns available rooms and rates for date range.

### Room Rates
```
GET /api/properties/{id}/rates/
```
Returns room types with base + seasonal pricing.

---

## 3. Rooms (CRUD)

### Room Types
```
GET    /api/room-types/        → list (paginated)
GET    /api/room-types/{id}/   → detail with images, prices
POST   /api/room-types/        → create (admin/staff)
PATCH  /api/room-types/{id}/   → update (admin/staff)
DELETE /api/room-types/{id}/   → delete (admin/staff)
```

### Room Prices (Seasonal)
```
GET    /api/room-prices/        → list
GET    /api/room-prices/{id}/   → detail
POST   /api/room-prices/        → create (admin/staff)
PATCH  /api/room-prices/{id}/   → update (admin/staff)
DELETE /api/room-prices/{id}/   → delete (admin/staff)
```

### Availability Blocks
```
GET    /api/availability-blocks/        → list
GET    /api/availability-blocks/{id}/   → detail
POST   /api/availability-blocks/        → create (admin/staff)
PATCH  /api/availability-blocks/{id}/   → update (admin/staff)
DELETE /api/availability-blocks/{id}/   → delete (admin/staff)
```

### Room Type Images
```
GET    /api/room-type-images/        → list
GET    /api/room-type-images/{id}/   → detail
POST   /api/room-type-images/        → upload (multipart, file field: image)
PATCH  /api/room-type-images/{id}/   → update metadata
DELETE /api/room-type-images/{id}/   → delete
```

---

## 4. Amenities

### Property Amenities
```
GET    /api/property-amenities/        → list
GET    /api/property-amenities/{id}/   → detail
POST   /api/property-amenities/        → create (admin/staff)
PATCH  /api/property-amenities/{id}/   → update (admin/staff)
DELETE /api/property-amenities/{id}/   → delete (admin/staff)
```

### Room Amenities
```
GET /api/room-amenities/   → list (active, unpaginated)
```

---

## 5. Property Images

```
GET    /api/property-images/        → list
GET    /api/property-images/{id}/   → detail
POST   /api/property-images/        → upload (multipart: image + hotel_id)
PATCH  /api/property-images/{id}/   → update metadata
DELETE /api/property-images/{id}/   → delete
```

**Upload note:** Do NOT set `Content-Type` header — let the browser set it with the boundary.

---

## 6. Bookings

### Create Booking
```
POST /api/bookings/
Body: {
  "hotel_id": 1,
  "room_type_id": 1,
  "check_in": "2026-07-01",
  "check_out": "2026-07-05",
  "guests": 2,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+1234567890"
}
```

### Booking Inquiry (no auth required)
```
POST /api/bookings/inquiry/
Body: {
  "hotel_id": 1,
  "room_type_id": 1,
  "check_in": "2026-07-01",
  "check_out": "2026-07-05",
  "guests": 2,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+1234567890"
}
```
Validates room type belongs to property. Returns availability and price estimate.

### Confirm Booking
```
POST /api/bookings/confirm/
Body: { "booking_id": 1 }
```
Transitions status from `new` to `confirmed`. Requires auth.

### List Bookings
```
GET /api/bookings/?search=&page=&page_size=
```
Customers see own; staff see assigned hotels'; admins see all.

### Booking Detail
```
GET /api/bookings/{id}/
```

### Cancel Booking
```
POST /api/bookings/{id}/cancel/
```

---

## 7. Reviews

### Create Review (no auth required)
```
POST /api/properties/{property}/reviews/
Body: {
  "rating": 5,
  "comment": "Amazing stay!",
  "customer_name": "John Doe",
  "email": "john@example.com"
}
```

### List Reviews for a Hotel
```
GET /api/properties/{property}/reviews/?page=1&page_size=20
```
Returns only `approved` reviews by default.

### Review Summary
```
GET /api/properties/{property}/reviews/summary/
```
Returns: `{ "average_rating": 4.5, "total_reviews": 10, "breakdown": {"5":5,"4":3,...} }`

### Moderate Reviews (staff/admin)
```
GET    /api/reviews/         → list all (for moderation)
GET    /api/reviews/{id}/    → detail
PATCH  /api/reviews/{id}/   → update status (approve/reject)
DELETE /api/reviews/{id}/   → delete
```

---

## Key Response Shapes

### User
```json
{"id": 1, "email": "...", "full_name": "John Doe", "role": "customer", "is_active": true, "is_email_verified": true}
```

### Hotel Detail
```json
{"id": 1, "name": "Beach Resort", "slug": "beach-resort", "status": "published", "country": "AE", "city": "Dubai",
 "amenities": [{"id":1,"name":"WiFi","icon":"wifi"}],
 "images": [{"id":1,"image_url":"http://...","is_primary":true}],
 "reviews_summary": {"average_rating":4.5,"total_reviews":10},
 "policy": {"check_in_time":"14:00","check_out_time":"12:00","cancellation_policy":"..."}
}
```

### Room Type
```json
{"id":1,"hotel_id":1,"name":"Deluxe Suite","max_guests":3,"bed_type":"king","quantity":5,"base_price":350.00,"currency":"AED","is_active":true,"images":[...]}
```

### Booking
```json
{"id":1,"hotel_id":1,"room_type_id":1,"check_in":"2026-07-01","check_out":"2026-07-05","guests":2,"total_amount":1400,"status":"confirmed","guests_list":[...]}
```

### Review
```json
{"id":1,"hotel_id":1,"customer_name":"John Doe","rating":5,"comment":"Amazing stay!","status":"approved"}
```

---

## What's NOT in MVP

| Feature | Route | Status |
|---------|-------|--------|
| Channel Manager CRUD | `/api/channel-manager-connections/*` | Disabled |
| Service Categories CRUD | `/api/service-categories/*` | Future |
| Property Services CRUD | `/api/property-services/*` | Future |
| Service Image Upload | `/api/service-images/*` | Future |
| Contact Messages | `/api/contact-messages/*` | Future |
| Audit Logs | `/api/audit-logs/*` | Future |
| Admin User Management | `/api/auth/users/*`, `/api/auth/admins/*` | Future |
| Admin-specific Login | `/api/auth/admin/login/` | Future |
| Hotel Calendar | `/api/bookings/calendar/` | Future |
| Hotel Admin Workflow | `POST /properties/{id}/{publish,unpublish,archive,unarchive}/` | Future |

Use `GET /api/properties/` with status filter instead of publish/unpublish/archive actions for MVP.

---

## .env for Next.js

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_API_PREFIX=/api
NEXT_PUBLIC_AUTH_SCHEME=Bearer
NEXT_PUBLIC_DEFAULT_PAGE_SIZE=20
NEXT_PUBLIC_MAX_PAGE_SIZE=100
```
