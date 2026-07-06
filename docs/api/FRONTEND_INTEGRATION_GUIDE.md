# Frontend Integration Guide

## 1. Summary

The backend now includes two production-facing additions:

1. A platform-owned Blog CMS API.
2. Optional Arabic content fields for selected hotel-related entities.

The Blog CMS lets Almohit Hotels publish platform content such as travel guides, city guides, booking tips, and best-hotel articles. Blog posts are owned by the platform, not by individual hotels, but a post can optionally link to one hotel.

Arabic support was added as separate nullable fields such as `name_ar`, `description_ar`, and policy-specific Arabic fields. The backend returns English and Arabic fields separately. It does not choose a language and does not apply fallback. The frontend is responsible for display logic.

Available now:

- Public blog post listing and detail APIs.
- Admin blog post CRUD.
- Admin blog category CRUD.
- Arabic fields for hotels, policies, room types, services, and amenities.
- Create/edit support for Arabic fields.
- Clearing Arabic fields by sending `null` or an empty value.
- Backward compatibility for existing English-only clients.

## 2. Blog CMS

### Public Endpoints

#### List Published Blog Posts

```http
GET /api/blog/posts/
```

Authentication: not required.

Returns only public posts:

- `status = published`
- `published_at <= now`
- category is active

Default ordering: newest first by `published_at`.

Query params:

```http
GET /api/blog/posts/?page=1&page_size=20
```

`page_size` defaults to `20` and is capped at `100`.

Example response:

```json
{
  "count": 1,
  "next": null,
  "previous": null,
  "results": [
    {
      "id": 12,
      "title": "Best Hotels in Dubai",
      "slug": "best-hotels-in-dubai",
      "excerpt": "A practical guide to choosing hotels in Dubai.",
      "content": "Long-form article content...",
      "featured_image": "/media/blog/dubai.jpg",
      "featured_image_alt": "Dubai skyline near hotels",
      "meta_title": "Best Hotels in Dubai | Almohit Hotels",
      "meta_description": "Compare Dubai hotel areas and find the right stay.",
      "status": "published",
      "published_at": "2026-06-27T10:00:00.000000Z",
      "locale": "en",
      "author": {
        "id": 1,
        "full_name": "Admin User",
        "email": "admin@example.com"
      },
      "category": {
        "id": 3,
        "name": "Travel Guides",
        "slug": "travel-guides",
        "description": "Useful travel guides.",
        "locale": "en",
        "is_active": true,
        "created_at": "2026-06-27T09:00:00.000000Z",
        "updated_at": "2026-06-27T09:00:00.000000Z"
      },
      "created_at": "2026-06-27T09:30:00.000000Z",
      "updated_at": "2026-06-27T09:30:00.000000Z"
    }
  ]
}
```

#### Get Blog Post By Slug

```http
GET /api/blog/posts/{slug}/
```

Authentication: not required.

Returns `404` if the post is not published, does not exist, is scheduled for the future, or belongs to an inactive category.

Example response:

```json
{
  "id": 12,
  "title": "Best Hotels in Dubai",
  "slug": "best-hotels-in-dubai",
  "excerpt": "A practical guide to choosing hotels in Dubai.",
  "content": "Long-form article content...",
  "featured_image": "/media/blog/dubai.jpg",
  "featured_image_alt": "Dubai skyline near hotels",
  "meta_title": "Best Hotels in Dubai | Almohit Hotels",
  "meta_description": "Compare Dubai hotel areas and find the right stay.",
  "status": "published",
  "published_at": "2026-06-27T10:00:00.000000Z",
  "locale": "en",
  "author": {
    "id": 1,
    "full_name": "Admin User",
    "email": "admin@example.com"
  },
  "category": {
    "id": 3,
    "name": "Travel Guides",
    "slug": "travel-guides",
    "description": "Useful travel guides.",
    "locale": "en",
    "is_active": true
  },
  "hotel": {
    "id": 8,
    "name": "Dubai Hotel",
    "name_ar": "فندق دبي",
    "slug": "dubai-hotel",
    "subdomain": "dubai",
    "property_type": "hotel",
    "country": "UAE",
    "city": "Dubai",
    "description": "English hotel description",
    "description_ar": "وصف الفندق بالعربية",
    "short_description": "Short English description",
    "short_description_ar": "وصف عربي قصير",
    "cover_image_url": "/media/hotels/dubai.jpg",
    "publishing_status": "published"
  },
  "created_at": "2026-06-27T09:30:00.000000Z",
  "updated_at": "2026-06-27T09:30:00.000000Z"
}
```

