<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['hotel_id', 'is_active'], 'idx_reviews_hotel_active');
        });

        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->index(['status'], 'idx_booking_status');
            $table->index(['customer_id', 'status'], 'idx_booking_customer_status');
            $table->index(['hotel_id', 'status'], 'idx_booking_hotel_status');
        });

        Schema::table('room_type_images', function (Blueprint $table) {
            $table->index(['room_type_id', 'is_active'], 'idx_room_type_images_active');
        });

        Schema::table('service_images', function (Blueprint $table) {
            $table->index(['hotel_service_id', 'is_active'], 'idx_service_images_active');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index(['status'], 'idx_contact_status');
        });

        Schema::table('booking_guests', function (Blueprint $table) {
            $table->index(['booking_inquiry_id'], 'idx_booking_guests_inquiry');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->index(['country', 'city'], 'idx_hotels_country_city');
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->index(['hotel_id', 'is_active'], 'idx_room_types_hotel_active');
        });

        Schema::table('hotel_services', function (Blueprint $table) {
            $table->index(['hotel_id', 'is_active', 'is_featured'], 'idx_services_hotel_active_featured');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->dropIndex('idx_reviews_hotel_active'));
        Schema::table('booking_inquiries', fn (Blueprint $t) => $t->dropIndex(['idx_booking_status', 'idx_booking_customer_status', 'idx_booking_hotel_status']));
        Schema::table('room_type_images', fn (Blueprint $t) => $t->dropIndex('idx_room_type_images_active'));
        Schema::table('service_images', fn (Blueprint $t) => $t->dropIndex('idx_service_images_active'));
        Schema::table('contact_messages', fn (Blueprint $t) => $t->dropIndex('idx_contact_status'));
        Schema::table('booking_guests', fn (Blueprint $t) => $t->dropIndex('idx_booking_guests_inquiry'));
        Schema::table('hotels', fn (Blueprint $t) => $t->dropIndex('idx_hotels_country_city'));
        Schema::table('room_types', fn (Blueprint $t) => $t->dropIndex('idx_room_types_hotel_active'));
        Schema::table('hotel_services', fn (Blueprint $t) => $t->dropIndex('idx_services_hotel_active_featured'));
    }
};
