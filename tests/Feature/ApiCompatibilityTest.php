<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_route_matches_django_shape(): void
    {
        $this->getJson('/api/health/')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_login_returns_django_compatible_auth_shape(): void
    {
        User::query()->create([
            'email' => 'admin@example.com',
            'full_name' => 'Admin User',
            'role' => 'admin',
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/admin/login/', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure(['token', 'access', 'token_type', 'role', 'redirect_url', 'user' => ['id', 'email', 'full_name', 'role']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('role', 'admin');
    }

    public function test_public_hotel_context_uses_subdomain_header(): void
    {
        Hotel::query()->create([
            'name' => 'Demo Hotel',
            'slug' => 'demo-hotel',
            'subdomain' => 'demo',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        $this->withHeader('X-Hotel-Subdomain', 'demo')
            ->getJson('/api/public/hotel-context/')
            ->assertOk()
            ->assertJsonPath('status', 'available')
            ->assertJsonPath('property.subdomain', 'demo');
    }

    public function test_booking_inquiry_returns_normalized_response(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Booking Hotel',
            'slug' => 'booking-hotel',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);
        $room = RoomType::query()->create([
            'hotel_id' => $hotel->id,
            'name' => 'Standard Room',
            'max_adults' => 2,
            'base_price' => 100,
            'total_units' => 3,
        ]);

        $this->postJson('/api/bookings/inquiry/', [
            'customer_name' => 'Guest One',
            'phone' => '+100000000',
            'email' => 'guest@example.com',
            'property' => $hotel->id,
            'room_type' => $room->id,
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-03',
            'adults' => 2,
            'children' => 0,
        ])->assertCreated()
            ->assertJsonStructure(['booking_id', 'customer_name', 'property_id', 'room_type_id', 'estimated_total', 'status'])
            ->assertJsonPath('estimated_total', '200.00')
            ->assertJsonPath('status', 'new');
    }
}
