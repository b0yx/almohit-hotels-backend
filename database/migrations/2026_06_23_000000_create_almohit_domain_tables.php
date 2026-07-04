<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('api');
            $table->string('token', 128)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hashed_code', 128);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('resend_count')->default(0);
            $table->timestamp('resend_reset_at')->nullable();
            $table->timestamps();
        });

        Schema::create('facility_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->nullable()->unique();
            $table->string('short_description')->default('');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('pricing_type', 20)->default('included');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->boolean('advance_booking_required')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('subdomain', 63)->nullable()->unique();
            $table->string('property_type', 20)->default('hotel');
            $table->string('country', 100)->default('');
            $table->string('city', 100)->default('');
            $table->text('address')->nullable();
            $table->string('phone', 50)->default('');
            $table->string('email')->default('');
            $table->string('website')->default('');
            $table->unsignedSmallInteger('stars')->default(3);
            $table->text('description')->nullable();
            $table->longText('details')->nullable();
            $table->string('short_description', 300)->default('');
            $table->string('timezone', 64)->default('UTC');
            $table->json('languages_spoken')->nullable();
            $table->boolean('parking_available')->default(false);
            $table->boolean('airport_transfer')->default(false);
            $table->boolean('shuttle_service')->default(false);
            $table->unsignedSmallInteger('opening_year')->nullable();
            $table->unsignedSmallInteger('renovation_year')->nullable();
            $table->string('video_url')->default('');
            $table->string('virtual_tour_url')->default('');
            $table->boolean('is_active')->default(true)->index();
            $table->string('publishing_status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('facility_hotel', function (Blueprint $table) {
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['hotel_id', 'facility_id']);
        });

        Schema::create('hotel_user_assignments', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'hotel_id']);
        });

        Schema::create('hotel_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('thumbnail')->nullable();
            $table->string('caption')->default('');
            $table->string('alt_text')->default('');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['hotel_id', 'is_active']);
        });

        Schema::create('hotel_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained()->cascadeOnDelete();
            foreach (['cancellation_policy', 'children_policy', 'pet_policy', 'smoking_policy', 'late_check_in_policy', 'refund_policy', 'terms_and_conditions', 'extra_bed_policy', 'important_notes'] as $column) {
                $table->text($column)->nullable();
            }
            $table->time('check_in_from')->nullable();
            $table->time('check_in_to')->nullable();
            $table->time('check_out_from')->nullable();
            $table->time('check_out_to')->nullable();
            $table->timestamps();
        });

        Schema::create('property_social_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained()->cascadeOnDelete();
            foreach (['facebook_url', 'instagram_url', 'tiktok_url', 'twitter_url', 'youtube_url', 'linkedin_url', 'booking_com_url', 'agoda_url', 'airbnb_url', 'expedia_url'] as $column) {
                $table->string($column)->default('');
            }
            $table->string('whatsapp_number', 50)->default('');
            $table->string('telegram_username', 100)->default('');
            $table->timestamps();
        });

        Schema::create('property_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_contact_person')->default('');
            $table->string('contact_position', 150)->default('');
            $table->string('emergency_contact_number', 50)->default('');
            $table->timestamps();
        });

        Schema::create('property_setup_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('completion_percentage')->default(0);
            $table->unsignedSmallInteger('last_completed_step')->default(1);
            $table->timestamp('autosaved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('channel_manager_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider_name', 50);
            $table->string('external_property_id')->default('');
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('rates_synced_at')->nullable();
            $table->timestamp('availability_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('room_size', 7, 2)->nullable();
            $table->string('bed_type', 100)->default('');
            $table->boolean('smoking_allowed')->default(false);
            $table->unsignedSmallInteger('max_adults');
            $table->unsignedSmallInteger('max_children')->default(0);
            $table->unsignedSmallInteger('total_units')->default(1);
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('weekend_price', 10, 2)->nullable();
            $table->string('pricing_mode', 20)->default('per_night');
            $table->string('currency', 10)->default('USD');
            $table->boolean('extra_bed_allowed')->default(false);
            $table->decimal('extra_bed_price', 10, 2)->default(0);
            $table->boolean('breakfast_included')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['hotel_id', 'name']);
        });

        Schema::create('facility_room_type', function (Blueprint $table) {
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['room_type_id', 'facility_id']);
        });

        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('blocked_units')->default(1);
            $table->string('reason', 20)->default('maintenance');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['room_type_id', 'is_active', 'start_date', 'end_date']);
        });

        Schema::create('room_type_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('thumbnail')->nullable();
            $table->string('caption')->default('');
            $table->string('alt_text')->default('');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('season_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_per_night', 10, 2);
            $table->timestamps();
            $table->unique(['room_type_id', 'season_name', 'start_date', 'end_date']);
        });

        Schema::create('facility_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('thumbnail')->nullable();
            $table->string('caption')->default('');
            $table->string('alt_text')->default('');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone', 50);
            $table->string('email')->default('');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->unsignedSmallInteger('infants')->default(0);
            $table->boolean('extra_bed_needed')->default(false);
            $table->unsignedSmallInteger('extra_bed_count')->default(0);
            $table->decimal('estimated_total', 10, 2)->default(0);
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });

        Schema::create('booking_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('guest_type', 10)->default('adult');
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('document_number', 100)->default('');
            $table->string('phone', 50)->default('');
            $table->string('email')->default('');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_email')->default('');
            $table->unsignedSmallInteger('rating');
            $table->string('title')->default('');
            $table->text('comment');
            foreach (['cleanliness', 'location', 'staff', 'comfort', 'value_for_money'] as $column) {
                $table->unsignedSmallInteger($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20);
            $table->string('content_type')->default('');
            $table->string('object_id', 64);
            $table->string('object_repr');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_email')->default('');
            $table->string('actor_name')->default('');
            $table->json('changes')->nullable();
            $table->string('request_method', 12)->default('');
            $table->string('request_path', 500)->default('');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50)->default('');
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'contact_messages', 'audit_logs', 'reviews', 'booking_guests', 'booking_inquiries',
            'facility_images', 'room_prices',
            'room_type_images', 'availability_blocks', 'facility_room_type',
            'room_types', 'channel_manager_connections', 'property_setup_statuses',
            'property_contacts', 'property_social_media', 'hotel_policies',
            'hotel_images', 'hotel_user_assignments', 'facility_hotel',
            'hotels', 'facilities', 'facility_categories', 'email_otps', 'api_tokens',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
