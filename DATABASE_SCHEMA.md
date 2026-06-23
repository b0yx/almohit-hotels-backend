# Almohit Hotels — Database Schema Reference

> Extracted from Django models (`almohit_hotels_end/apps/`).
> Source of truth: Django backend at `/home/venom/mohit-hotels-project/almohit_hotels_end/`.

---

## Naming Conventions (Django → PostgreSQL)

| Django field    | PostgreSQL column          |
|-----------------|---------------------------|
| AutoField       | `id` — `BIGSERIAL` PK     |
| CharField       | `VARCHAR`                 |
| TextField       | `TEXT`                    |
| IntegerField    | `INTEGER`                 |
| PositiveSmallIntegerField | `SMALLINT`      |
| BooleanField    | `BOOLEAN`                 |
| DateTimeField   | `TIMESTAMP WITH TIME ZONE`|
| DateField       | `DATE`                    |
| TimeField       | `TIME`                    |
| DecimalField    | `NUMERIC(precision,scale)`|
| JSONField       | `JSONB`                   |
| EmailField      | `VARCHAR(254)`            |
| URLField        | `VARCHAR(200)`            |
| SlugField       | `VARCHAR(50/255)`         |
| ImageField      | `VARCHAR(100)`            |
| FileField       | `VARCHAR(100)`            |
| ForeignKey      | `BIGINT` + FK constraint  |
| ManyToManyField | Junction table            |

---

## Common Fields (via `BaseModel`)

Every non-abstract model inherits:

| Column       | Type                        | Constraints     |
|-------------|-----------------------------|-----------------|
| `id`        | `BIGSERIAL`                 | PK              |
| `created_at`| `TIMESTAMPTZ`               | NOT NULL        |
| `updated_at`| `TIMESTAMPTZ`               | NOT NULL        |

---

## App: `common`

### `contact_messages`

| Column         | Type                        | Constraints                    |
|----------------|-----------------------------|--------------------------------|
| `id`           | `BIGSERIAL`                 | PK                             |
| `created_at`   | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`   | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`     | `BIGINT`                    | NULLABLE, FK → hotels.id       |
| `full_name`    | `VARCHAR(255)`              | NOT NULL                       |
| `email`        | `VARCHAR(254)`              | NOT NULL                       |
| `phone`        | `VARCHAR(50)`               | NOT NULL, DEFAULT ''           |
| `subject`      | `VARCHAR(255)`              | NOT NULL                       |
| `message`      | `TEXT`                      | NOT NULL                       |
| `status`       | `VARCHAR(20)`               | NOT NULL, DEFAULT 'new'        |
| `handled_by_id`| `BIGINT`                    | NULLABLE, FK → users.id        |
| `handled_at`   | `TIMESTAMPTZ`               | NULLABLE                       |

Status values: `new`, `read`, `resolved`, `archived`

Indexes:
- `contact_prop_status_idx` ON (`hotel_id`, `status`, `created_at` DESC)
- `contact_email_created_idx` ON (`email`, `created_at` DESC)
- `contact_status_created_idx` ON (`status`, `created_at` DESC)

### `audit_logs`

| Column         | Type                        | Constraints                    |
|----------------|-----------------------------|--------------------------------|
| `id`           | `BIGSERIAL`                 | PK                             |
| `action`       | `VARCHAR(20)`               | NOT NULL                       |
| `content_type_id`| `BIGINT`                  | NOT NULL, FK → django_content_type.id |
| `object_id`    | `VARCHAR(64)`               | NOT NULL                       |
| `object_repr`  | `VARCHAR(255)`              | NOT NULL                       |
| `actor_id`     | `BIGINT`                    | NULLABLE, FK → users.id        |
| `actor_email`  | `VARCHAR(254)`              | NOT NULL, DEFAULT ''           |
| `actor_name`   | `VARCHAR(255)`              | NOT NULL, DEFAULT ''           |
| `changes`      | `JSONB`                     | NOT NULL, DEFAULT '{}'         |
| `request_method`| `VARCHAR(12)`              | NOT NULL, DEFAULT ''           |
| `request_path` | `VARCHAR(500)`              | NOT NULL, DEFAULT ''           |
| `ip_address`   | `INET`                      | NULLABLE                       |
| `created_at`   | `TIMESTAMPTZ`               | NOT NULL                       |

