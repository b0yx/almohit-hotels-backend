# MVP Scope Report — Almohit Hotels

**Audit Date:** 2026-06-23
**Customer Journey:** Signup → Login → Browse Hotels → View Hotel Details → View Rooms → Check Availability → Create Booking → Confirm Booking → Leave Review
**Channel Manager:** Explicitly OUT OF SCOPE (Future Feature)

---

## Models Audit

### Required For MVP (15)

| # | Model | Domain | Journey Step |
|---|-------|--------|--------------|
| 1 | `User` | Customer/staff/admin accounts | 1. Signup / 2. Login |
| 2 | `EmailOTP` | Email verification codes | 1. Signup |
| 3 | `ApiToken` | Bearer auth tokens | 2. Login |
| 4 | `Hotel` | Core property entity | 3. Browse / 4. Details |
| 5 | `HotelImage` | Property gallery photos | 3. Browse / 4. Details |
| 6 | `HotelAmenity` | Amenities (WiFi, pool, etc.) | 3. Browse / 4. Details |
| 7 | `HotelPolicy` | Check-in/out, cancellation | 4. Details / 6. Availability |
| 8 | `PropertyContacts` | Emergency/primary contact info | 4. Details |
| 9 | `RoomType` | Room categories within hotel | 5. Rooms / 6. Availability |
| 10 | `RoomTypeImage` | Room-specific photos | 5. Rooms |
| 11 | `RoomPrice` | Seasonal pricing overrides | 6. Availability |
| 12 | `AvailabilityBlock` | Blocked dates for rooms | 6. Availability |
| 13 | `BookingInquiry` | Booking request/order | 7. Create / 8. Confirm |
| 14 | `BookingGuest` | Individual guest details | 7. Create |
| 15 | `Review` | Guest ratings + comments | 9. Leave Review |

### Future Feature (8)

| # | Model | Reason |
|---|-------|--------|
| 16 | `PropertySocialMedia` | Social links — nice-to-have, not in booking flow |
| 17 | `PropertySetupStatus` | Hotel onboarding wizard — admin tooling |
| 18 | `HotelService` | Service booking module — post-MVP |
| 19 | `ServiceImage` | Coupled to HotelService |
| 20 | `ServiceCategory` | Coupled to HotelService |
| 21 | `ContactMessage` | Contact form — not in booking flow |
| 22 | `AuditLog` | Admin audit trail — operational tooling |
| 23 | `ChannelManagerConnection` | **Explicitly out of scope** per directive |

### Unused (0)

None.

---

## Controllers Audit

### Required For MVP (6 controllers, all methods)

| Controller | Domain | Reason |
|------------|--------|--------|
| `AuthController` | Signup, login, OTP, profile, logout | Steps 1-2 of journey |
| `HotelController` | Browse, detail, rooms, availability, rates | Steps 3-6 |
| `BookingController` | Inquiry, create, confirm, cancel | Steps 7-8 |
| `ReviewController` | Create reviews, list, summary | Step 9 |
| `ImageUploadController` | Property/room image management | Steps 3-5 (visual content) |
| `CrudController` (base) | Serves models via route binding | Required for amenity, room, price, availability, room-type CRUD |

### CrudController — Per-Model Classification

| Route | Model | MVP? | Status |
|-------|-------|------|--------|
| `property-amenities` | `HotelAmenity` | ✅ Required | GET (read) is MVP; POST/PATCH/DELETE are admin |
| `channel-manager-connections` | `ChannelManagerConnection` | ❌ Future | **Route disabled for MVP** |
| `room-types` | `RoomType` | ✅ Required | GET (read) is MVP |
| `room-prices` | `RoomPrice` | ✅ Required | GET (read) is MVP |
| `room-amenities` | `HotelAmenity` | ✅ Required | GET (read) is MVP |
| `availability-blocks` | `AvailabilityBlock` | ✅ Required | GET (read) is MVP |
| `service-categories` | `ServiceCategory` | ❌ Future | Post-MVP |
| `property-services` | `HotelService` | ❌ Future | Post-MVP |
| `audit-logs` | `AuditLog` | ❌ Future | Post-MVP |
| `contact-messages` | `ContactMessage` | ❌ Future | Post-MVP |
| `users` / `admins` | `User` | ❌ Future | Admin user management |

