# Blog CMS API Frontend Handoff

This document covers the backend Blog CMS API for the Next.js frontend.

Base API URL in local development: `http://localhost:8000/api`

All response keys use `snake_case`.

## Public Endpoints

### List Published Posts

`GET /api/blog/posts/`

Auth: not required.

Behavior:
- Returns published posts only.
- Hidden from public response: `draft`, `scheduled`, `archived`, and published posts with future `published_at`.
- Newest first by `published_at`.
- Supports pagination with `page` and `page_size`.
- `page_size` defaults to `20` and is capped at `100`.

Example:

```http
GET /api/blog/posts/?page=1&page_size=10
```

Response:

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
      "content": "Long-form travel content...",
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

### Get Published Post By Slug

`GET /api/blog/posts/{slug}/`

Auth: not required.

Behavior:
- Returns only a publicly visible published post.
- Returns `404` if the slug does not exist or the post is not publicly visible.
- Includes category, author, and linked hotel if `hotel_id` is set.

Example:

```http
GET /api/blog/posts/best-hotels-in-dubai/
```

Response:

```json
{
  "id": 12,
  "title": "Best Hotels in Dubai",
  "slug": "best-hotels-in-dubai",
  "excerpt": "A practical guide to choosing hotels in Dubai.",
  "content": "Long-form travel content...",
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
  "hotel": {
    "id": 8,
    "name": "Dubai Hotel",
    "slug": "dubai-hotel",
    "subdomain": "dubai",
    "property_type": "hotel",
    "country": "UAE",
    "city": "Dubai",
    "cover_image_url": "/media/hotels/dubai.jpg",
    "publishing_status": "published"
  },
  "created_at": "2026-06-27T09:30:00.000000Z",
  "updated_at": "2026-06-27T09:30:00.000000Z"
}
```

The `hotel` object uses the existing property response shape and may contain more fields than shown above.

## Admin Endpoints

Admin endpoints require an authenticated active admin or staff user.

Header:

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Use the token returned by existing login endpoints, for example `POST /api/auth/admin/login/`.

### Blog Posts

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/admin/blog/posts/` | List all posts |
| `POST` | `/api/admin/blog/posts/` | Create post |
| `GET` | `/api/admin/blog/posts/{id}/` | Get one post |
| `PATCH` | `/api/admin/blog/posts/{id}/` | Partially update post |
| `PUT` | `/api/admin/blog/posts/{id}/` | Replace/update post |
| `DELETE` | `/api/admin/blog/posts/{id}/` | Delete post |

Admin list filters:

```http
GET /api/admin/blog/posts/?status=draft&locale=en&category_id=3&hotel_id=8&author_id=1&page=1&page_size=20
```

Create request body:

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

Notes:
- `hotel_id` may be `null` or omitted if the article is not linked to a hotel.
- `POST` requires all post fields except nullable `hotel_id`.
- `PATCH` supports partial updates.
- `slug` is manually supplied by the frontend/admin UI and must be unique.

Partial update example:

```json
{
  "status": "published",
  "published_at": "2026-06-27T10:00:00Z"
}
```

Create/update response: same object shape as public detail, including loaded `author`, `category`, and `hotel` when present.

Delete response: `204 No Content`.

### Blog Categories

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/admin/blog/categories/` | List categories |
| `POST` | `/api/admin/blog/categories/` | Create category |
| `GET` | `/api/admin/blog/categories/{id}/` | Get one category |
| `PATCH` | `/api/admin/blog/categories/{id}/` | Partially update category |
| `PUT` | `/api/admin/blog/categories/{id}/` | Replace/update category |
| `DELETE` | `/api/admin/blog/categories/{id}/` | Delete category |

Admin category list filter:

```http
GET /api/admin/blog/categories/?locale=en&page=1&page_size=20
```

Create request body:

```json
{
  "name": "Travel Guides",
  "slug": "travel-guides",
  "description": "Useful travel guides.",
  "locale": "en",
  "is_active": true
}
```

Response:

```json
{
  "id": 3,
  "name": "Travel Guides",
  "slug": "travel-guides",
  "description": "Useful travel guides.",
  "locale": "en",
  "is_active": true,
  "created_at": "2026-06-27T09:00:00.000000Z",
  "updated_at": "2026-06-27T09:00:00.000000Z"
}
```

Delete behavior:
- Empty category: `204 No Content`.
- Category with posts: `422` with `{ "detail": "Cannot delete a category that has blog posts." }`.

## Pagination Format

All list endpoints use this format:

```json
{
  "count": 125,
  "next": "http://localhost:8000/api/blog/posts/?page=2",
  "previous": null,
  "results": []
}
```