Action values: `created`, `updated`, `deleted`

Indexes:
- `audit_object_created_idx` ON (`content_type_id`, `object_id`, `created_at` DESC)
- `audit_actor_created_idx` ON (`actor_id`, `created_at` DESC)
- `audit_action_created_idx` ON (`action`, `created_at` DESC)

---

## App: `user`

### `users`

| Column                | Type                        | Constraints                        |
|-----------------------|-----------------------------|------------------------------------|
| `id`                  | `BIGSERIAL`                 | PK                                 |
| `created_at`          | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`          | `TIMESTAMPTZ`               | NOT NULL                           |
| `password`            | `VARCHAR(128)`              | NOT NULL                           |
| `last_login`          | `TIMESTAMPTZ`               | NULLABLE                           |
| `is_superuser`        | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `email`               | `VARCHAR(254)`              | NOT NULL, UNIQUE                   |
| `full_name`           | `VARCHAR(255)`              | NOT NULL                           |
| `phone`               | `VARCHAR(50)`               | NOT NULL, DEFAULT ''                |
| `role`                | `VARCHAR(20)`               | NOT NULL, DEFAULT 'customer'        |
| `is_active`           | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |
| `email_verified`      | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |
| `is_staff`            | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `failed_login_attempts`| `INTEGER`                  | NOT NULL, DEFAULT 0                |
| `last_failed_login`   | `TIMESTAMPTZ`               | NULLABLE                           |
| `lockout_until`       | `TIMESTAMPTZ`               | NULLABLE                           |

Role values: `customer`, `staff`, `admin`

ManyToMany:
- `assigned_hotels` → junction table `users_assigned_hotels` (`user_id`, `hotel_id`)

Groups + Permissions via Django auth.

### `email_otps`

| Column          | Type                        | Constraints                    |
|-----------------|-----------------------------|--------------------------------|
| `id`            | `BIGSERIAL`                 | PK                             |
| `created_at`    | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`    | `TIMESTAMPTZ`               | NOT NULL                       |
| `user_id`       | `BIGINT`                    | NOT NULL, FK → users.id        |
| `hashed_code`   | `VARCHAR(128)`              | NOT NULL                       |
| `expires_at`    | `TIMESTAMPTZ`               | NOT NULL                       |
| `verified_at`   | `TIMESTAMPTZ`               | NULLABLE                       |
| `attempt_count` | `INTEGER`                   | NOT NULL, DEFAULT 0            |
| `resend_count`  | `INTEGER`                   | NOT NULL, DEFAULT 0            |
| `resend_reset_at`| `TIMESTAMPTZ`              | NULLABLE                       |

---

## App: `hotels`

### `hotels`