`hotel` may be `null` or absent if no hotel is linked.

### Admin Endpoints

Admin endpoints require an active `admin` or `staff` user.

Use:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

#### Blog Posts

```http
GET    /api/admin/blog/posts/
POST   /api/admin/blog/posts/
GET    /api/admin/blog/posts/{id}/
PATCH  /api/admin/blog/posts/{id}/
PUT    /api/admin/blog/posts/{id}/
DELETE /api/admin/blog/posts/{id}/
```

Admin list supports filters:

```http
GET /api/admin/blog/posts/?status=draft&locale=en&category_id=3&hotel_id=8&author_id=1&page=1&page_size=20
```

Create example:

```json
{
  "title": "Best Hotels in Dubai",
  "slug": "best-hotels-in-dubai",
  "excerpt": "A practical guide to choosing hotels in Dubai.",
  "content": "Long-form article content.",
  "featured_image": "/media/blog/dubai.jpg",
  "featured_image_alt": "Dubai skyline near hotels",
  "meta_title": "Best Hotels in Dubai | Almohit Hotels",
  "meta_description": "Compare Dubai hotel areas and find the right stay.",
  "status": "draft",
  "published_at": "2026-06-27T10:00:00Z",
  "locale": "en",
  "author_id": 1,
  "category_id": 3,
  "hotel_id": 8
}
```

Partial update example:

```json
{
  "status": "published",
  "published_at": "2026-06-27T10:00:00Z"
}
```

#### Blog Categories

```http
GET    /api/admin/blog/categories/
POST   /api/admin/blog/categories/
GET    /api/admin/blog/categories/{id}/
PATCH  /api/admin/blog/categories/{id}/
PUT    /api/admin/blog/categories/{id}/
DELETE /api/admin/blog/categories/{id}/
```

List filter:

```http
GET /api/admin/blog/categories/?locale=en&page=1&page_size=20
```

Create example:

```json
{
  "name": "Travel Guides",
  "slug": "travel-guides",
  "description": "Useful travel guides.",
  "locale": "en",
  "is_active": true
}
```

### Blog Response Format

List responses use:

```json
{
  "count": 125,
  "next": "http://localhost:8000/api/blog/posts/?page=2",
  "previous": null,
  "results": []
}
```

Detail responses return a single object directly.

### Blog Status Values

Allowed post statuses:

```txt
draft
scheduled
published
archived
```

Frontend meaning:

- `draft`: editable, not public.
- `scheduled`: not public yet.
- `published`: public only when `published_at` is not in the future.
- `archived`: hidden from public blog.

### Blog Locale Field

Allowed values:

```txt
en
ar
```

This is only a record-level locale marker. Translation linking is not implemented yet.

### Blog Relationships

Each post has:

- `category`
- `author`
- optional `hotel`

A blog post does not belong to a hotel. It is platform-owned. The `hotel` relation is only an optional link for internal content strategy.

## 3. Arabic Language Support

The backend now stores English and Arabic fields separately.

Rules:

- Arabic fields are optional.
- Arabic fields are nullable.
- Backend does not fallback.
- Frontend must fallback when needed.
- Existing English fields remain unchanged.

### Hotels

| English Field | Arabic Field | Nullable | Frontend Should Send | Frontend Should Display |
|---|---|---:|---:|---:|
| `name` | `name_ar` | Yes | Yes, in hotel forms | Yes, when Arabic UI/content is requested |
| `description` | `description_ar` | Yes | Yes | Yes |
| `short_description` | `short_description_ar` | Yes | Yes | Yes |

### Policies

Arabic policy fields are nested inside `policy` when creating/updating a hotel.

| English Field | Arabic Field | Nullable | Frontend Should Send | Frontend Should Display |
|---|---|---:|---:|---:|
| `cancellation_policy` | `cancellation_policy_ar` | Yes | Yes | Yes |
| `children_policy` | `children_policy_ar` | Yes | Yes | Yes |
| `pet_policy` | `pet_policy_ar` | Yes | Yes | Yes |
| `smoking_policy` | `smoking_policy_ar` | Yes | Yes | Yes |
| `extra_bed_policy` | `extra_bed_policy_ar` | Yes | Yes | Yes |

### Room Types

