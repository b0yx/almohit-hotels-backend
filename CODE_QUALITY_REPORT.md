# Code Quality Report — Almohit Hotels API

**Date:** 2026-06-23

---

## Issues Fixed

| Issue | File | Severity | Fix |
|-------|------|:--------:|-----|
| Dead code: `ensureSlug()` | `CrudController.php:213-220` | Low | Removed unused method |
| Dead code: `calendar()` alias | `BookingController.php:150-153` | Low | Removed; route now points directly to `index` |
| Unused import: `Str` | `CrudController.php:10` | Low | Removed |
| Unconditional `unset` of amenity_ids/amenities/guests | `CrudController.php:167` | Low | Removed unconditional stripping |
| Hardcoded `/media/` symlink missing from config | `config/filesystems.php` | High | Added `public/media` symlink to config |

## Issues Remaining (Not Modified)

| Issue | File | Priority | Reason |
|-------|------|:--------:|--------|
| `forceFill` used throughout | AuthController, HotelController, BookingController | Medium | Intentional — models use `$guarded = ['id']` so `fill` works the same |
| `app()->bind(CrudController::class, ...)` in routes | `routes/api.php:127-153` | Low | Works correctly, moving to provider is a refactor |
| `/room-types/{id}/rates/` stub | `routes/api.php:115` | Low | Documented stub for future seasonal pricing |
| `workspace()` returns hardcoded data | `HotelController.php:151-162` | Low | Stub for future workspace feature |
| ContactMessage has no public submission | `CrudController accessMap` | Low | Future feature — contact form |

## Code Quality Score

| Metric | Value |
|--------|:-----:|
| Controllers with no dead code | 6/6 |
| Unused imports removed | 1 |
| Lint errors | 0 |
| Dead methods removed | 2 |
