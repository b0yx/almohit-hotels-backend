# Storage Verification Report — Almohit Hotels API

**Date:** 2026-06-23

---

## Storage Configuration

| Disk | Driver | Root | URL Prefix |
|------|--------|------|------------|
| `local` | `local` | `storage/app/private` | N/A |
| `public` | `local` | `storage/app/public` | `/storage` |
| `s3` | `s3` | Bucket | Via env |

## Image Upload Paths

| Resource | Directory | DB Prefix | URL Example |
|----------|-----------|-----------|-------------|
| `property-images` | `hotels/` | `/media/hotels/{uuid}.jpg` | `/media/hotels/uuid.jpg` |
| `room-type-images` | `room-types/` | `/media/room-types/{uuid}.jpg` | `/media/room-types/uuid.jpg` |
| `service-images` | `services/` | `/media/services/{uuid}.jpg` | `/media/services/uuid.jpg` |

## Symlinks

| Symlink | Target | Status |
|---------|--------|:------:|
| `public/storage` | `storage/app/public` | ✅ Laravel default |
| `public/media` | `storage/app/public` | ✅ Added to config |

**Fix:** Added `public/media => storage/app/public` to `config/filesystems.php` `links` array. Now `php artisan storage:link` creates both symlinks.

## S3 Support

The `FILESYSTEM_DISK=s3` env var switches to S3 storage. However, the image URL prefix `/media/` is hardcoded in `ImageUploadController`. With S3, URLs should use the S3 bucket URL instead.

For production with S3, the controller needs to return the full S3 URL. This is noted as a future improvement — current MVP uses local storage.

## Asset Verification

| Check | Status |
|-------|:------:|
| `public/media` symlink | ✅ |
| `public/storage` symlink | ✅ (Laravel default) |
| `config/filesystems.php links` | ✅ Both added |
| Image store path | ✅ `Storage::disk('public')->storeAs(...)` |
| Image delete on update | ✅ Old files cleaned up |
| Image delete on destroy | ✅ Files cleaned up |

## Issues Found

| Issue | Severity | Status |
|-------|----------|:------:|
| `public/media` symlink not in config | **High** | ✅ Fixed |
| Hardcoded `/media/` prefix with S3 | Low | Noted |
| No thumbnail generation | Low | Future feature |