| Column              | Type                        | Constraints                        |
|---------------------|-----------------------------|------------------------------------|
| `id`                | `BIGSERIAL`                 | PK                                 |
| `created_at`        | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`        | `TIMESTAMPTZ`               | NOT NULL                           |
| `name`              | `VARCHAR(255)`              | NOT NULL                           |
| `slug`              | `VARCHAR(255)`              | UNIQUE, NULLABLE                   |
| `subdomain`         | `VARCHAR(63)`               | UNIQUE, NULLABLE, INDEXED          |
| `property_type`     | `VARCHAR(20)`               | NOT NULL, DEFAULT 'hotel'           |
| `country`           | `VARCHAR(100)`              | NOT NULL, DEFAULT ''                |
| `city`              | `VARCHAR(100)`              | NOT NULL, DEFAULT ''                |
| `address`           | `TEXT`                      | NOT NULL, DEFAULT ''                |
| `phone`             | `VARCHAR(50)`               | NOT NULL, DEFAULT ''                |
| `email`             | `VARCHAR(254)`              | NOT NULL, DEFAULT ''                |
| `website`           | `VARCHAR(200)`              | NOT NULL, DEFAULT ''                |
| `stars`             | `SMALLINT`                  | NOT NULL, DEFAULT 3, CHECK 1-5     |
| `description`       | `TEXT`                      | NOT NULL, DEFAULT ''                |
| `short_description` | `VARCHAR(300)`              | NOT NULL, DEFAULT ''                |
| `timezone`          | `VARCHAR(64)`               | NOT NULL, DEFAULT 'UTC'            |
| `languages_spoken`  | `JSONB`                     | NOT NULL, DEFAULT '[]'             |
| `parking_available` | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `airport_transfer`  | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `shuttle_service`   | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `opening_year`      | `SMALLINT`                  | NULLABLE                           |
| `renovation_year`   | `SMALLINT`                  | NULLABLE                           |
| `video_url`         | `VARCHAR(200)`              | NOT NULL, DEFAULT ''                |
| `virtual_tour_url`  | `VARCHAR(200)`              | NOT NULL, DEFAULT ''                |
| `is_active`         | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |
| `publishing_status` | `VARCHAR(20)`               | NOT NULL, DEFAULT 'published'      |
| `published_at`      | `TIMESTAMPTZ`               | NULLABLE                           |
| `owner_id`          | `BIGINT`                    | NULLABLE, FK → users.id            |
| `latitude`          | `NUMERIC(9,6)`              | NULLABLE, CHECK -90..90            |
| `longitude`         | `NUMERIC(9,6)`              | NULLABLE, CHECK -180..180          |
| `created_by_id`     | `BIGINT`                    | NULLABLE, FK → users.id            |
| `updated_by_id`     | `BIGINT`                    | NULLABLE, FK → users.id            |

Property types: `hotel`, `chalet`, `apartment`, `villa`, `resort`, `camp`, `other`
Publishing statuses: `draft`, `pending_setup`, `ready_for_review`, `published`, `archived`

Check constraint: `hotel_stars_between_1_and_5`

### `hotel_amenities`

| Column      | Type                        | Constraints                    |
|-------------|-----------------------------|--------------------------------|
| `id`        | `BIGSERIAL`                 | PK                             |
| `created_at`| `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`| `TIMESTAMPTZ`               | NOT NULL                       |
| `name`      | `VARCHAR(100)`              | NOT NULL, UNIQUE               |
| `icon`      | `VARCHAR(100)`              | NULLABLE                       |
| `is_active` | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE         |

Junction table: `hotels_amenities` — connects `hotels` ↔ `hotel_amenities`

### `hotel_images`

| Column          | Type                        | Constraints                        |
|-----------------|-----------------------------|------------------------------------|
| `id`            | `BIGSERIAL`                 | PK                                 |
| `created_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `hotel_id`      | `BIGINT`                    | NOT NULL, FK → hotels.id           |
| `image`         | `VARCHAR(100)`              | NOT NULL                           |
| `thumbnail`     | `VARCHAR(100)`              | NULLABLE                           |
| `caption`       | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `alt_text`      | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `display_order` | `SMALLINT`                  | NOT NULL, DEFAULT 0, CHECK ≥ 0    |
| `is_cover`      | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `is_active`     | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Unique constraint: `unique_cover_image_per_hotel` (partial, WHERE `is_cover`=TRUE)
Check: `hotel_image_cover_must_be_active`
Index: `hotel_image_active_idx` ON (`hotel_id`, `is_active`)

### `hotel_policies`

