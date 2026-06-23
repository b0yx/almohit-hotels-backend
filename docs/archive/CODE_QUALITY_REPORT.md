# Code Quality Audit Report

**Project:** Almohit Hotels (Laravel)  
**Audit Date:** 2026-06-23  
**Scope:** `app/`, `routes/`, `config/`, `database/migrations/`

---

## 1. Route Conflicts & Shadowed Routes

### 1.1 Fragile Route Ordering
`routes/api.php:57` registers `/properties/available/` **before** `routes/api.php:58` registers `apiResource('properties', ...)`. If the order is ever swapped, `/properties/available` would be matched by `GET /properties/{id}` (treating "available" as an id).

```php
// Line 57 — explicit route
Route::get('/properties/available/', [HotelController::class, 'index']);
// Line 58 — generates GET /properties (index), GET /properties/{id} (show), etc.
Route::apiResource('properties', HotelController::class)->parameters(['properties' => 'id']);
```

**Severity:** Medium — fragile; order-dependent.

### 1.2 Inconsistent Route Parameter Names
`routes/api.php:70` uses `{property}` while all other property routes use `{id}`:

```php
Route::match(['get', 'post'], '/properties/{property}/reviews/', ...);
```

**Severity:** Low — cosmetic, but confusing.

---

## 2. Dead Code

### 2.1 Unused Method
`app/Http/Controllers/Api/CrudController.php:117-124` — `ensureSlug()` is defined but **never called** anywhere in the codebase.

```php
protected function ensureSlug(array $data): array
{
    if (! isset($data['slug']) && isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }
    return $data;
}
```

**Severity:** Low — dead code, should be removed or utilized.

### 2.2 Unused Import
`app/Http/Controllers/Api/ReviewController.php:5` — `use App\Models\Hotel;` is imported but never referenced.

**Severity:** Low — no runtime impact, but clutters namespace.

### 2.3 Unused Config Value
`config/almohit.php:9` — `'frontend_home_url'` is defined but never referenced in any application code. Only `frontend_admin_url` and `frontend_customer_url` are used (in `AuthController::login()`).

**Severity:** Low.

### 2.4 Stub Methods Returning Hardcoded Values
- `app/Http/Controllers/Api/HotelController.php:85-88` — `readiness()` returns `['is_ready_to_publish' => true, 'errors' => []]`
- `app/Http/Controllers/Api/HotelController.php:90-93` — `setupStatus()` returns `['completion_percentage' => 0, 'last_completed_step' => 1, 'autosaved_at' => null]`

**Severity:** Medium — these are incomplete implementations shipped to production.

---

## 3. N+1 Query Problems

### 3.1 Review Summary Executes ~22 Queries
`app/Http/Controllers/Api/ReviewController.php:49-67` — `summary()` clones the same query builder 11+ times, each executing a separate COUNT or AVG query:

```php
$reviews = Review::query()->where('hotel_id', $property)->where('is_active', true);
$total = (clone $reviews)->count();
foreach ([5, 4, 3, 2, 1] as $rating) {
    $breakdown[(string) $rating] = (clone $reviews)->where('rating', $rating)->count();
}
// Then 5 more AVG queries for category_averages
```

**Fix:** Use `GROUP BY` with conditional aggregation or a single query with `selectRaw`.

**Severity:** ⚠️ **Critical** — 22 queries for a single endpoint response.

### 3.2 `CompatResponse::hotel()` Triggers Un-eager-loaded Queries
`app/Support/CompatResponse.php:56,83-84` — Every hotel response executes:
1. `$hotel->coverImage()` — queries `hotel_images` table
2. `$hotel->reviews()->where('is_active', true)->avg('rating')` — queries `reviews`
3. `$hotel->reviews()->where('is_active', true)->count()` — queries `reviews`

These run even when `amenities` and `images` are eager-loaded. When used in `CompatResponse::page()`, this becomes N+3 per hotel in the list.

**Affected methods:**
- `HotelController::index()` — loads with `['amenities', 'images']` but coverImage() and reviews() are extra queries
- `HotelController::show()` — inherited from `CrudController::show()`, no eager loading at all
- `HotelController::publish()`, `unpublish()`, `archive()`, `unarchive()` — no eager loading
- `HotelController::autosave()` — no eager loading