Room type forms should load bed options from `GET /api/bed-types/?is_active=true` instead of hardcoding the select list. Staff/admin users can manage the options with `POST /api/bed-types/`, `PATCH /api/bed-types/{id}/`, and `DELETE /api/bed-types/{id}/`. The selected room type still sends `bed_type` as the option name string for backward compatibility.

| English Field | Arabic Field | Nullable | Frontend Should Send | Frontend Should Display |
|---|---|---:|---:|---:|
| `name` | `name_ar` | Yes | Yes, in room type forms | Yes |
| `description` | `description_ar` | Yes | Yes | Yes |

### Services

| English Field | Arabic Field | Nullable | Frontend Should Send | Frontend Should Display |
|---|---|---:|---:|---:|
| `name` | `name_ar` | Yes | Yes, in service forms | Yes |
| `short_description` | `short_description_ar` | Yes | Yes | Yes |
| `description` | `description_ar` | Yes | Yes | Yes |

### Amenities

| English Field | Arabic Field | Nullable | Frontend Should Send | Frontend Should Display |
|---|---|---:|---:|---:|
| `name` | `name_ar` | Yes | Yes, in amenity forms | Yes |

## 4. Frontend Responsibilities

The backend intentionally returns raw English and Arabic fields separately. The frontend must handle language selection.

Frontend must:

- Display Arabic fields when the active locale is `ar`.
- Fallback to English if Arabic is `null` or empty.
- Continue displaying English fields when locale is `en`.
- Send both English and Arabic fields from admin forms when available.
- Allow Arabic fields to be blank.
- Preserve `null` values from the backend.
- Send `null` when the user clears an Arabic field.
- Support `PATCH` correctly for partial updates.
- Avoid overwriting unrelated fields during partial updates.
- Keep slug behavior unchanged. Do not create Arabic slugs yet.
- Do not expect backend fallback or localization logic.

Example fallback helper:

```ts
function localizedText(en?: string | null, ar?: string | null, locale?: string) {
  if (locale === "ar" && ar && ar.trim().length > 0) {
    return ar;
  }

  return en ?? "";
}
```

Clearing Arabic field example:

```json
{
  "name_ar": null
}
```

Partial nested policy update example:

```json
{
  "policy": {
    "children_policy_ar": null
  }
}
```

## 5. Breaking Changes

There are no breaking changes.

Existing clients continue to work without modification.

All new Arabic fields are optional and nullable. Existing English fields, slugs, routes, response structures, and validation requirements remain compatible.

## 6. API Changes

### `POST /api/properties/`

What changed:

- Accepts `name_ar`, `description_ar`, `short_description_ar`.
- Accepts nested Arabic policy fields inside `policy`.

Why:

- Allows admins to enter Arabic hotel and policy content.

How to use:

- Add optional Arabic inputs to the hotel and policy forms.
- Continue sending existing English fields as before.

### `PATCH /api/properties/{id}/`

What changed:

- Can update or clear Arabic hotel fields.
- Can update or clear nested Arabic policy fields.

How to use:

- Use `PATCH` for partial updates.
- Send `null` to clear Arabic content.
- Do not send fields that are not being changed.

### `GET /api/properties/{id}/`

What changed:

- Returns `name_ar`, `description_ar`, `short_description_ar`.
- Returns Arabic policy fields inside `policy`.

How to use:

- Store these values in form state.
- Use frontend locale-aware fallback on display.

### `GET /api/properties/`

What changed:

- Hotel results now include Arabic hotel fields.
- Amenities attached to hotels may include `name_ar`.

How to use:

- Public listing cards can display Arabic hotel names/descriptions when available.

### `POST/PATCH /api/room-types/`

What changed:

- Accepts `name_ar`, `description_ar`.

How to use:

- Add optional Arabic fields to room type forms.

### `GET /api/room-types/` and `GET /api/room-types/{id}/`

What changed:

- Returns `name_ar`, `description_ar`.

How to use:

- Use Arabic fields in room cards/details when locale is `ar`.

### `POST/PATCH /api/property-services/`

What changed:

- Accepts `name_ar`, `short_description_ar`, `description_ar`.

How to use:

- Add optional Arabic fields to service forms.

### `GET /api/property-services/` and `GET /api/property-services/{id}/`

What changed:

- Returns `name_ar`, `short_description_ar`, `description_ar`.

How to use:

- Use Arabic fields for service listings/details when locale is `ar`.

### `POST/PATCH /api/property-amenities/`

What changed:

- Accepts `name_ar`.

How to use:

- Add optional Arabic field to amenity form.

