<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestRoomSearchLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::query()->create([
            'name' => 'Guest Search Hotel',
            'slug' => 'guest-search-hotel',
            'subdomain' => 'guestsearch',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Search Street',
            'stars' => 4,
            'publishing_status' => 'published',
            'is_active' => true,
        ]);
    }

    public function test_rooms_search_returns_english_by_default(): void
    {
        RoomType::query()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe Room',
            'name_ar' => 'غرفة ديلوكس',
            'description' => 'A spacious deluxe room.',
            'description_ar' => 'غرفة ديلوكس واسعة ومريحة.',
            'max_adults' => 2,
            'max_children' => 1,
            'base_price' => 120,
            'total_units' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/properties/'.$this->hotel->id.'/rooms/search/', ['X-Locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('rooms.0.name', 'Deluxe Room')
            ->assertJsonPath('rooms.0.description', 'A spacious deluxe room.')
            ->assertJsonMissingPath('rooms.0.name_ar')
            ->assertJsonMissingPath('rooms.0.description_ar');
    }

    public function test_rooms_search_returns_arabic_when_available(): void
    {
        RoomType::query()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe Room',
            'name_ar' => 'غرفة ديلوكس',
            'description' => 'A spacious deluxe room.',
            'description_ar' => 'غرفة ديلوكس واسعة ومريحة.',
            'max_adults' => 2,
            'max_children' => 1,
            'base_price' => 120,
            'total_units' => 3,
            'is_active' => true,
        ]);

        $this->getJson('/api/properties/'.$this->hotel->id.'/rooms/search/', ['X-Locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('rooms.0.name', 'غرفة ديلوكس')
            ->assertJsonPath('rooms.0.description', 'غرفة ديلوكس واسعة ومريحة.')
            ->assertJsonMissingPath('rooms.0.name_ar')
            ->assertJsonMissingPath('rooms.0.description_ar');
    }

    public function test_rooms_search_falls_back_to_english_when_arabic_values_are_missing(): void
    {
        RoomType::query()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Room',
            'name_ar' => null,
            'description' => 'A practical standard room.',
            'description_ar' => null,
            'max_adults' => 2,
            'max_children' => 0,
            'base_price' => 90,
            'total_units' => 2,
            'is_active' => true,
        ]);

        $this->getJson('/api/properties/'.$this->hotel->id.'/rooms/search/', ['X-Locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('rooms.0.name', 'Standard Room')
            ->assertJsonPath('rooms.0.description', 'A practical standard room.');
    }

    public function test_availability_units_return_localized_room_names(): void
    {
        RoomType::query()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Family Suite',
            'name_ar' => 'جناح عائلي',
            'description' => 'A suite for families.',
            'description_ar' => 'جناح مناسب للعائلات.',
            'max_adults' => 3,
            'max_children' => 2,
            'base_price' => 180,
            'total_units' => 4,
            'is_active' => true,
        ]);

        $this->getJson('/api/properties/'.$this->hotel->id.'/availability/', ['X-Locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('units.0.name', 'جناح عائلي')
            ->assertJsonPath('available_rooms.0.name', 'جناح عائلي');
    }
}
