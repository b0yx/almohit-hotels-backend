# Frontend ↔ Laravel API Mapping

> Generated: 2026-06-23
> Frontend source: `/home/venom/mohit-hotels-project/almohit_hotels/src/api/`
> Laravel source: `/home/venom/mohit-hotels-project/almohit_hotels_laravel/`

---

## Legend

| Icon | Meaning |
|:----:|---------|
| ✅ | Implemented and tested |
| ⚠️ | Implemented but response shape may differ |
| ❌ | Not implemented in Laravel |
| 🔲 | Not applicable / deprecated |

---

## 1. Authentication

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 1 | POST | `/auth/signup/` | `customerSignupApi` | `AuthController@signup` | ✅ |
| 2 | POST | `/auth/verify-otp/` | `verifyOTPApi` | `AuthController@verifyOtp` | ✅ |
| 3 | POST | `/auth/resend-otp/` | `resendOTPApi` | `AuthController@resendOtp` | ✅ |
| 4 | POST | `/auth/login/` | `unifiedLoginApi` | `AuthController@login` | ✅ |
| 5 | POST | `/auth/admin/login/` | `adminLoginApi` | `AuthController@adminLogin` | ✅ |
| 6 | POST | `/auth/customer/login/` | `customerLoginApi` | `AuthController@customerLogin` | ✅ |
| 7 | GET | `/auth/me/` | `fetchCurrentUser` | `AuthController@me` | ✅ |
| 8 | PATCH | `/auth/me/` | `updateCurrentUser` | `AuthController@updateMe` | ✅ |
| 9 | POST | `/auth/logout/` | `logoutApi` | `AuthController@logout` | ✅ |
| 10 | GET | `/auth/users/` | `fetchUsers` | `CrudController@index` (User) | ✅ |
| 11 | POST | `/auth/users/` | `createUser` | `CrudController@store` (User) | ✅ |
| 12 | GET | `/auth/users/{id}/` | `fetchUserById` | `CrudController@show` (User) | ✅ |
| 13 | PATCH | `/auth/users/{id}/` | `updateUser` | `CrudController@update` (User) | ✅ |
| 14 | DELETE | `/auth/users/{id}/` | `deleteUser` | `CrudController@destroy` (User) | ✅ |
| 15 | POST | `/auth/users/{id}/activate/` | `activateUser` | `AuthController@activateUser` | ✅ |
| 16 | POST | `/auth/users/{id}/deactivate/` | `deactivateUser` | `AuthController@deactivateUser` | ✅ |
| 17 | POST | `/auth/users/{id}/change-role/` | `changeUserRole` | `AuthController@changeUserRole` | ✅ |
| 18 | POST | `/auth/users/{id}/reset-password/` | `resetUserPassword` | `AuthController@resetUserPassword` | ✅ |

---

## 2. Properties / Hotels

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 19 | GET | `/properties/` | `fetchProperties`, `fetchAdminProperties` | `HotelController@index` | ✅ |
| 20 | POST | `/properties/` | `createProperty` | `HotelController@store` | ✅ |
| 21 | GET | `/properties/{id}/` | `fetchPropertyById` | `HotelController@show` | ✅ |
| 22 | PATCH | `/properties/{id}/` | `updateProperty` | `HotelController@update` | ✅ |
| 23 | GET | `/properties/{id}/availability/` | `fetchPropertyAvailability` | `HotelController@availability` | ✅ |
| 24 | GET | `/properties/{id}/rates/` | `fetchPropertyRates` | `HotelController@rates` | ✅ |
| 25 | GET | `/properties/{id}/setup-status/` | `fetchPropertySetupStatus` | `HotelController@setupStatus` | ✅ |
| 26 | POST | `/properties/{id}/publish/` | `publishProperty` | `HotelController@publish` | ✅ |
| 27 | POST | `/properties/{id}/archive/` | `archiveProperty` | `HotelController@archive` | ✅ |
| 28 | POST | `/properties/{id}/unarchive/` | `unarchiveProperty` | `HotelController@unarchive` | ✅ |
| 29 | GET | `/properties/{id}/workspace/` | — (not called from frontend directly) | `HotelController@workspace` | ⚠️ |
| 30 | PATCH | `/properties/{id}/autosave/` | — (not called from frontend directly) | `HotelController@autosave` | ⚠️ |
| 31 | GET | `/properties/{id}/readiness/` | — (not called from frontend directly) | `HotelController@readiness` | ⚠️ |
| 32 | GET | `/properties/{id}/rooms/search/` | `fetchPropertyRoomsSearch` | `HotelController@roomsSearch` | ✅ |
| 33 | POST | `/properties/{id}/unpublish/` | — (not called from frontend) | `HotelController@unpublish` | ⚠️ |
| 34 | GET | `/properties/available/` | — (not called from frontend) | `HotelController@index` | 🔲 |
| 35 | GET | `/public/hotel-context/` | `fetchPublicHotelContext` | `HotelController@publicContext` | ✅ |

