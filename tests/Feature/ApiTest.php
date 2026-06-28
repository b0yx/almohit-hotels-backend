<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);
    }

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
            'email' => 'admin_test@example.com',
            'full_name' => 'Admin User',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    private function createStaff(): User
    {
        return User::query()->create([
            'email' => 'staff_test@example.com',
            'full_name' => 'Staff User',
            'role' => User::ROLE_STAFF,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    private function createCustomer(): User
    {
        return User::query()->create([
            'email' => 'customer_test@example.com',
            'full_name' => 'Customer User',
            'role' => User::ROLE_CUSTOMER,
            'is_staff' => false,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    // ─── AUTH TESTS ──────────────────────────────────────────

    public function test_signup_creates_inactive_customer_with_otp(): void
    {
        $response = $this->postJson('/api/auth/signup/', [
            'email' => 'new_guest@example.com',
            'full_name' => 'New Guest',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['detail', 'debug_code']);

        $this->assertDatabaseHas('users', [
            'email' => 'new_guest@example.com',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => false,
        ]);
    }

    public function test_login_authenticates_active_verified_user(): void
    {
        $user = $this->createCustomer();

        $response = $this->postJson('/api/auth/login/', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'token', 'access', 'user' => ['id', 'email', 'permissions']]);
    }

    public function test_invalid_login_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/login/', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['detail']);
    }

    public function test_profile_retrieval_and_update(): void
    {
        $user = $this->createCustomer();
        $headers = $this->headersFor($user);

        $this->getJson('/api/auth/me/', $headers)
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->patchJson('/api/auth/me/', ['full_name' => 'Updated Name'], $headers)
            ->assertOk()
            ->assertJsonPath('full_name', 'Updated Name');
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->createCustomer();
        $headers = $this->headersFor($user);

        $this->postJson('/api/auth/logout/', [], $headers)
            ->assertStatus(204);

        $this->getJson('/api/auth/me/', $headers)
            ->assertStatus(401);
    }

    // ─── USER MANAGEMENT (ADMIN ONLY) ──────────────────────────

    public function test_admin_can_list_users(): void
    {
        $admin = $this->createAdmin();
        $headers = $this->headersFor($admin);

        $response = $this->getJson('/api/auth/users/', $headers);
        $response->assertOk()
            ->assertJsonStructure(['count', 'results']);
    }

    public function test_staff_cannot_list_users(): void
    {
        $staff = $this->createStaff();
        $headers = $this->headersFor($staff);

        $this->getJson('/api/auth/users/', $headers)
            ->assertStatus(403);
    }

    // ─── PROPERTIES (STAFF/ADMIN) ──────────────────────────

    public function test_properties_crud_operations(): void
    {
        $admin = $this->createAdmin();
        $headers = $this->headersFor($admin);

        // Create Property
        $response = $this->postJson('/api/properties/', [
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'subdomain' => 'testhotel',
            'property_type' => 'hotel',
            'address' => '123 Test St',
            'stars' => 4,
            'country' => 'Germany',
            'city' => 'Berlin',
        ], $headers);

        $response->assertCreated();
        $hotelId = $response->json('id');

        // List Properties
        $this->getJson('/api/properties/', $headers)
            ->assertOk()
            ->assertJsonStructure(['count', 'results']);

        // Update Property
        $this->patchJson("/api/properties/$hotelId/", [
            'name' => 'Updated Test Hotel',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('name', 'Updated Test Hotel');

        // Delete Property
        $this->deleteJson("/api/properties/$hotelId/", [], $headers)
            ->assertStatus(204);
    }

    // ─── BOOKINGS ──────────────────────────────────────────

    public function test_booking_flow_inquiry_creation_confirmation(): void
    {
        $admin = $this->createAdmin();
        $headers = $this->headersFor($admin);

        $hotel = Hotel::query()->create([
            'name' => 'Demo Hotel',
            'slug' => 'demo-hotel',
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

        // Create Booking (Customer/Admin context)
        $response = $this->postJson('/api/bookings/', [
            'guest_name' => 'Booking Guest',
            'guest_phone' => '+4900000000',
            'guest_email' => 'guest@example.com',
            'property' => $hotel->id,
            'room_type' => $room->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
            'adults' => 2,
            'children' => 0,
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('status', 'new');
        $bookingId = $response->json('id');

        // Confirm Booking (Staff/Admin)
        $this->postJson('/api/bookings/confirm/', [
            'booking_id' => $bookingId,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'confirmed');

        // Cancel Booking (Customer/Admin)
        $this->postJson("/api/bookings/$bookingId/cancel/", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    // ─── REVIEWS ──────────────────────────────────────────

    public function test_reviews_submission_and_listing(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Demo Hotel',
            'slug' => 'demo-hotel',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        // Submit Review (Public)
        $this->postJson("/api/properties/{$hotel->id}/reviews/", [
            'guest_name' => 'Reviewer One',
            'guest_email' => 'reviewer@example.com',
            'rating' => 5,
            'comment' => 'Outstanding experience.',
        ])->assertCreated()
            ->assertJsonPath('rating', 5);

        // List Reviews
        $admin = $this->createAdmin();
        $this->getJson('/api/reviews/', $this->headersFor($admin))
            ->assertOk()
            ->assertJsonStructure(['count', 'results']);
    }

    // ─── ROLE-BASED ACCESS CONTROL (RBAC) TESTS ──────────────────────────

    public function test_role_based_endpoint_isolation(): void
    {
        $adminHeaders = $this->headersFor($this->createAdmin());
        $staffHeaders = $this->headersFor($this->createStaff());
        $customerHeaders = $this->headersFor($this->createCustomer());

        // Admin endpoint (GET /api/auth/users/)
        $this->getJson('/api/auth/users/', $adminHeaders)->assertOk();
        $this->getJson('/api/auth/users/', $staffHeaders)->assertStatus(403);
        $this->getJson('/api/auth/users/', $customerHeaders)->assertStatus(403);

        // Staff-only endpoint (GET /api/reviews/)
        $this->getJson('/api/reviews/', $adminHeaders)->assertOk();
        $this->getJson('/api/reviews/', $staffHeaders)->assertOk();
        $this->getJson('/api/reviews/', $customerHeaders)->assertStatus(403);
    }
}
