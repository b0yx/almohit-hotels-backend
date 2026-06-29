<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelService;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\User;
use Database\Seeders\DemoPropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicServicesAndDemoImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_property_services_endpoint_is_accessible_and_filters_active_services_for_guests(): void
    {
        $hotel = Hotel::create([
            'slug' => 'demo-hotel-test',
            'name' => 'Test Hotel',
            'subdomain' => 'test',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Test Address',
            'stars' => 4,
            'short_description' => 'Test short description',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        $activeService = HotelService::create([
            'hotel_id' => $hotel->id,
            'name' => 'Active Service',
            'is_active' => true,
            'price' => 50.00,
        ]);

        $inactiveService = HotelService::create([
            'hotel_id' => $hotel->id,
            'name' => 'Inactive Service',
            'is_active' => false,
            'price' => 20.00,
        ]);

        $draftHotel = Hotel::create([
            'slug' => 'draft-hotel-test',
            'name' => 'Draft Hotel',
            'subdomain' => 'draft',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Draft Address',
            'stars' => 4,
            'short_description' => 'Draft short description',
            'publishing_status' => 'draft',
            'is_active' => true,
        ]);

        $draftService = HotelService::create([
            'hotel_id' => $draftHotel->id,
            'name' => 'Draft Hotel Service',
            'is_active' => true,
            'price' => 75.00,
        ]);

        // Guest index query
        $response = $this->getJson('/api/property-services/?property='.$hotel->id);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'results');
        $response->assertJsonPath('results.0.name', 'Active Service');

        $draftHotelResponse = $this->getJson('/api/property-services/?property='.$draftHotel->id);
        $draftHotelResponse->assertStatus(200);
        $draftHotelResponse->assertJsonCount(0, 'results');

        $draftShowResponse = $this->getJson('/api/property-services/'.$draftService->id.'/');
        $draftShowResponse->assertStatus(404);

        // Guest show query on inactive service -> 404
        $guestShowResponse = $this->getJson('/api/property-services/'.$inactiveService->id.'/');
        $guestShowResponse->assertStatus(404);

        // Admin show query on inactive service -> 200
        $admin = User::create([
            'email' => 'admin_test@example.com',
            'full_name' => 'Admin Test',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_superuser' => true,
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $adminShowResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/property-services/'.$inactiveService->id.'/');
        $adminShowResponse->assertStatus(200);
        $adminShowResponse->assertJsonPath('name', 'Inactive Service');
    }

    public function test_demo_properties_seeder_and_room_cover_image_url(): void
    {
        $this->seed(DemoPropertiesSeeder::class);

        $hotel = Hotel::query()->where('slug', 'grand-almohit-hotel')->firstOrFail();

        $hotel2Response = $this->getJson('/api/properties/'.$hotel->id.'/');
        $hotel2Response->assertStatus(200);
        $this->assertNotNull($hotel2Response->json('cover_image_url'));
        $this->assertNotEmpty($hotel2Response->json('images'));

        $roomSearchResponse = $this->getJson('/api/properties/'.$hotel->id.'/rooms/search/');
        $roomSearchResponse->assertStatus(200);
        $rooms = $roomSearchResponse->json('rooms');
        $this->assertNotEmpty($rooms);
        foreach ($rooms as $room) {
            $this->assertNotNull($room['cover_image_url']);
        }
    }

    public function test_room_search_cover_image_selection_priority_and_zero_images_degradation(): void
    {
        $hotel = Hotel::create([
            'slug' => 'priority-hotel-test',
            'name' => 'Priority Hotel',
            'subdomain' => 'priority',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Test Address',
            'stars' => 4,
            'short_description' => 'Test short description',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        // Room 1: Zero images
        $roomNoImages = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'No Images Room',
            'max_adults' => 2,
            'total_units' => 5,
            'base_price' => 100.00,
            'is_active' => true,
        ]);

        // Room 2: Gallery image first, then cover image
        $roomWithCover = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => 'With Cover Room',
            'max_adults' => 2,
            'total_units' => 5,
            'base_price' => 200.00,
            'is_active' => true,
        ]);

        RoomTypeImage::create([
            'room_type_id' => $roomWithCover->id,
            'image' => 'https://example.com/gallery.jpg',
            'is_cover' => false,
            'is_active' => true,
            'display_order' => 1,
        ]);

        RoomTypeImage::create([
            'room_type_id' => $roomWithCover->id,
            'image' => 'https://example.com/cover.jpg',
            'is_cover' => true,
            'is_active' => true,
            'display_order' => 2,
        ]);

        $response = $this->getJson('/api/properties/'.$hotel->id.'/rooms/search/');
        $response->assertStatus(200);

        $rooms = collect($response->json('rooms'));
        $noImgRes = $rooms->firstWhere('room_type_id', $roomNoImages->id);
        $withCoverRes = $rooms->firstWhere('room_type_id', $roomWithCover->id);

        $this->assertNull($noImgRes['cover_image_url']);
        $this->assertEquals('https://example.com/cover.jpg', $withCoverRes['cover_image_url']);
    }
}
