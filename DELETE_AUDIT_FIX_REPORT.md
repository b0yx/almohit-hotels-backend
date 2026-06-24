# Delete Audit Fix Report

## Issue
`HotelController::destroy()` created duplicate audit entries for hotel deletion.

## Root Cause
`HotelController::destroy()` logged `AuditService::log('deleted', 'hotel', $hotel)` on line 62, then called `parent::destroy($id)`, which triggered `CrudController::destroy()` which also logged `AuditService::log('deleted', $contentType, $model)` on line 229 — creating **two** `deleted` entries for every hotel deletion.

## Fix
Removed the duplicate `AuditService::log()` call from `HotelController::destroy()` (`app/Http/Controllers/Api/HotelController.php:62`). The parent `CrudController::destroy()` already handles the audit logging correctly, using `AuditService::contentTypeFor($this->modelClass)` which correctly maps `Hotel::class` → `'hotel'`.

## Verification
- `AuditCoverageTest::test_audit_on_hotel_delete` now asserts `assertCount(1, ...)` — confirms exactly one delete entry, not two.
- All 30 AuditCoverageTest tests pass.
- No other delete paths affected (BookingController, ImageUploadController, and all CRUD resources handle delete without parent delegation).