---

## 3. Reviews

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 36 | GET | `/properties/{property}/reviews/` | `fetchPropertyReviews` | `ReviewController@propertyReviews` (GET) | ✅ |
| 37 | POST | `/properties/{property}/reviews/` | `submitPropertyReview` | `ReviewController@propertyReviews` (POST) | ✅ |
| 38 | GET | `/properties/{property}/reviews/summary/` | `fetchPropertyReviewSummary` | `ReviewController@summary` | ✅ |
| 39 | GET | `/reviews/{id}/` | (via apiResource) | `ReviewController@show` | ✅ |
| 40 | PATCH | `/reviews/{id}/` | `updatePropertyReview` | `ReviewController@update` | ✅ |
| 41 | DELETE | `/reviews/{id}/` | `deletePropertyReview` | `ReviewController@destroy` | ✅ |

---

## 4. Bookings

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 42 | GET | `/bookings/` | `fetchBookings`, `fetchAdminBookings` | `BookingController@index` | ✅ |
| 43 | POST | `/bookings/` | `createBooking` | `BookingController@store` | ✅ |
| 44 | GET | `/bookings/{id}/` | `fetchBookingById` | `BookingController@show` | ✅ |
| 45 | PATCH | `/bookings/{id}/` | `updateBooking`, `updateBookingStatus` | `BookingController@update` | ✅ |
| 46 | DELETE | `/bookings/{id}/` | `deleteBooking` | `BookingController@destroy` | ✅ |
| 47 | POST | `/bookings/inquiry/` | `submitBookingInquiry` | `BookingController@inquiry` | ✅ |
| 48 | POST | `/bookings/confirm/` | `confirmBooking` | `BookingController@confirm` | ✅ |
| 49 | GET | `/bookings/calendar/` | `fetchBookingsCalendar` | `BookingController@calendar` | ✅ |
| 50 | POST | `/bookings/{id}/cancel/` | — (not called from frontend directly) | `BookingController@cancel` | ⚠️ |

---

## 5. Property Amenities

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 51 | GET | `/property-amenities/` | `fetchPropertyAmenities` | `CrudController@index` (HotelAmenity) | ✅ |
| 52 | POST | `/property-amenities/` | `createPropertyAmenity` | `CrudController@store` (HotelAmenity) | ✅ |
| 53 | GET | `/property-amenities/{id}/` | (via fetch) | `CrudController@show` (HotelAmenity) | ✅ |
| 54 | PATCH | `/property-amenities/{id}/` | `updatePropertyAmenity` | `CrudController@update` (HotelAmenity) | ✅ |
| 55 | DELETE | `/property-amenities/{id}/` | `deletePropertyAmenity` | `CrudController@destroy` (HotelAmenity) | ✅ |

---

## 6. Property Images

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 56 | GET | `/property-images/` | `fetchPropertyImages` | `CrudController@index` (HotelImage) | ✅ |
| 57 | POST | `/property-images/` | `createPropertyImage` | `CrudController@store` (HotelImage) | ⚠️ |
| 58 | GET | `/property-images/{id}/` | (via fetch) | `CrudController@show` (HotelImage) | ✅ |
| 59 | PATCH | `/property-images/{id}/` | `updatePropertyImage` | `CrudController@update` (HotelImage) | ⚠️ |
| 60 | DELETE | `/property-images/{id}/` | `deletePropertyImage` | `CrudController@destroy` (HotelImage) | ✅ |

> ⚠️ **Note:** Property image upload uses `multipart/form-data` (FormData). The `CrudController@store` expects JSON. Image upload handling needs FormData support in the generic CRUD.