| Column               | Type                        | Constraints                    |
|----------------------|-----------------------------|--------------------------------|
| `id`                 | `BIGSERIAL`                 | PK                             |
| `created_at`         | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`         | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`           | `BIGINT`                    | NOT NULL, UNIQUE, FK → hotels.id |
| `check_in_time`      | `TIME`                      | NULLABLE                       |
| `check_out_time`     | `TIME`                      | NULLABLE                       |
| `cancellation_policy`| `TEXT`                      | NOT NULL, DEFAULT ''            |
| `children_policy`    | `TEXT`                      | NOT NULL, DEFAULT ''            |
| `pet_policy`         | `TEXT`                      | NOT NULL, DEFAULT ''            |
| `smoking_policy`     | `TEXT`                      | NOT NULL, DEFAULT ''            |
| `late_check_in_policy`| `TEXT`                     | NOT NULL, DEFAULT ''            |
| `refund_policy`      | `TEXT`                      | NOT NULL, DEFAULT ''            |
| `terms_and_conditions`| `TEXT`                     | NOT NULL, DEFAULT ''            |
| `extra_bed_policy`   | `TEXT`                      | NOT NULL, DEFAULT ''            |
| `important_notes`    | `TEXT`                      | NOT NULL, DEFAULT ''            |

### `channel_manager_connections`

| Column                 | Type                        | Constraints                    |
|------------------------|-----------------------------|--------------------------------|
| `id`                   | `BIGSERIAL`                 | PK                             |
| `created_at`           | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`           | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`             | `BIGINT`                    | NOT NULL, UNIQUE, FK → hotels.id |
| `provider_name`        | `VARCHAR(50)`               | NOT NULL                       |
| `external_property_id` | `VARCHAR(255)`              | NOT NULL, DEFAULT ''            |
| `is_connected`         | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE        |
| `last_sync_at`         | `TIMESTAMPTZ`               | NULLABLE                       |
| `rates_synced_at`      | `TIMESTAMPTZ`               | NULLABLE                       |
| `availability_synced_at`| `TIMESTAMPTZ`              | NULLABLE                       |

Providers: `none`, `siteminder`, `cloudbeds`, `rategain`, `staah`, `other`

### `property_social_media`

| Column             | Type                        | Constraints                    |
|--------------------|-----------------------------|--------------------------------|
| `id`               | `BIGSERIAL`                 | PK                             |
| `created_at`       | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`       | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`         | `BIGINT`                    | NOT NULL, UNIQUE, FK → hotels.id |
| `facebook_url`     | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `instagram_url`    | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `tiktok_url`       | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `twitter_url`      | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `youtube_url`      | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `linkedin_url`     | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `whatsapp_number`  | `VARCHAR(50)`               | NOT NULL, DEFAULT ''            |
| `telegram_username`| `VARCHAR(100)`              | NOT NULL, DEFAULT ''            |
| `booking_com_url`  | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `agoda_url`        | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `airbnb_url`       | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |
| `expedia_url`      | `VARCHAR(200)`              | NOT NULL, DEFAULT ''            |

### `property_contacts`

| Column                     | Type                        | Constraints                    |
|----------------------------|-----------------------------|--------------------------------|
| `id`                       | `BIGSERIAL`                 | PK                             |
| `created_at`               | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`               | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`                 | `BIGINT`                    | NOT NULL, UNIQUE, FK → hotels.id |
| `primary_contact_person`   | `VARCHAR(255)`              | NOT NULL, DEFAULT ''            |
| `contact_position`         | `VARCHAR(150)`              | NOT NULL, DEFAULT ''            |
| `emergency_contact_number` | `VARCHAR(50)`               | NOT NULL, DEFAULT ''            |

### `property_setup_statuses`

