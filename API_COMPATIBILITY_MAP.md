# API Compatibility Map

## Global Response Rules To Preserve

- Keep trailing slash routes.
- Keep `/api` prefix.
- Keep DRF-style pagination where enabled:
  - `count`
  - `next`
  - `previous`
  - `results`
- Keep validation errors as field-keyed JSON where possible:
  - `{"field":["message"]}` or `{"detail":"message"}`
- Keep auth header support:
  - `Authorization: Bearer <access-or-token>`
- Keep public hotel header support:
  - `X-Hotel-Subdomain: {subdomain}`
- Keep snake_case response fields.
- Keep decimal values serialized as strings where Django currently returns strings.

## Auth

| Django endpoint | Laravel endpoint | Response compatibility |
| --- | --- | --- |
| `POST /api/auth/signup/` | same | `{"detail":"Account created. Please check your email for the verification code."}` |
| `POST /api/auth/verify-otp/` | same | `{"detail":"Email verified successfully."}` or OTP error codes |
| `POST /api/auth/resend-otp/` | same | `{"detail":"A new verification code has been sent."}` |
| `POST /api/auth/login/` | same | login response object below |
| `POST /api/auth/admin/login/` | same | admin-only login response |
| `POST /api/auth/customer/login/` | same | customer-only login response |
| `GET /api/auth/me/` | same | user object |
| `PATCH /api/auth/me/` | same | user object |
| `POST /api/auth/logout/` | same | `204 No Content` |
| `/api/auth/users/` | same | admin user CRUD |
| `/api/auth/admins/` | same | admin account CRUD |

Login response:
```json
{
  "token": "legacy-token-compatible-value",
  "access": "bearer-token-compatible-value",
  "token_type": "Bearer",
  "role": "admin",
  "redirect_url": "http://localhost:3000/admin/",
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "full_name": "Admin User",
    "phone": "",
    "role": "admin",
    "is_staff": true,
    "is_active": true,
    "email_verified": true,
    "created_at": "2026-06-23T00:00:00Z"
  }
}
```

## Properties

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `GET /api/properties/` | same | guest list active/published only; admin all; staff assigned |
| `POST /api/properties/` | same | admin/staff; default draft |
| `GET /api/properties/{id}/` | same | hotel detail |
| `PATCH /api/properties/{id}/` | same | admin/staff assigned |
| `DELETE /api/properties/{id}/` | same | admin/staff assigned |
| `POST /api/properties/{id}/publish/` | same | readiness checked |
| `POST /api/properties/{id}/unpublish/` | same | status draft |
| `POST /api/properties/{id}/archive/` | same | inactive archived |
| `POST /api/properties/{id}/unarchive/` | same | active draft |
| `GET /api/properties/{id}/readiness/` | same | `is_ready_to_publish`, `errors` |
| `GET /api/properties/{id}/setup-status/` | same | setup completion object |
| `PATCH /api/properties/{id}/autosave/` | same | `property`, `setup` |
| `GET /api/properties/{id}/workspace/` | same | workspace payload |
| `GET /api/properties/available/` | same | paginated available properties |
| `GET /api/public/hotel-context/` | same | resolved public hotel context |

Important hotel fields:
- `id`, `name`, `slug`, `subdomain`, `property_type`, `country`, `city`, `address`, `phone`, `email`, `website`, `stars`, `description`, `short_description`, `timezone`, `languages_spoken`
- `parking_available`, `airport_transfer`, `shuttle_service`, `opening_year`, `renovation_year`, `video_url`, `virtual_tour_url`
- `cover_image_url`, `average_rating`, `total_reviews`, `amenities`, `images`, `policy`, `social_media`, `contacts`, `setup_status`
- `is_active`, `publishing_status`, `published_at`, `owner`, `readiness_errors`, `is_ready_to_publish`, `latitude`, `longitude`, `created_at`, `updated_at`

## Property Images And Amenities

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `/api/property-images/` | same | CRUD; filter `property` |
| `/api/property-amenities/` | same | CRUD; read public, write admin |
| `/api/room-amenities/` | same | active amenities without pagination |

Image fields:
- `id`, `property`, `image`, `thumbnail`, `caption`, `alt_text`, `display_order`, `is_cover`, `is_active`