---

## 7. Room Types

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 61 | GET | `/room-types/` | `fetchRoomTypes` | `CrudController@index` (RoomType) | ✅ |
| 62 | POST | `/room-types/` | `createRoomType` | `CrudController@store` (RoomType) | ✅ |
| 63 | GET | `/room-types/{id}/` | `fetchRoomTypeById` | `CrudController@show` (RoomType) | ✅ |
| 64 | PATCH | `/room-types/{id}/` | `updateRoomType` | `CrudController@update` (RoomType) | ✅ |
| 65 | DELETE | `/room-types/{id}/` | `deleteRoomType` | `CrudController@destroy` (RoomType) | ✅ |
| 66 | GET | `/room-types/{id}/rates/` | — (not called from frontend) | Inline route returning empty array | ⚠️ |

---

## 8. Room Type Images

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 67 | GET | `/room-type-images/` | `fetchRoomTypeImages` | `CrudController@index` (RoomTypeImage) | ✅ |
| 68 | POST | `/room-type-images/` | `createRoomTypeImage` | `CrudController@store` (RoomTypeImage) | ⚠️ |
| 69 | PATCH | `/room-type-images/{id}/` | `updateRoomTypeImage` | `CrudController@update` (RoomTypeImage) | ⚠️ |
| 70 | DELETE | `/room-type-images/{id}/` | `deleteRoomTypeImage` | `CrudController@destroy` (RoomTypeImage) | ✅ |

> Same FormData limitation as property images.

---

## 9. Room Prices

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 71 | GET | `/room-prices/` | (included in room type data) | `CrudController@index` (RoomPrice) | ✅ |
| 72 | POST | `/room-prices/` | (via room type create) | `CrudController@store` (RoomPrice) | ✅ |
| 73 | PATCH | `/room-prices/{id}/` | (via room type update) | `CrudController@update` (RoomPrice) | ✅ |
| 74 | DELETE | `/room-prices/{id}/` | (via room type delete) | `CrudController@destroy` (RoomPrice) | ✅ |

---

## 10. Room Amenities

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 75 | GET | `/room-amenities/` | — (uses property-amenities) | `CrudController@index` (HotelAmenity) | 🔲 |

---

## 11. Availability Blocks

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 76 | GET | `/availability-blocks/` | `fetchAvailabilityBlocks` | `CrudController@index` (AvailabilityBlock) | ✅ |
| 77 | POST | `/availability-blocks/` | `createAvailabilityBlock` | `CrudController@store` (AvailabilityBlock) | ✅ |
| 78 | GET | `/availability-blocks/{id}/` | `fetchAvailabilityBlockById` | `CrudController@show` (AvailabilityBlock) | ✅ |
| 79 | PATCH | `/availability-blocks/{id}/` | `updateAvailabilityBlock` | `CrudController@update` (AvailabilityBlock) | ✅ |
| 80 | DELETE | `/availability-blocks/{id}/` | `deleteAvailabilityBlock` | `CrudController@destroy` (AvailabilityBlock) | ✅ |

---

## 12. Services

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 81 | GET | `/service-categories/` | `fetchServiceCategories` | `CrudController@index` (ServiceCategory) | ✅ |
| 82 | POST | `/service-categories/` | `createServiceCategory` | `CrudController@store` (ServiceCategory) | ✅ |
| 83 | GET | `/service-categories/{id}/` | `fetchServiceCategoryById` | `CrudController@show` (ServiceCategory) | ✅ |
| 84 | PATCH | `/service-categories/{id}/` | `updateServiceCategory` | `CrudController@update` (ServiceCategory) | ✅ |
| 85 | DELETE | `/service-categories/{id}/` | `deleteServiceCategory` | `CrudController@destroy` (ServiceCategory) | ✅ |
| 86 | GET | `/property-services/` | `fetchPropertyServices` | `CrudController@index` (HotelService) | ✅ |
| 87 | POST | `/property-services/` | `createPropertyService` | `CrudController@store` (HotelService) | ✅ |
| 88 | GET | `/property-services/{id}/` | `fetchPropertyServiceById` | `CrudController@show` (HotelService) | ✅ |
| 89 | PATCH | `/property-services/{id}/` | `updatePropertyService` | `CrudController@update` (HotelService) | ✅ |
| 90 | DELETE | `/property-services/{id}/` | `deletePropertyService` | `CrudController@destroy` (HotelService) | ✅ |
| 91 | GET | `/service-images/` | — | `CrudController@index` (ServiceImage) | ✅ |
| 92 | POST | `/service-images/` | — | `CrudController@store` (ServiceImage) | ⚠️ |
| 93 | DELETE | `/service-images/{id}/` | — | `CrudController@destroy` (ServiceImage) | ✅ |