| Column                | Type                        | Constraints                    |
|-----------------------|-----------------------------|--------------------------------|
| `id`                  | `BIGSERIAL`                 | PK                             |
| `created_at`          | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`          | `TIMESTAMPTZ`               | NOT NULL                       |
| `hotel_id`            | `BIGINT`                    | NOT NULL, UNIQUE, FK → hotels.id |
| `completion_percentage`| `SMALLINT`                 | NOT NULL, DEFAULT 0            |
| `last_completed_step` | `SMALLINT`                  | NOT NULL, DEFAULT 1            |
| `autosaved_at`        | `TIMESTAMPTZ`               | NULLABLE                       |

---

## App: `rooms`

### `room_types`

| Column             | Type                        | Constraints                        |
|--------------------|-----------------------------|------------------------------------|
| `id`               | `BIGSERIAL`                 | PK                                 |
| `created_at`       | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`       | `TIMESTAMPTZ`               | NOT NULL                           |
| `hotel_id`         | `BIGINT`                    | NOT NULL, FK → hotels.id           |
| `name`             | `VARCHAR(255)`              | NOT NULL                           |
| `description`      | `TEXT`                      | NOT NULL, DEFAULT ''                |
| `room_size`        | `NUMERIC(7,2)`              | NULLABLE                           |
| `bed_type`         | `VARCHAR(100)`              | NOT NULL, DEFAULT ''                |
| `smoking_allowed`  | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `max_adults`       | `SMALLINT`                  | NOT NULL, CHECK ≥ 1                |
| `max_children`     | `SMALLINT`                  | NOT NULL, DEFAULT 0                |
| `total_units`      | `SMALLINT`                  | NOT NULL, DEFAULT 1, CHECK ≥ 1     |
| `base_price`       | `NUMERIC(10,2)`             | NULLABLE, CHECK ≥ 0                |
| `weekend_price`    | `NUMERIC(10,2)`             | NULLABLE, CHECK ≥ 0                |
| `pricing_mode`     | `VARCHAR(20)`               | NOT NULL, DEFAULT 'per_night'       |
| `currency`         | `VARCHAR(10)`               | NOT NULL, DEFAULT 'USD'            |
| `extra_bed_allowed`| `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `extra_bed_price`  | `NUMERIC(10,2)`             | NOT NULL, DEFAULT 0, CHECK ≥ 0     |
| `breakfast_included`| `BOOLEAN`                  | NOT NULL, DEFAULT FALSE            |
| `is_active`        | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Pricing modes: `per_night`, `external`

ManyToMany:
- `amenities` → junction table `room_types_amenities` (`roomtype_id`, `hotelamenity_id`)

Unique: `unique_room_type_name_per_hotel` ON (`hotel_id`, `name`)

### `availability_blocks`

| Column          | Type                        | Constraints                        |
|-----------------|-----------------------------|------------------------------------|
| `id`            | `BIGSERIAL`                 | PK                                 |
| `created_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `room_type_id`  | `BIGINT`                    | NOT NULL, FK → room_types.id       |
| `start_date`    | `DATE`                      | NOT NULL                           |
| `end_date`      | `DATE`                      | NOT NULL                           |
| `blocked_units` | `SMALLINT`                  | NOT NULL, DEFAULT 1, CHECK ≥ 1     |
| `reason`        | `VARCHAR(20)`               | NOT NULL, DEFAULT 'maintenance'     |
| `notes`         | `TEXT`                      | NOT NULL, DEFAULT ''                |
| `is_active`     | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Reasons: `maintenance`, `owner_hold`, `closed`, `other`
Check: `end_date` > `start_date`
Index: `availability_block_lookup_idx`

### `room_type_images`

| Column          | Type                        | Constraints                        |
|-----------------|-----------------------------|------------------------------------|
| `id`            | `BIGSERIAL`                 | PK                                 |
| `created_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `room_type_id`  | `BIGINT`                    | NOT NULL, FK → room_types.id       |
| `image`         | `VARCHAR(100)`              | NOT NULL                           |
| `thumbnail`     | `VARCHAR(100)`              | NULLABLE                           |
| `caption`       | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `alt_text`      | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `display_order` | `SMALLINT`                  | NOT NULL, DEFAULT 0, CHECK ≥ 0    |
| `is_cover`      | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `is_active`     | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Unique: `unique_cover_image_per_room_type` (partial)

### `room_prices`

| Column           | Type                        | Constraints                        |
|------------------|-----------------------------|------------------------------------|
| `id`             | `BIGSERIAL`                 | PK                                 |
| `created_at`     | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`     | `TIMESTAMPTZ`               | NOT NULL                           |
| `room_type_id`   | `BIGINT`                    | NOT NULL, FK → room_types.id       |
| `season_name`    | `VARCHAR(100)`              | NOT NULL                           |
| `start_date`     | `DATE`                      | NOT NULL                           |
| `end_date`       | `DATE`                      | NOT NULL                           |
| `price_per_night`| `NUMERIC(10,2)`             | NOT NULL, CHECK ≥ 0                |

