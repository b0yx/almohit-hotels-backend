<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyCreateVideoUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);
    }

    public function test_video_url_empty_string_is_accepted_on_create(): void
    {
        $admin = User::query()->create([
            'email' => 'admin@example.com',
            'full_name' => 'Admin',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
        ])->postJson('/api/properties', [
            'name' => 'Test Hotel',
            'property_type' => 'hotel',
            'country' => 'Oman',
            'city' => 'Muscat',
            'address' => '123 Main St',
            'stars' => 4,
            'video_url' => '',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('hotels', [
            'name' => 'Test Hotel',
            'video_url' => '',
        ]);
    }
}
