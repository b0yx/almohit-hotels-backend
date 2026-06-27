<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('name_ar')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('short_description_ar', 300)->nullable();
        });

        Schema::table('hotel_policies', function (Blueprint $table) {
            $table->text('cancellation_policy_ar')->nullable();
            $table->text('children_policy_ar')->nullable();
            $table->text('pet_policy_ar')->nullable();
            $table->text('smoking_policy_ar')->nullable();
            $table->text('extra_bed_policy_ar')->nullable();
        });

        Schema::table('hotel_amenities', function (Blueprint $table) {
            $table->string('name_ar', 100)->nullable();
        });

        Schema::table('hotel_services', function (Blueprint $table) {
            $table->string('name_ar')->nullable();
            $table->string('short_description_ar')->nullable();
            $table->text('description_ar')->nullable();
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->string('name_ar')->nullable();
            $table->text('description_ar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'description_ar']);
        });

        Schema::table('hotel_services', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'short_description_ar', 'description_ar']);
        });

        Schema::table('hotel_amenities', function (Blueprint $table) {
            $table->dropColumn(['name_ar']);
        });

        Schema::table('hotel_policies', function (Blueprint $table) {
            $table->dropColumn([
                'cancellation_policy_ar',
                'children_policy_ar',
                'pet_policy_ar',
                'smoking_policy_ar',
                'extra_bed_policy_ar',
            ]);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'description_ar', 'short_description_ar']);
        });
    }
};
