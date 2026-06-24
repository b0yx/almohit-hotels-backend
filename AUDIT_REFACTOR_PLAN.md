# Audit Refactor Plan

## Current State
- 4 inline `AuditLog::query()->create()` calls in `AuthController.php` (lines 219, 243, 271, 295)
- Each call duplicates the same 10-line boilerplate (actor, request, IP extraction)
- Only 1 content type (`user`) audited
- ~35KB of identical pattern across the file

## Plan

### 1. Create `app/Services/AuditService.php`
Central service with one `log()` entry point and helpers:
- `log(action, contentType, entity, changes)` — captures actor/request/IP automatically
- `diff(model, data)` — computes {field: {old, new}} diffs
- `contentTypeFor(modelClass)` — maps model classes to content_type strings

### 2. Refactor `AuthController`
Replace 4 inline calls with 1-liner `AuditService::log(...)` calls.
Add missing audit points: signup, updateMe.

### 3. Audit `HotelController`
Add audit at: store, update, destroy, publish, unpublish, archive, unarchive, autosave.

### 4. Audit `BookingController`
Add audit at: store, update, destroy, confirm, cancel, inquiry.

### 5. Audit `CrudController`
Add audit at: store, update, destroy — covers all CRUD resources (room-types, room-prices, amenities, services, availability-blocks, contact-messages, etc.).

### 6. Audit `ImageUploadController`
Add audit at: store, update, destroy — covers property-images, room-type-images, service-images.

### 7. Audit `ReviewController`
Add audit at: propertyReviews POST handler.

### 8. Tests
`AuditCoverageTest.php` — asserts audit log creation for each operation type.

## Files Changed
- CREATE: `app/Services/AuditService.php`
- MODIFY: `app/Http/Controllers/Api/AuthController.php`
- MODIFY: `app/Http/Controllers/Api/CrudController.php`
- MODIFY: `app/Http/Controllers/Api/HotelController.php`
- MODIFY: `app/Http/Controllers/Api/BookingController.php`
- MODIFY: `app/Http/Controllers/Api/ImageUploadController.php`
- MODIFY: `app/Http/Controllers/Api/ReviewController.php`
- CREATE: `tests/Feature/AuditCoverageTest.php`
- CREATE: `AUDIT_IMPLEMENTATION_REPORT.md`

## Content Type Mapping

| Model Class | content_type |
|------------|-------------|
| User | user |
| Hotel | hotel |
| BookingInquiry | booking |
| Review | review |
| RoomType | room_type |
| RoomPrice | room_price |
| HotelAmenity | hotel_amenity |
| HotelService | hotel_service |
| ServiceCategory | service_category |
| AvailabilityBlock | availability_block |
| ContactMessage | contact_message |
| HotelImage | hotel_image |
| RoomTypeImage | room_type_image |
| ServiceImage | service_image |