### Future Feature (0 controllers)

No controllers are entirely Future Feature. All controllers serve at least one MVP purpose.

### Unused (0 controllers)

None.

---

## Routes Audit (121 total entries)

### Required For MVP — 38 Routes

```
Auth (7):
  POST /api/auth/signup/
  POST /api/auth/verify-otp/
  POST /api/auth/resend-otp/
  POST /api/auth/login/
  POST /api/auth/customer/login/
  GET  /api/auth/me/
  PATCH /api/auth/me/
  POST /api/auth/logout/

Hotels (12):
  GET  /api/public/hotel-context/
  GET  /api/properties/available/
  GET  /api/properties/
  GET  /api/properties/{id}/
  GET  /api/properties/{id}/rooms/search/
  GET  /api/properties/{id}/availability/
  GET  /api/properties/{id}/rates/
  GET  /api/properties/{property}/reviews/
  POST /api/properties/{property}/reviews/
  GET  /api/properties/{property}/reviews/summary/

Bookings (6):
  GET  /api/bookings/
  POST /api/bookings/
  GET  /api/bookings/{id}/
  POST /api/bookings/inquiry/
  POST /api/bookings/confirm/
  POST /api/bookings/{id}/cancel/

Reviews (4):
  GET  /api/reviews/
  GET  /api/reviews/{id}/
  PATCH /api/reviews/{id}/
  DELETE /api/reviews/{id}/

Amenities (3):
  GET  /api/property-amenities/
  GET  /api/property-amenities/{id}/
  GET  /api/room-amenities/

Images (3):
  GET  /api/property-images/
  GET  /api/property-images/{id}/
  GET  /api/room-type-images/
  GET  /api/room-type-images/{id}/

Rooms (8):
  GET  /api/room-types/
  GET  /api/room-types/{id}/
  GET  /api/room-prices/
  GET  /api/room-prices/{id}/
  GET  /api/availability-blocks/
  GET  /api/availability-blocks/{id}/
```

### Future Feature — 82 Routes

Admin user management (12), Hotel workflow (8), Room/service/image CRUD write endpoints (30), Service categories/prices (10), Audit logs (2), Contact messages (5), 403 by CrudController authorization on writes.

### Disabled for MVP — 5 Routes

```
Channel Manager CRUD (5):
  GET    /api/channel-manager-connections/
  POST   /api/channel-manager-connections/
  GET    /api/channel-manager-connections/{id}/
  PATCH  /api/channel-manager-connections/{id}/
  DELETE /api/channel-manager-connections/{id}/
```

**Action Taken:** Route registration commented out in `routes/api.php:110` with note: `// FUTURE: Channel Manager integration — disabled for MVP`. Model and controller code preserved.

### Unused — 1 Route

```
GET /api/room-types/{id}/rates/  →  returns hardcoded empty []
```

No database interaction, no business logic. Safe to keep or remove.

---

## Summary

| Category | Count |
|----------|-------|
| Models — Required For MVP | 15 |
| Models — Future Feature | 8 |
| Models — Unused | 0 |
| Routes — Required For MVP | 38 |
| Routes — Future Feature | 82 |
| Routes — Disabled (CM) | 5 |
| Routes — Unused | 1 |
| Controllers — Required For MVP | 6 |
| Controllers — Future Feature | 0 |
| Controllers — Unused | 0 |

**MVP is fully buildable with the 38 Required routes + 15 Required models.** All customer journey steps (1-9) are covered. No Channel Manager contamination in any MVP controller.
