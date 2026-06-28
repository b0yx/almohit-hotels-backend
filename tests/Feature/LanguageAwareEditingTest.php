<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LanguageAwareEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'lang-admin@test.com',
            'full_name' => 'Lang Admin',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->adminToken = $this->createToken($this->admin);
    }

    private function createToken(User $user): string
    {
        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return $plain;
    }

    private function authHeader(string $locale = 'en'): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Locale' => $locale,
        ];
    }

    public function test_english_update_changes_only_english_columns(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Original English Name',
            'name_ar' => 'الاسم العربي الأصلي',
            'description' => 'Original English Description',
            'description_ar' => 'الوصف العربي الأصلي',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Downtown',
            'stars' => 5,
        ]);

        $response = $this->patchJson('/api/properties/' . $hotel->id . '/', [
            'name' => 'Updated English Name',
            'description' => 'Updated English Description',
        ], $this->authHeader('en'));

        $response->assertOk()
            ->assertJsonPath('name', 'Updated English Name')
            ->assertJsonPath('description', 'Updated English Description');

        $fresh = $hotel->fresh();
        $this->assertEquals('Updated English Name', $fresh->name);
        $this->assertEquals('Updated English Description', $fresh->description);
        $this->assertEquals('الاسم العربي الأصلي', $fresh->name_ar);
        $this->assertEquals('الوصف العربي الأصلي', $fresh->description_ar);
    }

    public function test_arabic_update_changes_only_arabic_columns(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Original English Name',
            'name_ar' => 'الاسم العربي الأصلي',
            'description' => 'Original English Description',
            'description_ar' => 'الوصف العربي الأصلي',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Downtown',
            'stars' => 5,
        ]);

        $response = $this->patchJson('/api/properties/' . $hotel->id . '/', [
            'name' => 'الاسم العربي المحدث',
            'description' => 'الوصف العربي المحدث',
        ], $this->authHeader('ar'));

        $response->assertOk()
            ->assertJsonPath('name', 'الاسم العربي المحدث')
            ->assertJsonPath('description', 'الوصف العربي المحدث');

        $fresh = $hotel->fresh();
        $this->assertEquals('Original English Name', $fresh->name);
        $this->assertEquals('Original English Description', $fresh->description);
        $this->assertEquals('الاسم العربي المحدث', $fresh->name_ar);
        $this->assertEquals('الوصف العربي المحدث', $fresh->description_ar);
    }

    public function test_room_type_service_and_amenity_language_aware_crud(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Hotel One',
            'slug' => 'hotel-one',
            'subdomain' => 'hotelone',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        // Create Room Type in Arabic
        $roomResponse = $this->postJson('/api/room-types/', [
            'hotel_id' => $hotel->id,
            'name' => 'غرفة تنفيذي',
            'description' => 'وصف الغرفة التنفيذية',
            'max_adults' => 2,
        ], $this->authHeader('ar'));

        $roomResponse->assertCreated()
            ->assertJsonPath('name', 'غرفة تنفيذي')
            ->assertJsonPath('description', 'وصف الغرفة التنفيذية');

        $roomId = $roomResponse->json('id');
        $this->assertDatabaseHas('room_types', [
            'id' => $roomId,
            'name_ar' => 'غرفة تنفيذي',
        ]);

        // Fetch in English mode
        $this->getJson('/api/room-types/' . $roomId . '/', $this->authHeader('en'))
            ->assertOk()
            ->assertJsonPath('description', null);

        // Fetch in Arabic mode
        $this->getJson('/api/room-types/' . $roomId . '/', $this->authHeader('ar'))
            ->assertOk()
            ->assertJsonPath('name', 'غرفة تنفيذي');
    }

    public function test_nested_policy_language_aware_update(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Policy Hotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'JBR',
            'stars' => 4,
        ]);

        $hotel->policy()->create([
            'cancellation_policy' => 'English Cancellation Policy',
            'cancellation_policy_ar' => 'سياسة الإلغاء القديمة',
        ]);

        $response = $this->patchJson('/api/properties/' . $hotel->id . '/', [
            'policy' => [
                'cancellation_policy' => 'سياسة الإلغاء المحدثة',
            ],
        ], $this->authHeader('ar'));

        $response->assertOk()
            ->assertJsonPath('policy.cancellation_policy', 'سياسة الإلغاء المحدثة');

        $policy = $hotel->fresh()->policy;
        $this->assertEquals('English Cancellation Policy', $policy->cancellation_policy);
        $this->assertEquals('سياسة الإلغاء المحدثة', $policy->cancellation_policy_ar);
    }
}
