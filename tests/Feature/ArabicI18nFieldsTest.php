<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArabicI18nFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'admin-ar@test.com',
            'full_name' => 'Arabic Admin',
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

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->adminToken];
    }

    private function propertyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'subdomain' => 'testhotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => '123 Test St',
            'stars' => 4,
        ], $overrides);
    }

    public function test_property_post_patch_and_get_return_arabic_fields(): void
    {
        $response = $this->postJson('/api/properties/', $this->propertyPayload([
            'name_ar' => 'فندق الاختبار',
            'description_ar' => 'وصف عربي للفندق',
            'short_description_ar' => 'وصف قصير عربي',
        ]), $this->authHeader());

        $response->assertCreated()
            ->assertJsonPath('name_ar', 'فندق الاختبار')
            ->assertJsonPath('description_ar', 'وصف عربي للفندق')
            ->assertJsonPath('short_description_ar', 'وصف قصير عربي');

        $hotelId = $response->json('id');

        $this->patchJson('/api/properties/'.$hotelId.'/', [
            'name_ar' => null,
            'description_ar' => '',
            'short_description_ar' => 'تحديث عربي',
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('name_ar', null)
            ->assertJsonPath('description_ar', null)
            ->assertJsonPath('short_description_ar', 'تحديث عربي');

        $this->getJson('/api/properties/'.$hotelId.'/', $this->authHeader())
            ->assertOk()
            ->assertJsonPath('name_ar', null)
            ->assertJsonPath('description_ar', null)
            ->assertJsonPath('short_description_ar', 'تحديث عربي');
    }

    public function test_nested_policy_arabic_fields_are_saved_returned_and_clearable(): void
    {
        $response = $this->postJson('/api/properties/', $this->propertyPayload([
            'policy' => [
                'cancellation_policy_ar' => 'سياسة الإلغاء',
                'children_policy_ar' => 'سياسة الأطفال',
                'pet_policy_ar' => 'سياسة الحيوانات',
                'smoking_policy_ar' => 'سياسة التدخين',
                'extra_bed_policy_ar' => 'سياسة السرير الإضافي',
            ],
        ]), $this->authHeader());

        $response->assertCreated()
            ->assertJsonPath('policy.cancellation_policy_ar', 'سياسة الإلغاء')
            ->assertJsonPath('policy.children_policy_ar', 'سياسة الأطفال')
            ->assertJsonPath('policy.pet_policy_ar', 'سياسة الحيوانات')
            ->assertJsonPath('policy.smoking_policy_ar', 'سياسة التدخين')
            ->assertJsonPath('policy.extra_bed_policy_ar', 'سياسة السرير الإضافي');

        $hotelId = $response->json('id');

        $this->patchJson('/api/properties/'.$hotelId.'/', [
            'policy' => [
                'cancellation_policy_ar' => null,
                'children_policy_ar' => '',
            ],
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('policy.cancellation_policy_ar', null)
            ->assertJsonPath('policy.children_policy_ar', null);
    }

    public function test_room_type_service_and_amenity_crud_support_arabic_fields(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Arabic Field Hotel',
            'slug' => 'arabic-field-hotel',
            'subdomain' => 'arabicfield',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        $room = $this->postJson('/api/room-types/', [
            'hotel_id' => $hotel->id,
            'name' => 'Deluxe Room',
            'name_ar' => 'غرفة ديلوكس',
            'description_ar' => 'وصف الغرفة بالعربية',
            'max_adults' => 2,
            'total_units' => 3,
        ], $this->authHeader());

        $room->assertCreated()
            ->assertJsonPath('name_ar', 'غرفة ديلوكس')
            ->assertJsonPath('description_ar', 'وصف الغرفة بالعربية');

        $this->patchJson('/api/room-types/'.$room->json('id').'/', [
            'name_ar' => 'غرفة محدثة',
            'description_ar' => null,
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('name_ar', 'غرفة محدثة')
            ->assertJsonPath('description_ar', null);

        $service = $this->postJson('/api/property-services/', [
            'hotel_id' => $hotel->id,
            'name' => 'Airport Transfer',
            'name_ar' => 'نقل المطار',
            'short_description_ar' => 'نقل من وإلى المطار',
            'description_ar' => 'تفاصيل خدمة النقل بالعربية',
        ], $this->authHeader());

        $service->assertCreated()
            ->assertJsonPath('name_ar', 'نقل المطار')
            ->assertJsonPath('short_description_ar', 'نقل من وإلى المطار')
            ->assertJsonPath('description_ar', 'تفاصيل خدمة النقل بالعربية');

        $this->patchJson('/api/property-services/'.$service->json('id').'/', [
            'name_ar' => 'نقل محدث',
            'short_description_ar' => null,
            'description_ar' => null,
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('name_ar', 'نقل محدث')
            ->assertJsonPath('short_description_ar', null)
            ->assertJsonPath('description_ar', null);

        $amenity = $this->postJson('/api/property-amenities/', [
            'name' => 'WiFi',
            'name_ar' => 'واي فاي',
        ], $this->authHeader());

        $amenity->assertCreated()->assertJsonPath('name_ar', 'واي فاي');

        $this->patchJson('/api/property-amenities/'.$amenity->json('id').'/', [
            'name_ar' => null,
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('name_ar', null);
    }

    public function test_creating_records_without_arabic_fields_still_succeeds(): void
    {
        $hotelResponse = $this->postJson('/api/properties/', $this->propertyPayload([
            'slug' => 'english-only-hotel',
            'subdomain' => 'englishonly',
        ]), $this->authHeader());

        $hotelResponse->assertCreated()
            ->assertJsonPath('name_ar', null)
            ->assertJsonPath('description_ar', null)
            ->assertJsonPath('short_description_ar', null);

        $this->postJson('/api/room-types/', [
            'hotel_id' => $hotelResponse->json('id'),
            'name' => 'Standard Room',
            'max_adults' => 2,
        ], $this->authHeader())->assertCreated();

        $this->postJson('/api/property-services/', [
            'hotel_id' => $hotelResponse->json('id'),
            'name' => 'Breakfast',
        ], $this->authHeader())->assertCreated();

        $this->postJson('/api/property-amenities/', [
            'name' => 'Parking',
        ], $this->authHeader())->assertCreated();
    }
}