**Severity:** ⚠️ **High** — affects all hotel list/detail endpoints.

### 3.3 `CompatResponse::roomType()` Triggers Extra Query
`app/Support/CompatResponse.php:107` — Every room type response executes:

```php
$room->images()->where('is_active', true)->orderByDesc('is_cover')->value('image')
```

This is an uncached query per room type, even when `images` relation is loaded.

**Severity:** Medium — affects `HotelController::rates()`, `roomsSearch()`, `availability()`.

### 3.4 `CompatResponse::service()` Accesses Relations Without Eager Loading
`app/Support/CompatResponse.php:120-121` — `$service->hotel?->name` and `$service->category?->name` trigger lazy loads when called from `CrudController::index()` for `property-services`.

**Severity:** Medium — N+2 when listing services.

---

## 4. Missing Eager Loading

| Endpoint | Controller Method | Eager Loads | Missing |
|---|---|---|---|
| `GET /properties/{id}` | `CrudController::show()` (Hotel) | None | `amenities`, `images` |
| `POST /properties/{id}/publish` | `HotelController::publish()` | None | `amenities`, `images` |
| `POST /properties/{id}/unpublish` | `HotelController::unpublish()` | None | `amenities`, `images` |
| `POST /properties/{id}/archive` | `HotelController::archive()` | None | `amenities`, `images` |
| `POST /properties/{id}/unarchive` | `HotelController::unarchive()` | None | `amenities`, `images` |
| `PATCH /properties/{id}/autosave` | `HotelController::autosave()` | None | `amenities`, `images` |
| `GET /properties/{id}/workspace` | `HotelController::workspace()` | None | `amenities`, `images` |
| `GET /properties/{id}/availability` | `HotelController::availability()` | None | `roomTypes` |
| `GET /properties/{id}/rates` | `HotelController::rates()` | `roomTypes.prices` | `amenities`, `images` |
| `GET /properties/{id}/rooms/search` | `HotelController::roomsSearch()` | None | `roomTypes` |

**Severity:** ⚠️ **High** — every property detail endpoint triggers N+1 queries.

---

## 5. Missing Database Indexes

| Table | Column(s) | Missing Index | Impact |
|---|---|---|---|
| `reviews` | `hotel_id`, `is_active` | Composite index | Queries filter by hotel + active status |
| `booking_inquiries` | `status` | Single column | Queries filter by status |
| `booking_inquiries` | `customer_id`, `status` | Composite index | Customer booking lists |
| `booking_inquiries` | `hotel_id`, `status` | Composite index | Hotel booking management |
| `room_type_images` | `room_type_id`, `is_active` | Composite index | Room type image queries |
| `service_images` | `hotel_service_id`, `is_active` | Composite index | Service image queries |
| `contact_messages` | `status` | Single column | Message filtering |
| `booking_guests` | `booking_inquiry_id` | Single column | FK queries |

**Severity:** Medium — performance degrades as data grows.

---

## 6. Validation Gaps

### 6.1 Missing Cross-Entity Validation
`app/Http/Controllers/Api/BookingController.php` — Both `store()` (line 43-44) and `inquiry()` (line 85-86) validate:

```php
'property' => ['required', 'exists:hotels,id'],
'room_type' => ['required', 'exists:room_types,id'],
```

But there is **no validation** that the `room_type` actually **belongs to** the specified `property` (hotel). A malicious user could book a room type from hotel A while claiming to book at hotel B.

**Severity:** ⚠️ **High** — data integrity issue / potential booking fraud.

### 6.2 Missing Existence Validation in Review Creation
`app/Http/Controllers/Api/ReviewController.php:21-36` — POST to `propertyReviews()` doesn't validate that `$property` exists as a hotel. If the hotel doesn't exist, a foreign key constraint exception will be thrown (500 error) instead of a proper 422 validation response.

**Severity:** Medium — unhandled exception risk.

---

## 7. Type Safety & Static Analysis Issues

### 7.1 Missing PHP Return Types