Unique: `unique_room_price_period_per_room_type` ON (`room_type_id`, `season_name`, `start_date`, `end_date`)
Check: `end_date` >= `start_date`

---

## App: `bookings`

### `booking_inquiries`

| Column             | Type                        | Constraints                        |
|--------------------|-----------------------------|------------------------------------|
| `id`               | `BIGSERIAL`                 | PK                                 |
| `created_at`       | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`       | `TIMESTAMPTZ`               | NOT NULL                           |
| `customer_name`    | `VARCHAR(255)`              | NOT NULL                           |
| `phone`            | `VARCHAR(50)`               | NOT NULL                           |
| `email`            | `VARCHAR(254)`              | NOT NULL, DEFAULT ''                |
| `customer_id`      | `BIGINT`                    | NULLABLE, FK → users.id            |
| `hotel_id`         | `BIGINT`                    | NOT NULL, FK → hotels.id           |
| `room_type_id`     | `BIGINT`                    | NOT NULL, FK → room_types.id       |
| `check_in`         | `DATE`                      | NOT NULL                           |
| `check_out`        | `DATE`                      | NOT NULL                           |
| `adults`           | `SMALLINT`                  | NOT NULL, CHECK ≥ 1                |
| `children`         | `SMALLINT`                  | NOT NULL, DEFAULT 0                |
| `infants`          | `SMALLINT`                  | NOT NULL, DEFAULT 0                |
| `extra_bed_needed` | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `extra_bed_count`  | `SMALLINT`                  | NOT NULL, DEFAULT 0                |
| `estimated_total`  | `NUMERIC(10,2)`             | NOT NULL, CHECK ≥ 0                |
| `status`           | `VARCHAR(20)`               | NOT NULL, DEFAULT 'new'            |

Statuses: `inquiry`, `new`, `contacted`, `confirmed`, `cancelled`
Check: `check_out` > `check_in`
Indexes: `booking_avail_lookup_idx`, `booking_prop_cal_idx`, `booking_customer_idx`

### `booking_guests`

| Column            | Type                        | Constraints                    |
|-------------------|-----------------------------|--------------------------------|
| `id`              | `BIGSERIAL`                 | PK                             |
| `created_at`      | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at`      | `TIMESTAMPTZ`               | NOT NULL                       |
| `booking_id`      | `BIGINT`                    | NOT NULL, FK → booking_inquiries.id |
| `full_name`       | `VARCHAR(255)`              | NOT NULL                       |
| `guest_type`      | `VARCHAR(10)`               | NOT NULL, DEFAULT 'adult'       |
| `age`             | `SMALLINT`                  | NULLABLE                       |
| `document_number` | `VARCHAR(100)`              | NOT NULL, DEFAULT ''            |
| `phone`           | `VARCHAR(50)`               | NOT NULL, DEFAULT ''            |
| `email`           | `VARCHAR(254)`              | NOT NULL, DEFAULT ''            |
| `is_primary`      | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE        |

Guest types: `adult`, `child`, `infant`
Unique: `unique_primary_guest_per_booking` (partial, WHERE `is_primary`=TRUE)

---

## App: `services`

### `service_categories`

| Column       | Type                        | Constraints                    |
|--------------|-----------------------------|--------------------------------|
| `id`         | `BIGSERIAL`                 | PK                             |
| `created_at` | `TIMESTAMPTZ`               | NOT NULL                       |
| `updated_at` | `TIMESTAMPTZ`               | NOT NULL                       |
| `name`       | `VARCHAR(100)`              | NOT NULL, UNIQUE               |
| `slug`       | `VARCHAR(120)`              | UNIQUE, NULLABLE               |
| `description`| `TEXT`                      | NOT NULL, DEFAULT ''            |
| `icon`       | `VARCHAR(100)`              | NULLABLE                       |
| `is_active`  | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE         |

### `hotel_services`

