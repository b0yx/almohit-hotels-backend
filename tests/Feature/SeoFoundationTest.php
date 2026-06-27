<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'admin-seo@test.com',
            'full_name' => 'SEO Admin',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $this->admin->id, 'token' => hash('sha256', $plain)]);
        $this->adminToken = $plain;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer '.$this->adminToken];
    }

    private function propertyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'SEO Hotel',
            'slug' => 'seo-hotel',
            'subdomain' => 'seohotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'SEO Street',
            'stars' => 4,
        ], $overrides);
    }

    public function test_hotel_seo_fields_are_saved_and_returned(): void
    {
        $response = $this->postJson('/api/properties/', $this->propertyPayload([
            'meta_title' => 'Dubai family hotel',
            'meta_description' => 'Book a family-friendly hotel in Dubai with practical amenities and central access.',
            'meta_title_ar' => 'فندق عائلي في دبي',
            'meta_description_ar' => 'احجز فندقا مناسبا للعائلات في دبي مع مرافق عملية وموقع مركزي.',
        ]), $this->authHeader());

        $response->assertCreated()
            ->assertJsonPath('meta_title', 'Dubai family hotel')
            ->assertJsonPath('meta_description', 'Book a family-friendly hotel in Dubai with practical amenities and central access.')
            ->assertJsonPath('meta_title_ar', 'فندق عائلي في دبي')
            ->assertJsonPath('meta_description_ar', 'احجز فندقا مناسبا للعائلات في دبي مع مرافق عملية وموقع مركزي.');

        $hotelId = $response->json('id');
        $this->assertDatabaseHas('hotels', [
            'id' => $hotelId,
            'meta_title_ar' => 'فندق عائلي في دبي',
        ]);

        $this->getJson('/api/properties/'.$hotelId.'/', $this->authHeader())
            ->assertOk()
            ->assertJsonPath('meta_title', 'Dubai family hotel')
            ->assertJsonPath('meta_title_ar', 'فندق عائلي في دبي');
    }

    public function test_hotel_english_only_payload_still_works_and_arabic_seo_is_optional(): void
    {
        $this->postJson('/api/properties/', $this->propertyPayload([
            'meta_title' => 'English SEO title',
            'meta_description' => 'English SEO description for hotel search pages and frontend rendering.',
        ]), $this->authHeader())
            ->assertCreated()
            ->assertJsonPath('meta_title', 'English SEO title')
            ->assertJsonPath('meta_title_ar', null)
            ->assertJsonPath('meta_description_ar', null);
    }

    public function test_hotel_seo_fields_accept_null_values(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Null SEO Hotel',
            'slug' => 'null-seo-hotel',
            'subdomain' => 'nullseo',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Null Street',
            'stars' => 4,
            'meta_title' => 'Initial title',
            'meta_description' => 'Initial description',
            'meta_title_ar' => 'عنوان أولي',
            'meta_description_ar' => 'وصف أولي',
        ]);

        $this->patchJson('/api/properties/'.$hotel->id.'/', [
            'meta_title' => null,
            'meta_description' => null,
            'meta_title_ar' => null,
            'meta_description_ar' => null,
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('meta_title', null)
            ->assertJsonPath('meta_description', null)
            ->assertJsonPath('meta_title_ar', null)
            ->assertJsonPath('meta_description_ar', null);
    }
}
