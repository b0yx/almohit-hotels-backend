<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Hotel;
use App\Models\User;
use Database\Seeders\DemoPropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HotelPolicyWindowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'policy-admin@test.com',
            'full_name' => 'Policy Admin',
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

    private function propertyPayload(array $policy): array
    {
        return [
            'name' => 'Policy Window Hotel',
            'slug' => 'policy-window-hotel',
            'subdomain' => 'policywindow',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Policy Street',
            'stars' => 4,
            'publishing_status' => 'published',
            'is_active' => true,
            'policy' => $policy,
        ];
    }

    public function test_policy_windows_are_saved_and_returned(): void
    {
        $response = $this->postJson('/api/properties/', $this->propertyPayload([
            'check_in_from' => '14:00',
            'check_in_to' => '00:00',
            'check_out_from' => '06:00',
            'check_out_to' => '12:00',
            'cancellation_policy' => 'Free cancellation before arrival.',
        ]), $this->authHeader());

        $response->assertCreated()
            ->assertJsonPath('policy.check_in_from', '14:00')
            ->assertJsonPath('policy.check_in_to', '00:00')
            ->assertJsonPath('policy.check_out_from', '06:00')
            ->assertJsonPath('policy.check_out_to', '12:00');

        $hotelId = $response->json('id');
        $this->assertDatabaseHas('hotel_policies', [
            'hotel_id' => $hotelId,
            'check_in_from' => '14:00:00',
            'check_in_to' => '00:00:00',
            'check_out_from' => '06:00:00',
            'check_out_to' => '12:00:00',
        ]);

        $this->getJson('/api/properties/'.$hotelId.'/', $this->authHeader())
            ->assertOk()
            ->assertJsonPath('policy.check_in_from', '14:00')
            ->assertJsonPath('policy.check_in_to', '00:00')
            ->assertJsonPath('policy.check_out_from', '06:00')
            ->assertJsonPath('policy.check_out_to', '12:00');
    }

    public function test_policy_windows_are_updated_and_null_values_persist(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Policy Update Hotel',
            'slug' => 'policy-update-hotel',
            'subdomain' => 'policyupdate',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Policy Street',
            'stars' => 4,
        ]);

        $hotel->policy()->create([
            'check_in_from' => '14:00:00',
            'check_in_to' => '00:00:00',
            'check_out_from' => '06:00:00',
            'check_out_to' => '12:00:00',
        ]);

        $this->patchJson('/api/properties/'.$hotel->id.'/', [
            'policy' => [
                'check_in_from' => '15:30:00',
                'check_in_to' => '',
                'check_out_from' => null,
                'check_out_to' => '11:00',
            ],
        ], $this->authHeader())
            ->assertOk()
            ->assertJsonPath('policy.check_in_from', '15:30')
            ->assertJsonPath('policy.check_in_to', null)
            ->assertJsonPath('policy.check_out_from', null)
            ->assertJsonPath('policy.check_out_to', '11:00');

        $this->assertDatabaseHas('hotel_policies', [
            'hotel_id' => $hotel->id,
            'check_in_from' => '15:30:00',
            'check_in_to' => null,
            'check_out_from' => null,
            'check_out_to' => '11:00:00',
        ]);
    }

    public function test_invalid_policy_window_times_return_validation_errors(): void
    {
        $this->postJson('/api/properties/', $this->propertyPayload([
            'check_in_from' => '24:00',
            'check_in_to' => '00:00',
            'check_out_from' => '06:00',
            'check_out_to' => '12:00',
        ]), $this->authHeader())
            ->assertStatus(422)
            ->assertJsonValidationErrors('policy.check_in_from');
    }

    public function test_public_property_endpoint_returns_policy_windows(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Public Policy Hotel',
            'slug' => 'public-policy-hotel',
            'subdomain' => 'publicpolicy',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Policy Street',
            'stars' => 4,
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        $hotel->policy()->create([
            'check_in_from' => '14:00:00',
            'check_in_to' => '00:00:00',
            'check_out_from' => '06:00:00',
            'check_out_to' => '12:00:00',
        ]);

        $this->getJson('/api/properties/'.$hotel->id.'/')
            ->assertOk()
            ->assertJsonPath('policy.check_in_from', '14:00')
            ->assertJsonPath('policy.check_in_to', '00:00')
            ->assertJsonPath('policy.check_out_from', '06:00')
            ->assertJsonPath('policy.check_out_to', '12:00');
    }

    public function test_demo_properties_seeder_creates_default_policy_windows(): void
    {
        $this->seed(DemoPropertiesSeeder::class);

        $hotel = Hotel::query()->where('slug', 'demo-hotel')->firstOrFail();

        $this->assertDatabaseHas('hotel_policies', [
            'hotel_id' => $hotel->id,
            'check_in_from' => '14:00:00',
            'check_in_to' => '00:00:00',
            'check_out_from' => '06:00:00',
            'check_out_to' => '12:00:00',
        ]);
    }
}
