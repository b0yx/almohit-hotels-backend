<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_ar')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $bedTypes = [
            'Single Bed',
            'Twin Beds',
            'Double Bed',
            'Queen Bed',
            'King Bed',
            'Sofa Bed',
            'Bunk Bed',
        ];

        foreach ($bedTypes as $index => $name) {
            DB::table('bed_types')->insert([
                'name' => $name,
                'display_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_types');
    }
};