---

## 13. Audit Logs

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 94 | GET | `/audit-logs/` | `fetchAuditLogs` | `CrudController@index` (AuditLog) | ✅ |
| 95 | GET | `/audit-logs/{id}/` | `fetchAuditLogById` | `CrudController@show` (AuditLog) | ✅ |

---

## 14. Channel Manager

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 96 | GET | `/channel-manager-connections/` | `fetchChannelManagerConnections` | `CrudController@index` (ChannelManagerConnection) | ✅ |
| 97 | POST | `/channel-manager-connections/` | `createChannelManagerConnection` | `CrudController@store` (ChannelManagerConnection) | ✅ |
| 98 | PATCH | `/channel-manager-connections/{id}/` | `updateChannelManagerConnection` | `CrudController@update` (ChannelManagerConnection) | ✅ |
| 99 | DELETE | `/channel-manager-connections/{id}/` | `deleteChannelManagerConnection` | `CrudController@destroy` (ChannelManagerConnection) | ✅ |

---

## 15. Contact Messages

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 100 | GET | `/contact-messages/` | — (not in frontend yet) | `CrudController@index` (ContactMessage) | 🔲 |
| 101 | POST | `/contact-messages/` | — | `CrudController@store` (ContactMessage) | 🔲 |

---

## 16. Health

| # | Method | Endpoint | Frontend Call | Laravel | Status |
|:-:|:------:|----------|---------------|:-------:|:------:|
| 102 | GET | `/health/` | — | Inline route | 🔲 |

---

## Summary

| Category | Total | ✅ | ⚠️ | ❌ | 🔲 |
|----------|:-----:|:-:|:--:|:--:|:--:|
| Authentication | 18 | 18 | 0 | 0 | 0 |
| Properties / Hotels | 17 | 12 | 5 | 0 | 0 |
| Reviews | 6 | 6 | 0 | 0 | 0 |
| Bookings | 9 | 8 | 1 | 0 | 0 |
| Property Amenities | 5 | 5 | 0 | 0 | 0 |
| Property Images | 5 | 3 | 2 | 0 | 0 |
| Room Types | 6 | 5 | 1 | 0 | 0 |
| Room Type Images | 4 | 2 | 2 | 0 | 0 |
| Room Prices | 4 | 4 | 0 | 0 | 0 |
| Room Amenities | 1 | 0 | 0 | 0 | 1 |
| Availability Blocks | 5 | 5 | 0 | 0 | 0 |
| Services | 12 | 11 | 1 | 0 | 0 |
| Audit Logs | 2 | 2 | 0 | 0 | 0 |
| Channel Manager | 4 | 4 | 0 | 0 | 0 |
| Contact Messages | 2 | 0 | 0 | 0 | 2 |
| Health | 1 | 0 | 0 | 0 | 1 |
| **Total** | **102** | **85** | **12** | **0** | **5** |

## Authentication Header Compatibility

The frontend sends `Authorization: Token <token>` (default `AUTH_SCHEME = 'Token'`).
Laravel's `AuthenticateApiToken` middleware accepts both `Bearer` and `Token` schemes ✅.

## Response Format Compatibility

| Aspect | Frontend Expects | Laravel Provides | Status |
|--------|-----------------|-----------------|:------:|
| Pagination | `{ count, next, previous, results }` | `{ count, next, previous, results }` | ✅ |
| Login response | `{ token/access, user, role, redirect_url }` | `{ token, access, user, role, redirect_url }` | ✅ |
| User object | `{ id, email, full_name, phone, role, is_staff, is_active, email_verified }` | Same fields | ✅ |
| Error format | `{ detail }` or `{ message }` | `{ detail }` | ✅ |
| Auth scheme | `Token` (default) or `Bearer` | Accepts both `Bearer` and `Token` | ✅ |