| Column                    | Type                        | Constraints                        |
|---------------------------|-----------------------------|------------------------------------|
| `id`                      | `BIGSERIAL`                 | PK                                 |
| `created_at`              | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`              | `TIMESTAMPTZ`               | NOT NULL                           |
| `hotel_id`                | `BIGINT`                    | NOT NULL, FK → hotels.id           |
| `category_id`             | `BIGINT`                    | NULLABLE, FK → service_categories.id|
| `name`                    | `VARCHAR(255)`              | NOT NULL                           |
| `slug`                    | `VARCHAR(255)`              | NULLABLE                           |
| `short_description`       | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `description`             | `TEXT`                      | NOT NULL, DEFAULT ''                |
| `price`                   | `NUMERIC(10,2)`             | NOT NULL, DEFAULT 0, CHECK ≥ 0     |
| `currency`                | `VARCHAR(10)`               | NOT NULL, DEFAULT 'USD'            |
| `pricing_type`            | `VARCHAR(20)`               | NOT NULL, DEFAULT 'on_request'      |
| `duration_minutes`        | `SMALLINT`                  | NULLABLE                           |
| `available_from`          | `TIME`                      | NULLABLE                           |
| `available_until`         | `TIME`                      | NULLABLE                           |
| `advance_booking_required`| `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `is_featured`             | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `is_active`               | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Pricing types: `free`, `per_person`, `per_booking`, `per_night`, `on_request`
Uniques: `unique_service_name_per_hotel`, `unique_service_slug_per_hotel`
Indexes: `service_active_idx`, `service_category_idx`

### `service_images`

| Column          | Type                        | Constraints                        |
|-----------------|-----------------------------|------------------------------------|
| `id`            | `BIGSERIAL`                 | PK                                 |
| `created_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`    | `TIMESTAMPTZ`               | NOT NULL                           |
| `service_id`    | `BIGINT`                    | NOT NULL, FK → hotel_services.id   |
| `image`         | `VARCHAR(100)`              | NOT NULL                           |
| `thumbnail`     | `VARCHAR(100)`              | NULLABLE                           |
| `caption`       | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `alt_text`      | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `display_order` | `SMALLINT`                  | NOT NULL, DEFAULT 0, CHECK ≥ 0    |
| `is_cover`      | `BOOLEAN`                   | NOT NULL, DEFAULT FALSE            |
| `is_active`     | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Unique: `unique_cover_image_per_service` (partial)

---

## App: `reviews`

### `reviews`

| Column           | Type                        | Constraints                        |
|------------------|-----------------------------|------------------------------------|
| `id`             | `BIGSERIAL`                 | PK                                 |
| `created_at`     | `TIMESTAMPTZ`               | NOT NULL                           |
| `updated_at`     | `TIMESTAMPTZ`               | NOT NULL                           |
| `hotel_id`       | `BIGINT`                    | NOT NULL, FK → hotels.id           |
| `user_id`        | `BIGINT`                    | NULLABLE, FK → users.id            |
| `guest_name`     | `VARCHAR(255)`              | NOT NULL                           |
| `guest_email`    | `VARCHAR(254)`              | NOT NULL, DEFAULT ''                |
| `rating`         | `SMALLINT`                  | NOT NULL, CHECK 1-5                |
| `title`          | `VARCHAR(255)`              | NOT NULL, DEFAULT ''                |
| `comment`        | `TEXT`                      | NOT NULL                           |
| `cleanliness`    | `SMALLINT`                  | NULLABLE, CHECK 1-5                |
| `location`       | `SMALLINT`                  | NULLABLE, CHECK 1-5                |
| `staff`          | `SMALLINT`                  | NULLABLE, CHECK 1-5                |
| `comfort`        | `SMALLINT`                  | NULLABLE, CHECK 1-5                |
| `value_for_money`| `SMALLINT`                  | NULLABLE, CHECK 1-5                |
| `is_active`      | `BOOLEAN`                   | NOT NULL, DEFAULT TRUE             |

Indexes: `review_public_idx`, `review_rating_idx`, `review_email_lookup_idx`
