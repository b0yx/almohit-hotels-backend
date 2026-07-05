<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelApiDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        $admin = User::query()->create([
            'email' => 'hotel-admin@example.com',
            'full_name' => 'Hotel Admin',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);

        return [
            'Authorization' => 'Bearer '.$admin->createToken('hotel-api-test')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    public function test_property_create_accepts_details_timezone_and_facilities(): void
    {
        $headers = $this->adminHeaders();
        $category = FacilityCategory::query()->create(['name' => 'Core Facilities']);
        $wifi = Facility::query()->create([
            'facility_category_id' => $category->id,
            'name' => 'Wi-Fi',
            'slug' => 'wi-fi',
        ]);
        $parking = Facility::query()->create([
            'facility_category_id' => $category->id,
            'name' => 'Parking',
            'slug' => 'parking',
        ]);

        $response = $this->postJson('/api/properties/', [
            'name' => 'Details Hotel',
            'property_type' => 'hotel',
            'country' => 'Oman',
            'city' => 'Muscat',
            'address' => 'Beach Road',
            'stars' => 5,
            'description' => 'Short public description.',
            'details' => 'Long editable hotel details for the property page.',
            'timezone' => 'Asia/Muscat',
            'facility_ids' => [$wifi->id, $parking->id],
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('description', 'Short public description.')
            ->assertJsonPath('details', 'Long editable hotel details for the property page.')
            ->assertJsonPath('timezone', 'Asia/Muscat')
            ->assertJsonCount(2, 'amenities');

        $hotelId = $response->json('id');
        $this->assertDatabaseHas('hotels', [
            'id' => $hotelId,
            'description' => 'Short public description.',
            'details' => 'Long editable hotel details for the property page.',
            'timezone' => 'Asia/Muscat',
        ]);
        $this->assertDatabaseHas('facility_hotel', ['hotel_id' => $hotelId, 'facility_id' => $wifi->id]);
        $this->assertDatabaseHas('facility_hotel', ['hotel_id' => $hotelId, 'facility_id' => $parking->id]);
    }

    public function test_property_update_changes_details_and_resyncs_facilities(): void
    {
        $headers = $this->adminHeaders();
        $category = FacilityCategory::query()->create(['name' => 'Guest Facilities']);
        $oldFacility = Facility::query()->create(['facility_category_id' => $category->id, 'name' => 'Old Facility']);
        $newFacility = Facility::query()->create(['facility_category_id' => $category->id, 'name' => 'New Facility']);

        $create = $this->postJson('/api/properties/', [
            'name' => 'Editable Hotel',
            'property_type' => 'hotel',
            'country' => 'Yemen',
            'city' => 'Aden',
            'address' => 'Main Street',
            'stars' => 4,
            'details' => 'Original details',
            'timezone' => 'Asia/Aden',
            'facility_ids' => [$oldFacility->id],
        ], $headers)->assertCreated();

        $hotelId = $create->json('id');

        $this->patchJson("/api/properties/{$hotelId}/", [
            'details' => 'Updated long details',
            'timezone' => 'Asia/Muscat',
            'facility_ids' => [$newFacility->id],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('details', 'Updated long details')
            ->assertJsonPath('timezone', 'Asia/Muscat')
            ->assertJsonCount(1, 'amenities')
            ->assertJsonPath('amenities.0.id', $newFacility->id);

        $this->assertDatabaseHas('hotels', [
            'id' => $hotelId,
            'details' => 'Updated long details',
            'timezone' => 'Asia/Muscat',
        ]);
        $this->assertDatabaseMissing('facility_hotel', ['hotel_id' => $hotelId, 'facility_id' => $oldFacility->id]);
        $this->assertDatabaseHas('facility_hotel', ['hotel_id' => $hotelId, 'facility_id' => $newFacility->id]);
    }

    public function test_facility_can_be_attached_to_multiple_properties(): void
    {
        $headers = $this->adminHeaders();
        $firstHotel = Hotel::query()->create([
            'name' => 'First Property',
            'slug' => 'first-property',
            'property_type' => 'hotel',
            'country' => 'Yemen',
            'city' => 'Aden',
            'address' => 'First Street',
            'stars' => 4,
        ]);
        $secondHotel = Hotel::query()->create([
            'name' => 'Second Property',
            'slug' => 'second-property',
            'property_type' => 'hotel',
            'country' => 'Oman',
            'city' => 'Muscat',
            'address' => 'Second Street',
            'stars' => 5,
        ]);

        $facility = Facility::query()->create([
            'name' => 'Shared Pool',
            'slug' => 'shared-pool',
        ]);

        $this->patchJson('/api/facilities/'.$facility->id.'/', [
            'property_ids' => [$firstHotel->id, $secondHotel->id],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('property_ids', [$firstHotel->id, $secondHotel->id])
            ->assertJsonCount(2, 'properties');

        $this->assertDatabaseHas('facility_hotel', ['hotel_id' => $firstHotel->id, 'facility_id' => $facility->id]);
        $this->assertDatabaseHas('facility_hotel', ['hotel_id' => $secondHotel->id, 'facility_id' => $facility->id]);

        $this->getJson('/api/properties/'.$firstHotel->id.'/', $headers)
            ->assertOk()
            ->assertJsonPath('facility_ids', [$facility->id])
            ->assertJsonPath('amenities.0.id', $facility->id);
    }
}