## Rooms And Availability

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `/api/room-types/` | same | CRUD; filter `property`, `is_active` |
| `GET /api/room-types/{id}/rates/` | same | base and seasonal prices |
| `/api/room-type-images/` | same | CRUD; filter `room_type` |
| `/api/room-prices/` | same | CRUD; filter `room_type` |
| `/api/availability-blocks/` | same | CRUD; staff/admin only for list/mutation |
| `GET /api/properties/{id}/rooms/search/` | same | guest room listing |
| `GET /api/properties/{id}/availability/` | same | date-range availability |
| `GET /api/properties/{id}/rates/` | same | base/seasonal or nightly rate breakdown |

Room type fields:
- `id`, `property`, `name`, `description`, `room_size`, `bed_type`, `smoking_allowed`, `cover_image_url`
- `max_adults`, `max_children`, `total_units`, `base_price`, `weekend_price`, `pricing_mode`, `currency`
- `extra_bed_allowed`, `extra_bed_price`, `breakfast_included`, `amenities`, `amenity_details`, `is_active`, `images`, `prices`

Availability response must include:
- `property_id`
- `property`
- `property_name`
- `property_type`
- `check_in`
- `check_out`
- `is_available`
- `available_rooms`
- `units`

## Services

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `/api/service-categories/` | same | CRUD; filter `is_active` |
| `/api/property-services/` | same | CRUD; filters `property`, `category`, `pricing_type`, `currency`, `is_active`, `is_featured`, `min_price`, `max_price` |
| `/api/service-images/` | same | CRUD; filter `service` |

Service fields:
- `id`, `property`, `category`, `category_name`, `property_name`, `name`, `slug`, `short_description`, `description`
- `price`, `currency`, `pricing_type`, `duration_minutes`, `available_from`, `available_until`
- `advance_booking_required`, `is_featured`, `is_active`, `images`, `cover_image_url`

## Bookings

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `/api/bookings/` | same | CRUD; customer own, staff assigned, admin all |
| `GET /api/bookings/calendar/` | same | staff calendar events |
| `POST /api/bookings/inquiry/` | same | public booking creation |
| `POST /api/bookings/confirm/` | same | staff/admin confirmation |
| `POST /api/bookings/{id}/cancel/` | same | customer own or staff/admin assigned |

Booking fields:
- `id`, `customer_name`, `phone`, `email`, `customer`, `property`, `property_name`, `room_type`, `room_type_name`
- `check_in`, `check_out`, `nights`, `adults`, `children`, `infants`
- `extra_bed_needed`, `extra_bed_count`, `guests`, `estimated_total`, `available_units_after_booking`
- `status`, `created_at`, `updated_at`

Inquiry normalized response:
```json
{
  "booking_id": 1,
  "customer_name": "Guest",
  "phone": "+000",
  "email": "guest@example.com",
  "property_id": 1,
  "room_type_id": 1,
  "check_in": "2026-07-01",
  "check_out": "2026-07-03",
  "adults": 2,
  "children": 0,
  "estimated_total": "200.00",
  "status": "new"
}
```

## Reviews

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `GET /api/properties/{id}/reviews/` | same | public active reviews |
| `POST /api/properties/{id}/reviews/` | same | public review create |
| `GET /api/properties/{id}/reviews/summary/` | same | summary object |
| `/api/reviews/` | same | staff/admin moderation |

Public review fields:
- `id`, `guest_name`, `rating`, `title`, `comment`, `cleanliness`, `location`, `staff`, `comfort`, `value_for_money`, `created_at`

Summary fields:
- `average_rating`
- `total_reviews`
- `rating_breakdown`
- `category_averages`

## Admin/Common

| Django endpoint | Laravel endpoint | Notes |
| --- | --- | --- |
| `/api/audit-logs/` | same | admin-only |
| `/api/contact-messages/` | same | public create; admin/staff management depending existing behavior |
| `/api/channel-manager-connections/` | same | admin/staff assigned |

## Laravel Parity Checklist

- Route path and slash parity.
- Query parameter parity.
- Permission parity.
- Tenant scoping parity.
- Pagination parity.
- Error response parity.
- Decimal string parity.
- Media URL parity.
- Auth response parity.
- React smoke test with current frontend API clients.