### `GET /api/property-amenities/` and `GET /api/property-amenities/{id}/`

What changed:

- Returns `name_ar`.

How to use:

- Use Arabic amenity names when locale is `ar`.

### Blog CMS Endpoints

What changed:

- New public and admin blog APIs are available.

Why:

- Enables platform-owned travel content.

How to use:

- Build public `/blog` and `/blog/[slug]`.
- Build admin blog list/editor/category screens.
- Use `meta_title` and `meta_description` for blog page metadata.

## 7. Validation Rules

### Hotel Arabic Fields

```txt
name_ar: nullable string max 255
description_ar: nullable string
short_description_ar: nullable string max 300
```

### Hotel Policy Arabic Fields

```txt
policy.cancellation_policy_ar: nullable text
policy.children_policy_ar: nullable text
policy.pet_policy_ar: nullable text
policy.smoking_policy_ar: nullable text
policy.extra_bed_policy_ar: nullable text
```

### Room Type Arabic Fields

```txt
name_ar: nullable string
description_ar: nullable text
```

### Service Arabic Fields

```txt
name_ar: nullable string
short_description_ar: nullable string
description_ar: nullable text
```

### Amenity Arabic Fields

```txt
name_ar: nullable string max 100
```

### Blog Post Fields

Required on `POST`:

```txt
title: required string max 255
slug: required string max 255, ASCII alpha-dash, unique
excerpt: required string
content: required string
featured_image: required string max 500
featured_image_alt: required string max 255
meta_title: required string max 255
meta_description: required string max 320
status: required one of draft, scheduled, published, archived
published_at: required date
locale: required one of ar, en
author_id: required existing user id
category_id: required existing blog category id
hotel_id: nullable existing hotel id
```

On `PATCH`, all blog post fields are optional, but supplied fields must pass the same validation.

### Blog Category Fields

Required on `POST`:

```txt
name: required string max 150
slug: required string max 180, ASCII alpha-dash, unique
locale: required one of ar, en
description: nullable string
is_active: boolean
```

On `PATCH`, all blog category fields are optional, but supplied fields must pass the same validation.

## 8. Future Work

These features were intentionally not implemented yet:

- SEO metadata for hotels, rooms, services, amenities, cities, countries, and destinations.
- JSON-LD generation.
- Canonical URL logic.
- Open Graph generation outside the existing blog fields.
- Twitter Card generation.
- Sitemap changes.
- Translation tables.
- Locale-based backend fallback.
- Arabic slugs.
- Localized canonical URL strategy.
- Blog frontend.
- Destination/city/country content entities.
- Relationship model for posts to cities/countries/destinations.
- Frontend fallback logic in backend.

These belong to later phases because the current phase only stores and returns optional Arabic content fields and adds the first CMS content type.

## 9. Integration Checklist

- [ ] Update hotel create/edit form with `name_ar`.
- [ ] Update hotel create/edit form with `description_ar`.
- [ ] Update hotel create/edit form with `short_description_ar`.
- [ ] Update hotel policy form with `cancellation_policy_ar`.
- [ ] Update hotel policy form with `children_policy_ar`.
- [ ] Update hotel policy form with `pet_policy_ar`.
- [ ] Update hotel policy form with `smoking_policy_ar`.
- [ ] Update hotel policy form with `extra_bed_policy_ar`.
- [ ] Update room type form with `name_ar`.
- [ ] Update room type form with `description_ar`.
- [ ] Update services form with `name_ar`.
- [ ] Update services form with `short_description_ar`.
- [ ] Update services form with `description_ar`.
- [ ] Update amenities form with `name_ar`.
- [ ] Implement frontend fallback from Arabic to English.
- [ ] Display Arabic fields when active locale is `ar`.
- [ ] Preserve `null` values in form state.
- [ ] Test clearing Arabic fields with `null`.
- [ ] Use `PATCH` for partial updates.
- [ ] Build `/blog` using `GET /api/blog/posts/`.
- [ ] Build `/blog/[slug]` using `GET /api/blog/posts/{slug}/`.
- [ ] Build admin blog post list/editor.
- [ ] Build admin blog category management.
- [ ] Test English-only create/edit flows still work.
- [ ] Test Arabic create/edit flows.
- [ ] Test public hotel, room, service, and amenity display in English and Arabic.
- [ ] Test blog pagination.
- [ ] Handle `404` for unpublished or missing blog posts.
- [ ] Handle validation errors from admin forms.
