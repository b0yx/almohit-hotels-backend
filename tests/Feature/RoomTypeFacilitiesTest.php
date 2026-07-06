<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTypeFacilitiesTest extends TestCase
{
    use RefreshDatabase;

    private function headersFor(User $user): array
    {
        $token = $user->createToken('test_token')->plainTextToken;

        return [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ];
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'email' => 'admin_room_type_facilities@example.com',
            'full_name' => 'Admin User',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    public function test_room_type_facility_ids_are_synced_and_returned(): void
    {
        $admin = $this->createAdmin();
        $headers = $this->headersFor($admin);
        $hotel = Hotel::query()->create([
            'name' => 'Facility Hotel',
            'slug' => 'facility-hotel',
        ]);
        $category = FacilityCategory::query()->create(['name' => 'Room Amenities']);
        $wifi = Facility::query()->create([
            'facility_category_id' => $category->id,
            'name' => 'Fast Wi-Fi',
        ]);
        $breakfast = Facility::query()->create([
            'facility_category_id' => $category->id,
            'name' => 'Breakfast',
        ]);
        $parking = Facility::query()->create([
            'facility_category_id' => $category->id,
            'name' => 'Parking',
        ]);

        $create = $this->postJson('/api/room-types/', [
            'property' => $hotel->id,
            'name' => 'Deluxe Room',
            'bed_type' => 'King Bed',
            'max_adults' => 2,
            'facility_ids' => [$wifi->id, $breakfast->id],
        ], $headers);

        $create->assertCreated();
        $roomTypeId = $create->json('id');

        $this->assertDatabaseHas('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $wifi->id,
        ]);
        $this->assertDatabaseHas('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $breakfast->id,
        ]);

        $show = $this->getJson("/api/room-types/$roomTypeId/", $headers);
        $show->assertOk();
        $this->assertSame(
            [$wifi->id, $breakfast->id],
            collect($show->json('facility_ids'))->sort()->values()->all()
        );
        $this->assertSame(
            [$wifi->id, $breakfast->id],
            collect($show->json('amenity_ids'))->sort()->values()->all()
        );
        $this->assertSame(
            [$wifi->id, $breakfast->id],
            collect($show->json('facility_details'))->pluck('id')->sort()->values()->all()
        );
        $this->assertSame(
            [$wifi->id, $breakfast->id],
            collect($show->json('amenity_details'))->pluck('id')->sort()->values()->all()
        );

        $this->patchJson("/api/room-types/$roomTypeId/", [
            'facility_ids' => [$parking->id],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('facility_ids', [$parking->id])
            ->assertJsonPath('amenity_ids', [$parking->id]);

        $this->assertDatabaseMissing('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $wifi->id,
        ]);
        $this->assertDatabaseMissing('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $breakfast->id,
        ]);
        $this->assertDatabaseHas('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $parking->id,
        ]);

        $this->patchJson("/api/room-types/$roomTypeId/", [
            'facility_ids' => [],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('facility_ids', [])
            ->assertJsonPath('facility_details', []);

        $this->assertDatabaseMissing('facility_room_type', [
            'room_type_id' => $roomTypeId,
            'facility_id' => $parking->id,
        ]);
    }
}