Query params:
- `page`: page number.
- `page_size`: page size, default `20`, maximum `100`.

## Status Values

Allowed post statuses:

| Status | Publicly visible? | Intended use |
|---|---:|---|
| `draft` | No | Work in progress |
| `scheduled` | No | Future publishing workflow placeholder |
| `published` | Yes, if `published_at` is not in the future | Live public article |
| `archived` | No | Removed from public blog without deleting |

## Locale Values

Allowed values:

| Locale | Language |
|---|---|
| `en` | English |
| `ar` | Arabic |

Multilingual translations are not implemented yet. Treat each post/category as a single-locale record.

## Validation Rules

### Blog Post

On `POST`, required fields:

- `title`: string, max 255.
- `slug`: string, max 255, ASCII alpha-dash, unique.
- `excerpt`: string.
- `content`: string.
- `featured_image`: string, max 500.
- `featured_image_alt`: string, max 255.
- `meta_title`: string, max 255.
- `meta_description`: string, max 320.
- `status`: one of `draft`, `scheduled`, `published`, `archived`.
- `published_at`: valid date.
- `locale`: one of `ar`, `en`.
- `author_id`: existing user id.
- `category_id`: existing blog category id.

Optional:

- `hotel_id`: nullable existing hotel id.

On `PATCH`, all fields are optional, but supplied fields must pass the same validation.

### Blog Category

On `POST`, required fields:

- `name`: string, max 150.
- `slug`: string, max 180, ASCII alpha-dash, unique.
- `locale`: one of `ar`, `en`.

Optional:

- `description`: nullable string.
- `is_active`: boolean.

On `PATCH`, all fields are optional, but supplied fields must pass the same validation.

## Error Notes

Typical errors:

```json
{
  "message": "The slug has already been taken.",
  "errors": {
    "slug": ["The slug has already been taken."]
  }
}
```

Permission error:

```json
{
  "detail": "You do not have permission to perform this action."
}
```

Public unpublished/missing slug: `404`.

## Notes For Next.js Implementation

- Build `/blog` from `GET /api/blog/posts/`.
- Build `/blog/[slug]` from `GET /api/blog/posts/{slug}/`.
- Use `meta_title`, `meta_description`, and `featured_image_alt` directly in page metadata/rendering.
- Frontend owns canonical URL, Open Graph, Twitter Card, JSON-LD, and sitemap generation for now.
- Do not assume every post has a linked hotel. `hotel` may be absent/null.
- Do not expose non-public statuses on public pages.
- Use `published_at` for display/sorting labels. The backend already returns newest published posts first.
- For admin forms, prefer `PATCH` for autosave or partial edits.
- For create forms, require all `POST` fields before submitting.
- The backend does not upload blog images yet. `featured_image` is currently a string path/URL that the admin UI must provide.
- The backend does not generate slugs. The admin UI should generate a suggested slug from the title, allow editing, and handle uniqueness errors.

## Example Frontend Flow

### Public `/blog`

1. Server component fetches `GET /api/blog/posts/?page=1&page_size=12`.
2. Render post cards from `results`.
3. Link each card to `/blog/{slug}`.
4. Use `count`, `next`, and `previous` for pagination controls.
5. Render empty state if `results.length === 0`.

### Public `/blog/[slug]`

1. Fetch `GET /api/blog/posts/{slug}/`.
2. If `404`, render Next.js `notFound()`.
3. Use `meta_title` and `meta_description` in `generateMetadata`.
4. Render title, featured image with `featured_image_alt`, author, category, `published_at`, and content.
5. If `hotel` exists, render a related hotel CTA/card linking to the hotel page.

### Admin `/admin/blog`

1. Require logged-in admin/staff token.
2. Fetch `GET /api/admin/blog/posts/?page=1&page_size=20`.
3. Provide filters for `status`, `locale`, `category_id`, `hotel_id`, and `author_id` if needed.
4. Render table/list with status, locale, category, author, and published date.
5. Use `POST /api/admin/blog/posts/` for new posts.
6. Use `PATCH /api/admin/blog/posts/{id}/` for inline edits/autosave/status changes.
7. Use `DELETE /api/admin/blog/posts/{id}/` only after confirmation.

### Admin `/admin/blog/categories`

1. Fetch `GET /api/admin/blog/categories/?page=1&page_size=50`.
2. Use categories to populate article form dropdowns.
3. Use `POST`, `PATCH`, and `DELETE` endpoints for category management.
4. If delete returns `422`, show the backend message and ask the admin to move/delete posts first.
