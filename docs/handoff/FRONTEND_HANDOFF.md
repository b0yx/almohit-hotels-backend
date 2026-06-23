# Frontend Handoff — Almohit Hotels API

## Overview

This document is the single source of truth for the Next.js frontend developer. The backend (Laravel) is **frozen** — no model, endpoint, or response shape will change. Use **only** this document + `OPENAPI_SPEC.yaml` + `POSTMAN_COLLECTION.json` to build; do not reference Django source.

---

## API Basics

| Property | Value |
|----------|-------|
| **Base URL** | `http://localhost:8000` (dev) / `https://your-app.laravel.cloud` (prod) |
| **API Prefix** | `/api/` |
| **Auth Scheme** | `Authorization: Bearer <token>` (also accepts `Token <token>`) |
| **Response Format** | JSON, **snake_case** keys |
| **Pagination** | DRF-compatible `{ count, next, previous, results }` |
| **Error Format** | `{ detail: string, code: string }` |
| **Page Size** | Default 20, max 100, via `?page_size=N` |
| **Tenant Header** | `X-Hotel-Subdomain: <subdomain>` (optional, for multi-tenant) |

---

## Authentication Flow

### 1. Signup

```
POST /api/auth/signup/
Body:  { "email": "user@example.com", "password": "SecurePass123!", "full_name": "John Doe" }
       Optionally: "phone", "country_code"
```

**Success (201):**
```json
{ "detail": "Account created. Please verify your email.", "email": "user@example.com" }
```

### 2. Verify OTP

```
POST /api/auth/verify-otp/
Body:  { "email": "user@example.com", "otp": "123456" }
```

**Success (200):**
```json
{ "detail": "Email verified successfully. You can now log in.", "email": "user@example.com" }
```

> **Dev note:** In `APP_ENV=local` or `testing`, the OTP is returned in the signup response as `debug_code` for convenience.

### 3. Resend OTP

```
POST /api/auth/resend-otp/
Body:  { "email": "user@example.com" }
```

### 4. Login

```
POST /api/auth/login/
Body:  { "email": "user@example.com", "password": "SecurePass123!" }
```

**Success (200):**
```json
{
    "token_type": "bearer",
    "access": "1|abc123...sanctum-token...",
    "user": { "id": 1, "email": "user@example.com", "full_name": "John Doe", "role": "customer" }
}
```

> **Important:** Store `access` as the auth token. Prepend `Bearer ` when using it. The token format is `{id}|{hash}` — never parse it client-side; always send it as-is.

### 5. Get Current User

```
GET /api/auth/me/
Headers: Authorization: Bearer <token>
```

### 6. Update Profile

```
PATCH /api/auth/me/
Headers: Authorization: Bearer <token>
Body:    { "full_name": "New Name", "phone": "+1234567890" }
```

### 7. Logout

```
POST /api/auth/logout/
Headers: Authorization: Bearer <token>
```

### Frontend Token Storage Pattern

```
// In your API client / axios instance:
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});
```

---

## Image Upload Flow

Images use the same pattern across three endpoints. All accept `multipart/form-data`.

### Upload Endpoints

| Resource | Endpoint | Field Names |
|----------|----------|-------------|
| Property images | `POST /api/property-images/` | `image` (file), `hotel_id` (int), `is_primary` (bool, optional) |
| Room type images | `POST /api/room-type-images/` | `image` (file), `room_type_id` (int), `is_primary` (bool, optional) |
| Service images | `POST /api/service-images/` | `image` (file), `service_id` (int) |

**IMPORTANT:** Do NOT set `Content-Type` header for multipart — the browser sets it automatically with the boundary. If you set it manually, uploads will fail.

### Response Shape (all image endpoints)

```json
{
    "id": 1,
    "hotel_id": 1,
    "room_type_id": null,
    "service_id": null,
    "image_url": "http://localhost:8000/storage/uploads/hotels/1/abc123.jpg",
    "is_primary": true,
    "created_at": "2026-06-23T12:00:00Z",
    "updated_at": "2026-06-23T12:00:00Z"
}
```

### Frontend Upload Pattern

```typescript
// Using fetch
const formData = new FormData();
formData.append('image', fileInput.files[0]);
formData.append('hotel_id', '1');
formData.append('is_primary', 'true');

const response = await fetch(`${API_URL}/api/property-images/`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` },
    body: formData
});
// DO NOT set Content-Type header!

// Using axios
await axios.post('/api/property-images/', formData, {
    headers: { 'Content-Type': 'multipart/form-data' } // axios will auto-set this
});
```

---

## Pagination

All list endpoints use DRF-compatible pagination.

### Response Shape

```json
{
    "count": 42,
    "next": "http://localhost:8000/api/properties/?page=3&page_size=20",
    "previous": "http://localhost:8000/api/properties/?page=1&page_size=20",
    "results": [ /* array of items */ ]
}
```

| Field | Type | Description |
|-------|------|-------------|
| `count` | int | Total number of results across all pages |
| `next` | string\|null | URL for next page, or `null` if last page |
| `previous` | string\|null | URL for previous page, or `null` if first page |
| `results` | array | Array of result objects for current page |

### Query Parameters

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `page` | int | 1 | Page number (1-indexed) |
| `page_size` | int | 20 | Results per page (max 100) |
| `search` | string | — | Search filter (supported on: users, properties, bookings, contact messages) |

### Frontend Pattern

```typescript
interface PaginatedResponse<T> {
    count: number;
    next: string | null;
    previous: string | null;
    results: T[];
}