| File | Method | Line |
|---|---|---|
| `CrudController.php` | `normalizeInput()` | 54 |
| `CrudController.php` | `syncManyToMany()` | 76 |
| `CrudController.php` | `applyFilters()` | 83 |
| `CrudController.php` | `ensureSlug()` | 117 |
| `ImageUploadController.php` | `config()` | 16 |
| `ImageUploadController.php` | `normalizeBooleans()` | 176 |
| `ImageUploadController.php` | `format()` | 190 |

**Severity:** Low — PHP 8.x benefits from type safety.

### 7.2 Inconsistent Parameter Types
`app/Http/Controllers/Api/ImageUploadController.php` — `show(string $id)`, `update(Request $request, string $id)`, `destroy(string $id)` declare `$id` as `string` but internally cast with `(int)`. Meanwhile, route definitions enforce `whereNumber('id')`. The signature should be `int $id`.

**Severity:** Low — works but misleading.

### 7.3 Hardcoded OTP Code
`app/Http/Controllers/Api/AuthController.php:47,77` — OTP codes are hardcoded to `'123456'`:

```php
'hashed_code' => Hash::make('123456'),
```

This means the verification code is always the same. While acceptable for development, this is a **security issue** in any non-development environment.

**Severity:** ⚠️ **High** (if deployed) / Low (dev only).

---

## 8. Duplicate Code

### 8.1 Image File Handling Logic Duplicated
`app/Http/Controllers/Api/ImageUploadController.php:95-103` (store) and `:139-151` (update) contain nearly identical file upload logic (storeAs, path construction, relation to model).

### 8.2 Common Validation Patterns
`app/Http/Controllers/Api/BookingController.php:store()` (lines 39-51) and `inquiry()` (lines 81-95) share overlapping validation rules (property, room_type, check_in, check_out, adults, children).

### 8.3 Property Status Methods
`publish()`, `unpublish()`, `archive()`, `unarchive()` in `HotelController.php` follow an identical `findOrFail → forceFill → save → response` pattern.

**Severity:** Low — maintenance burden increases.

---

## 9. Miscellaneous Issues

### 9.1 `BookingController::calendar()` Is a Passthrough
`app/Http/Controllers/Api/BookingController.php:140-143` — `calendar()` simply calls `$this->index($request)`. The route `GET /bookings/calendar/` returns a paginated booking list, not calendar data.

**Severity:** Low — misleading endpoint name.

### 9.2 Non-JSON Response in API Context
`app/Http/Controllers/Api/ImageUploadController.php:45` — `abort(404)` inside `config()` will return an HTML error page for API requests if the segment doesn't match, instead of a JSON error response. The `AppServiceProvider` configures JSON responses for `api/*` paths, but `abort()` may still render HTML in some configurations.

**Severity:** Low.

### 9.3 Trailing Slash Inconsistency
Some routes use trailing slashes (`/properties/available/`) while others don't (`/health/`). Laravel normalizes this, but it's inconsistently applied throughout `routes/api.php`.

**Severity:** Informational.

---

## 10. Summary & Priority Fixes

### Overall Assessment: **Needs Improvement**

The codebase has solid architectural foundations with clean separation of concerns via `CrudController` and `CompatResponse`. However, it suffers from significant **performance issues** (N+1 queries, 22-query summary endpoint), **validation gaps** (cross-entity booking validation), and **incomplete implementations** (stub methods, hardcoded OTP).

### Priority Fixes (Top 5)

| # | Issue | Severity | Effort |
|---|---|---|---|
| 1 | `ReviewController::summary()` — 22 queries → single GROUP BY query | Critical | Small |
| 2 | N+1 queries in `CompatResponse::hotel()` — coverImage and reviews() | High | Medium |
| 3 | Missing cross-entity validation in `BookingController::store()`/`inquiry()` | High | Small |
| 4 | Missing eager loading on `show()`, `publish()`, etc. | High | Small |
| 5 | Hardcoded OTP code '123456' | High | Small |

### Metric Summary

| Category | Issues Found |
|---|---|
| Route Conflicts | 2 |
| Dead Code | 4 |
| N+1 Query Problems | 4 |
| Missing Eager Loading | 10 |
| Missing Indexes | 8 |
| Validation Gaps | 2 |
| Type Safety Issues | 8 |
| Duplicate Code | 3 |
| Miscellaneous | 3 |
| **Total** | **44** |
