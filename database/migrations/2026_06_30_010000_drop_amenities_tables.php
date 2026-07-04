<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hotel_amenity_room_type');
        Schema::dropIfExists('hotel_amenity_hotel');
        Schema::dropIfExists('hotel_amenities');
    }

    public function down(): void
    {
        // Intentionally irreversible: amenities replaced by facilities
    }
};