async function fetchPage<T>(url: string, page: number, pageSize: number = 20): Promise<PaginatedResponse<T>> {
    const response = await fetch(`${url}?page=${page}&page_size=${pageSize}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    return response.json();
}
```

> **Important:** Always include trailing slash on API URLs: `/api/properties/` not `/api/properties`.

---

## Error Format

### Validation Errors (422)

```json
{
    "detail": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    },
    "code": "validation_error"
}
```

### Auth Errors (401)

```json
{ "detail": "Invalid email or password.", "code": "authentication_error" }
```

### Permission Errors (403)

```json
{ "detail": "You do not have permission to perform this action.", "code": "permission_denied" }
```

### Not Found (404)

```json
{ "detail": "Hotel not found.", "code": "not_found" }
```

### Business Logic Errors (422)

```json
{ "detail": "Room type does not belong to this property.", "code": "invalid_room_type_for_property" }
```

### Frontend Error Handling Pattern

```typescript
interface ApiError {
    detail: string | Record<string, string[]>;
    code: string;
}

async function handleApiError(response: Response): Promise<never> {
    const error: ApiError = await response.json();
    // Show toast / form errors based on error.code
    if (response.status === 422 && typeof error.detail === 'object') {
        // Field-level validation errors
        Object.entries(error.detail).forEach(([field, messages]) => {
            form.setError(field, { message: messages[0] });
        });
    } else {
        // General error
        toast.error(typeof error.detail === 'string' ? error.detail : 'An error occurred');
    }
    throw error;
}
```

---

## Key Endpoints by Feature

### Hotel Management (Admin/Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/properties/` | List all hotels (published for public, all for admin/staff) |
| POST | `/api/properties/` | Create hotel |
| GET | `/api/properties/{id}/` | Get hotel detail (includes amenities, images, reviews, policy, social_media, contacts, setup_status) |
| PATCH | `/api/properties/{id}/` | Update hotel |
| DELETE | `/api/properties/{id}/` | Delete hotel |
| POST | `/api/properties/{id}/publish/` | Publish (requires readiness) |
| POST | `/api/properties/{id}/unpublish/` | Unpublish → draft |
| POST | `/api/properties/{id}/archive/` | Archive |
| POST | `/api/properties/{id}/unarchive/` | Unarchive |
| GET | `/api/properties/{id}/readiness/` | Check readiness (returns `{ is_ready_to_publish, errors[] }`) |
| GET | `/api/properties/{id}/setup-status/` | Setup wizard completion |
| PATCH | `/api/properties/{id}/autosave/` | Auto-save setup progress |
| GET | `/api/properties/{id}/workspace/` | Owner workspace data |
| GET | `/api/properties/{id}/rooms/search/` | Search active room types |
| GET | `/api/properties/{id}/availability/` | Availability by date range |
| GET | `/api/properties/{id}/rates/` | Room rates with seasonal prices |

### Booking Flow

| Step | Endpoint | Auth | Description |
|------|----------|------|-------------|
| 1. Inquiry | `POST /api/bookings/inquiry/` | No | Check availability, get price estimate |
| 2. Confirm | `POST /api/bookings/confirm/` | Yes | Create confirmed booking |
| 3. List | `GET /api/bookings/` | Yes | List bookings (with filters) |
| 4. Detail | `GET /api/bookings/{id}/` | Yes | Single booking detail |
| 5. Cancel | `POST /api/bookings/{id}/cancel/` | Yes | Cancel a booking |

> **Inquiry is public (no auth).** All other booking endpoints require auth.

### Review Flow

| Step | Endpoint | Auth | Description |
|------|----------|------|-------------|
| 1. Create | `POST /api/properties/{property}/reviews/` | No | Submit a public review |
| 2. List | `GET /api/properties/{property}/reviews/` | No | List reviews for a hotel |
| 3. Summary | `GET /api/properties/{property}/reviews/summary/` | No | Aggregated rating stats |
| 4. Moderate | `PATCH /api/reviews/{id}/` | Staff | Moderate (approve/reject) |

---

## Hotel Detail Response Shape

This is the most complex response. It contains nested relations:

```json
{
    "id": 1,
    "name": "Beach Resort",
    "slug": "beach-resort",
    "subdomain": "beach",
    "description": "Luxury beachfront property",
    "country": "AE",
    "city": "Dubai",
    "address": "Jumeirah Beach Road",
    "status": "published",
    "contact_email": "resort@example.com",
    "contact_phone": "+971500000000",
    "amenities": [
        { "id": 1, "name": "WiFi", "icon": "wifi", "is_active": true }
    ],
    "images": [
        { "id": 1, "image_url": "http://...", "is_primary": true }
    ],
    "reviews_summary": {
        "average_rating": 4.5,
        "total_reviews": 10,
        "breakdown": { "5": 5, "4": 3, "3": 1, "2": 1, "1": 0 }
    },
    "policy": {
        "check_in_time": "14:00",
        "check_out_time": "12:00",
        "cancellation_policy": "Free cancellation 24h before check-in"
    },
    "social_media": {
        "facebook": "https://facebook.com/beachresort",
        "instagram": "https://instagram.com/beachresort"
    },
    "contacts": {
        "emergency_phone": "+971501111111",
        "whatsapp": "+971502222222"
    },
    "setup_status": {
        "completion_percentage": 100,
        "steps": { "basic_info": true, "location": true, "rooms": true, "images": true, "services": true }
    },
    "created_at": "2026-06-01T00:00:00Z",
    "updated_at": "2026-06-23T00:00:00Z"
}
```

---

## Key Response Shapes

### User

```json
{
    "id": 1,
    "email": "user@example.com",
    "full_name": "John Doe",
    "role": "customer",
    "phone": "+1234567890",
    "country_code": "+1",
    "is_active": true,
    "is_email_verified": true,
    "created_at": "2026-06-01T00:00:00Z"
}
```

### Room Type

```json
{
    "id": 1,
    "hotel_id": 1,
    "name": "Deluxe Suite",
    "description": "Spacious suite with ocean view",
    "max_guests": 3,
    "bed_type": "king",
    "square_meters": 45,
    "quantity": 5,
    "base_price": 350.00,
    "currency": "AED",
    "is_active": true,
    "images": [ { "id": 1, "image_url": "http://...", "is_primary": true } ],
    "amenities": [ { "id": 1, "name": "AC", "icon": "ac" } ]
}
```

### Booking

```json
{
    "id": 1,
    "hotel_id": 1,
    "room_type_id": 1,
    "hotel_name": "Beach Resort",
    "room_type_name": "Deluxe Suite",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+1234567890",
    "check_in": "2026-07-01",
    "check_out": "2026-07-05",
    "guests": 2,
    "total_amount": 1400.00,
    "currency": "AED",
    "status": "confirmed",
    "special_requests": "Late check-in",
    "guests_list": [
        { "full_name": "John Doe", "age": 30 },
        { "full_name": "Jane Doe", "age": 28 }
    ],
    "is_read": false,
    "created_at": "2026-06-20T00:00:00Z"
}
```

### Review

```json
{
    "id": 1,
    "hotel_id": 1,
    "user_id": 1,
    "customer_name": "John Doe",
    "email": "john@example.com",
    "rating": 5,
    "comment": "Amazing stay!",
    "status": "approved",
    "created_at": "2026-06-22T00:00:00Z"
}
```

---

## Role-Based Access Summary

| Role | Can Do |
|------|--------|
| **customer** | Browse published hotels, submit reviews, create bookings (own), manage own profile |
| **staff** | All customer permissions + manage assigned hotels, bookings, reviews, rooms, services |
| **admin** | All permissions + user management, audit logs, all hotels |

---

## Common Patterns

### Filtering by Search

```
GET /api/properties/?search=beach
GET /api/bookings/?search=john
GET /api/auth/users/?search=admin
```

### Getting Available Hotels

```
GET /api/properties/available/
```
Returns only published, non-archived hotels.

### Checking Hotel Readiness

```
GET /api/properties/{id}/readiness/
→ { "is_ready_to_publish": false, "errors": { "rooms": "No active room types", "images": "No property images" } }
```

---

## Common Pitfalls

1. **Trailing slash required** — Always use trailing slashes: `/api/properties/` ✅ not `/api/properties` ❌
2. **snake_case** — All API responses use `snake_case` (`created_at`, `hotel_id`), not `camelCase`
3. **Bearer vs Token** — Both `Bearer <token>` and `Token <token>` work; prefer `Bearer`
4. **Multipart uploads** — Never manually set `Content-Type` for image uploads
5. **Token format** — Token is `{id}|{hash}` (e.g., `1|abc123...`). Send the full string.
6. **Page start** — Pagination is 1-indexed: `?page=1` is the first page
7. **Date format** — All dates are ISO 8601 strings: `2026-06-23T12:00:00Z`

---

## Files in Handoff Package

| File | Description |
|------|-------------|
| `OPENAPI_SPEC.yaml` | Full OpenAPI 3.0.3 spec |
| `OPENAPI_SPEC.json` | JSON version (importable into tools) |
| `POSTMAN_COLLECTION.json` | Postman collection (84 endpoints, 11 folders) |
| `FRONTEND_HANDOFF.md` | This document |
| `.env.example` | Environment variables for Next.js |
| `NEXTJS.env.example` | Next.js environment variables template |
| `HANDOFF_PACKAGE.md` | Handoff summary and readiness assessment |
