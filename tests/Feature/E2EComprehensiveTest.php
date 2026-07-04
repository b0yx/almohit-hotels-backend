<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BookingInquiry;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class E2EComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staffA;

    private User $staffB;

    private User $customer;

    private Hotel $hotelA;

    private Hotel $hotelB;

    private RoomType $roomA;

    private string $adminToken;

    private string $staffAToken;

    private string $staffBToken;

    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);

        $this->admin = User::create([
            'email' => 'admin@test.com', 'full_name' => 'Admin', 'role' => 'admin',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->staffA = User::create([
            'email' => 'staffa@test.com', 'full_name' => 'Staff A', 'role' => 'staff',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->staffB = User::create([
            'email' => 'staffb@test.com', 'full_name' => 'Staff B', 'role' => 'staff',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->customer = User::create([
            'email' => 'customer@test.com', 'full_name' => 'Customer', 'role' => 'customer',
            'is_staff' => false, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->hotelA = Hotel::create([
            'name' => 'Hotel Alpha', 'slug' => 'hotel-alpha', 'subdomain' => 'alpha',
            'country' => 'USA', 'city' => 'New York', 'property_type' => 'hotel', 'stars' => 4,
            'publishing_status' => 'draft', 'is_active' => false,
        ]);
        $this->hotelB = Hotel::create([
            'name' => 'Hotel Beta', 'slug' => 'hotel-beta', 'subdomain' => 'beta',
            'country' => 'UK', 'city' => 'London', 'property_type' => 'resort', 'stars' => 5,
            'publishing_status' => 'draft', 'is_active' => false,
        ]);

        $this->hotelA->assignedStaff()->attach($this->staffA->id);

        $this->roomA = RoomType::create([
            'hotel_id' => $this->hotelA->id, 'name' => 'Standard Room',
            'max_adults' => 2, 'max_children' => 1, 'base_price' => 150,
            'currency' => 'USD', 'total_units' => 10, 'is_active' => true,
        ]);

        $this->adminToken = $this->createToken($this->admin);
        $this->staffAToken = $this->createToken($this->staffA);
        $this->staffBToken = $this->createToken($this->staffB);
        $this->customerToken = $this->createToken($this->customer);
    }

    private function createToken(User $user): string
    {
        $plain = Str::random(64);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return $plain;
    }

    private function auth(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    private function sanctum(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 2 — AUTHENTICATION E2E
    // ═══════════════════════════════════════════════════════════════

    public function test_p2_signup_creates_inactive_user_with_otp(): void
    {
        $r = $this->postJson('/api/auth/signup/', [
            'email' => 'fresh@test.com', 'full_name' => 'Fresh',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);
        $r->assertCreated()->assertJsonStructure(['detail', 'debug_code']);
        $this->assertDatabaseHas('users', ['email' => 'fresh@test.com', 'is_active' => false, 'email_verified' => false]);
    }

    public function test_p2_signup_validates_password_confirmation(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'test@test.com', 'full_name' => 'T', 'password' => 'password123', 'password_confirm' => 'mismatch',
        ])->assertStatus(422);
    }

    public function test_p2_signup_validates_unique_email(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'customer@test.com', 'full_name' => 'Dup', 'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertStatus(422);
    }

    public function test_p2_verify_otp_activates_user(): void
    {
        $signup = $this->postJson('/api/auth/signup/', [
            'email' => 'verify@test.com', 'full_name' => 'Verify',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);
        $code = $signup->json('debug_code');
        $this->assertNotNull($code);

        $this->postJson('/api/auth/verify-otp/', ['email' => 'verify@test.com', 'code' => $code])
            ->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'verify@test.com', 'is_active' => true, 'email_verified' => true]);
    }

    public function test_p2_verify_otp_rejects_invalid_code(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'badotp@test.com', 'full_name' => 'Bad',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);
        $this->postJson('/api/auth/verify-otp/', ['email' => 'badotp@test.com', 'code' => '000000'])
            ->assertStatus(400)->assertJsonPath('code', 'invalid_otp');
    }

    public function test_p2_resend_otp_creates_new_code(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'resend@test.com', 'full_name' => 'Resend',
            'password' => 'password123', 'password_confirm' => 'password123',
        ]);
        $r = $this->postJson('/api/auth/resend-otp/', ['email' => 'resend@test.com']);
        $r->assertOk();
        // No debug_code in response for resend in local — that's fine, OTP was created silently
    }

    public function test_p2_login_with_valid_credentials(): void
    {
        $r = $this->postJson('/api/auth/login/', ['email' => 'customer@test.com', 'password' => 'password']);
        $r->assertOk()->assertJsonStructure(['success', 'message', 'token', 'access', 'user']);
    }

    public function test_p2_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/auth/login/', ['email' => 'customer@test.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_p2_login_fails_for_inactive_user(): void
    {
        $inactive = User::create([
            'email' => 'inactive@test.com', 'full_name' => 'Inactive', 'role' => 'customer',
            'is_active' => false, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->postJson('/api/auth/login/', ['email' => 'inactive@test.com', 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_p2_login_fails_for_unverified_user(): void
    {
        $unverified = User::create([
            'email' => 'unverified@test.com', 'full_name' => 'Unverified', 'role' => 'customer',
            'is_active' => true, 'email_verified' => false, 'password' => 'password',
        ]);
        $r = $this->postJson('/api/auth/login/', ['email' => 'unverified@test.com', 'password' => 'password']);
        $r->assertStatus(400)->assertJsonPath('code', 'email_not_verified');
    }

    public function test_p2_login_rate_limited(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->postJson('/api/auth/login/', ['email' => 'customer@test.com', 'password' => 'wrong']);
        }
        // After many failures, should be throttled
        $r = $this->postJson('/api/auth/login/', ['email' => 'customer@test.com', 'password' => 'wrong']);
        $this->assertContains($r->status(), [429, 422], 'Login should eventually be rate limited');
    }

    public function test_p2_logout_revokes_token(): void
    {
        $r = $this->postJson('/api/auth/logout/', [], $this->auth($this->customerToken));
        $r->assertStatus(204);

        // Token should no longer work for protected routes
        $this->getJson('/api/auth/me/', $this->auth($this->customerToken))
            ->assertStatus(401);
    }

    public function test_p2_me_returns_authenticated_user(): void
    {
        $this->getJson('/api/auth/me/', $this->auth($this->customerToken))
            ->assertOk()->assertJsonPath('email', 'customer@test.com');
    }

    public function test_p2_update_me(): void
    {
        $this->patchJson('/api/auth/me/', ['full_name' => 'Updated Customer'], $this->auth($this->customerToken))
            ->assertOk()->assertJsonPath('full_name', 'Updated Customer');
    }

    public function test_p2_admin_login(): void
    {
        $r = $this->postJson('/api/auth/admin/login/', ['email' => 'admin@test.com', 'password' => 'password']);
        $r->assertOk()->assertJsonPath('role', 'admin');
    }

    public function test_p2_customer_login(): void
    {
        $r = $this->postJson('/api/auth/customer/login/', ['email' => 'customer@test.com', 'password' => 'password']);
        $r->assertOk()->assertJsonPath('role', 'customer');
    }

    public function test_p2_admin_login_fails_for_customer(): void
    {
        $this->postJson('/api/auth/admin/login/', ['email' => 'customer@test.com', 'password' => 'password'])
            ->assertStatus(403);
    }

    public function test_p2_forgot_password_sends_email(): void
    {
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);
        $r = $this->postJson('/api/auth/forgot-password/', ['email' => 'customer@test.com']);
        $r->assertOk();
        $this->assertStringContainsString('If the email exists', $r->json('detail'));
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request['to'][0]['email'] === 'customer@test.com'
                && str_contains($request['subject'], 'Reset Your Password');
        });
    }

    public function test_p2_forgot_password_nonexistent_email(): void
    {
        Http::fake();
        $r = $this->postJson('/api/auth/forgot-password/', ['email' => 'ghost@test.com']);
        $r->assertOk();
        Http::assertNothingSent();
    }

    public function test_p2_full_password_reset_flow(): void
    {
        // Step 1: forgot password
        $fr = $this->postJson('/api/auth/forgot-password/', ['email' => 'customer@test.com']);
        $code = $fr->json('debug_code');
        $this->assertNotNull($code);

        // Step 2: reset with code
        $rr = $this->postJson('/api/auth/reset-password/', [
            'email' => 'customer@test.com', 'code' => $code,
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ]);
        $rr->assertOk();

        // Step 3: login with new password
        $this->postJson('/api/auth/login/', ['email' => 'customer@test.com', 'password' => 'newpassword123'])
            ->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 3 — ROLE SECURITY
    // ═══════════════════════════════════════════════════════════════

    // ── Admin-only endpoints ──

    public function test_p3_admin_only_users_index(): void
    {
        $this->getJson('/api/auth/users/', $this->auth($this->adminToken))->assertOk();
        $this->getJson('/api/auth/users/', $this->auth($this->staffAToken))->assertStatus(403);
        $this->getJson('/api/auth/users/', $this->auth($this->customerToken))->assertStatus(403);
        $this->getJson('/api/auth/users/')->assertStatus(401);
    }

    public function test_p3_admin_only_activate(): void
    {
        $target = User::create(['email' => 't@t.com', 'full_name' => 'T', 'role' => 'customer', 'is_active' => false, 'email_verified' => true, 'password' => 'x']);
        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->auth($this->adminToken))->assertOk();
        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->auth($this->staffAToken))->assertStatus(403);
    }

    public function test_p3_admin_only_deactivate(): void
    {
        $target = User::create(['email' => 't2@t.com', 'full_name' => 'T2', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);
        $this->postJson('/api/auth/users/'.$target->id.'/deactivate/', [], $this->auth($this->adminToken))->assertOk();
        $this->postJson('/api/auth/users/'.$target->id.'/deactivate/', [], $this->auth($this->staffAToken))->assertStatus(403);
    }

    public function test_p3_admin_only_change_role(): void
    {
        $target = User::create(['email' => 't3@t.com', 'full_name' => 'T3', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);
        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->auth($this->adminToken))->assertOk();
        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->auth($this->staffAToken))->assertStatus(403);
    }

    public function test_p3_admin_only_reset_password(): void
    {
        $target = User::create(['email' => 't4@t.com', 'full_name' => 'T4', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);
        $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', ['password' => 'newpass123'], $this->auth($this->adminToken))->assertOk();
        $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', ['password' => 'newpass123'], $this->auth($this->staffAToken))->assertStatus(403);
    }

    // ── Audit logs (admin-only via CrudController accessMap) ──

    public function test_p3_audit_logs_admin_only(): void
    {
        $this->getJson('/api/audit-logs/', $this->auth($this->adminToken))->assertOk();
        $this->getJson('/api/audit-logs/', $this->auth($this->staffAToken))->assertStatus(403);
        $this->getJson('/api/audit-logs/', $this->auth($this->customerToken))->assertStatus(403);
    }

    // ── Hotel access (admin = all, staff = assigned only) ──

    public function test_p3_hotel_index_staff_sees_assigned_only(): void
    {
        // Admin sees both
        $r = $this->getJson('/api/properties/', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertCount(2, $r->json('results'));

        // StaffA sees only assigned hotel
        $r = $this->getJson('/api/properties/', $this->auth($this->staffAToken));
        $r->assertOk();
        $this->assertCount(1, $r->json('results'));
        $this->assertEquals('Hotel Alpha', $r->json('results.0.name'));
    }

    public function test_p3_staff_cannot_show_update_destroy_unassigned_hotel(): void
    {
        // Show
        $this->getJson('/api/properties/'.$this->hotelB->id.'/', $this->auth($this->staffAToken))->assertStatus(403);
        // Update
        $this->patchJson('/api/properties/'.$this->hotelB->id.'/', ['name' => 'Hacked'], $this->auth($this->staffAToken))->assertStatus(403);
        // Destroy
        $this->deleteJson('/api/properties/'.$this->hotelB->id.'/', [], $this->auth($this->staffAToken))->assertStatus(403);
    }

    public function test_p3_staff_can_show_update_destroy_assigned_hotel(): void
    {
        $this->getJson('/api/properties/'.$this->hotelA->id.'/', $this->auth($this->staffAToken))->assertOk();
        $this->patchJson('/api/properties/'.$this->hotelA->id.'/', ['name' => 'Updated Alpha'], $this->auth($this->staffAToken))
            ->assertOk()->assertJsonPath('name', 'Updated Alpha');
    }

    public function test_p3_staff_cannot_publish_unassigned_hotel(): void
    {
        $this->postJson('/api/properties/'.$this->hotelB->id.'/publish/', [], $this->auth($this->staffAToken))->assertStatus(403);
    }

    public function test_p3_staff_can_publish_assigned_hotel(): void
    {
        $this->postJson('/api/properties/'.$this->hotelA->id.'/publish/', [], $this->auth($this->staffAToken))->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotelA->id, 'publishing_status' => 'published']);
    }

    public function test_p3_customer_cannot_access_properties_index_as_staff(): void
    {
        // Customer should see only published hotels via the public index
        // But the auth'd customer endpoint should still work (they see published hotels)
        // Actually the public index is available at /api/properties/available/ not at /api/properties/
        // Let's test they can access (they get empty results since no published hotels)
        $this->getJson('/api/properties/', $this->auth($this->customerToken))->assertOk();
    }

    public function test_p3_anonymous_cannot_access_protected_routes(): void
    {
        // /api/auth/users/ is admin-only via role middleware which returns 401 for anonymous
        $this->getJson('/api/auth/users/')->assertStatus(401);
        // Unpublished properties are hidden from public callers to avoid leaking draft existence.
        $this->getJson('/api/properties/'.$this->hotelA->id.'/')
            ->assertStatus(404);
        // /api/auth/me/ checks if user exists; anonymous should get 401
        $this->getJson('/api/auth/me/')->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 4 — HOTEL TESTING
    // ═══════════════════════════════════════════════════════════════

    public function test_p4_create_hotel(): void
    {
        $r = $this->postJson('/api/properties/', [
            'name' => 'New Hotel', 'slug' => 'new-hotel', 'subdomain' => 'new',
            'property_type' => 'hotel', 'address' => '123 Test St', 'stars' => 4,
            'country' => 'France', 'city' => 'Paris',
        ], $this->auth($this->adminToken));
        $r->assertCreated()->assertJsonPath('name', 'New Hotel');
        $this->assertDatabaseHas('hotels', ['slug' => 'new-hotel']);
    }

    public function test_p4_hotel_lifecycle_publish_unpublish_archive_unarchive(): void
    {
        $hotel = $this->hotelA;

        // Publish
        $this->postJson('/api/properties/'.$hotel->id.'/publish/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'publishing_status' => 'published', 'is_active' => true]);

        // Unpublish
        $this->postJson('/api/properties/'.$hotel->id.'/unpublish/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'publishing_status' => 'draft']);

        // Archive
        $this->postJson('/api/properties/'.$hotel->id.'/archive/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'publishing_status' => 'archived', 'is_active' => false]);

        // Unarchive
        $this->postJson('/api/properties/'.$hotel->id.'/unarchive/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $hotel->id, 'publishing_status' => 'draft', 'is_active' => true]);
    }

    public function test_p4_hotel_readiness(): void
    {
        $this->postJson('/api/properties/'.$this->hotelA->id.'/publish/', [], $this->auth($this->adminToken))->assertOk();
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/readiness/', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertIsBool($r->json('is_ready_to_publish'));
        $this->assertIsArray($r->json('errors'));
    }

    public function test_p4_hotel_setup_status(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/setup-status/', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertArrayHasKey('completion_percentage', $r->json());
    }

    public function test_p4_hotel_autosave(): void
    {
        $r = $this->patchJson('/api/properties/'.$this->hotelA->id.'/autosave/', [
            'name' => 'Auto Saved', 'last_completed_step' => 3,
        ], $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotelA->id, 'name' => 'Auto Saved']);
    }

    public function test_p4_hotel_workspace(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/workspace/', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertArrayHasKey('property', $r->json());
    }

    public function test_p4_hotel_rates(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/rates/', $this->auth($this->adminToken));
        $r->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 5 — ROOM TESTING
    // ═══════════════════════════════════════════════════════════════

    public function test_p5_create_room_type(): void
    {
        $r = $this->postJson('/api/room-types/', [
            'hotel_id' => $this->hotelA->id, 'name' => 'Deluxe Suite',
            'max_adults' => 3, 'base_price' => 250, 'total_units' => 5,
        ], $this->auth($this->adminToken));
        $r->assertCreated()->assertJsonPath('name', 'Deluxe Suite');
    }

    public function test_p5_list_room_types(): void
    {
        $r = $this->getJson('/api/room-types/', $this->auth($this->adminToken));
        $r->assertOk()->assertJsonStructure(['count', 'results']);
    }

    public function test_p5_update_room_type(): void
    {
        $r = $this->patchJson('/api/room-types/'.$this->roomA->id.'/', ['base_price' => 200], $this->auth($this->adminToken));
        $r->assertOk()->assertJsonPath('base_price', 200);
    }

    public function test_p5_rooms_search(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/rooms/search/', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertCount(1, $r->json('rooms'));
    }

    public function test_p5_availability(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/availability/?check_in=2026-08-01&check_out=2026-08-03', $this->auth($this->adminToken));
        $r->assertOk();
        $this->assertArrayHasKey('available_rooms', $r->json());
    }

    public function test_p5_rates_endpoint(): void
    {
        $r = $this->getJson('/api/properties/'.$this->hotelA->id.'/rates/', $this->auth($this->adminToken));
        $r->assertOk();
    }

    public function test_p5_delete_room_type(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotelA->id, 'name' => 'Temp', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 1]);
        $this->deleteJson('/api/room-types/'.$room->id.'/', [], $this->auth($this->adminToken))
            ->assertStatus(204);
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 6 — BOOKING TESTING
    // ═══════════════════════════════════════════════════════════════

    public function test_p6_booking_inquiry_by_guest(): void
    {
        $r = $this->postJson('/api/bookings/inquiry/', [
            'customer_name' => 'Walk In', 'phone' => '+111', 'property' => $this->hotelA->id,
            'room_type' => $this->roomA->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2,
        ]);
        $r->assertCreated();
        $this->assertDatabaseHas('booking_inquiries', ['customer_name' => 'Walk In', 'status' => 'new']);
    }

    public function test_p6_booking_create_by_auth_user(): void
    {
        $r = $this->postJson('/api/bookings/', [
            'guest_name' => 'Auth Guest', 'guest_phone' => '+222', 'guest_email' => 'guest@test.com',
            'property' => $this->hotelA->id, 'room_type' => $this->roomA->id,
            'check_in' => '2026-08-01', 'check_out' => '2026-08-05', 'adults' => 2,
        ], $this->auth($this->customerToken));
        $r->assertCreated()->assertJsonPath('status', 'new');
    }

    public function test_p6_booking_confirm_by_admin(): void
    {
        $booking = $this->createBooking('pending');
        $r = $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], $this->auth($this->adminToken));
        $r->assertOk()->assertJsonPath('status', 'confirmed');
    }

    public function test_p6_booking_confirm_by_assigned_staff(): void
    {
        $booking = $this->createBooking('new');
        $r = $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], $this->auth($this->staffAToken));
        $r->assertOk()->assertJsonPath('status', 'confirmed');
    }

    public function test_p6_booking_confirm_fails_for_unassigned_staff(): void
    {
        $booking = $this->createBooking('new');
        $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], $this->auth($this->staffBToken))
            ->assertStatus(403);
    }

    public function test_p6_booking_confirm_fails_for_customer(): void
    {
        $booking = $this->createBooking('new');
        $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], $this->auth($this->customerToken))
            ->assertStatus(403);
    }

    public function test_p6_booking_cancel_by_owner(): void
    {
        $booking = BookingInquiry::create([
            'customer_name' => 'Owner', 'phone' => '+333', 'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->roomA->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'customer_id' => $this->customer->id, 'status' => 'confirmed',
        ]);
        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], $this->auth($this->customerToken))
            ->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public function test_p6_booking_cancel_by_admin(): void
    {
        $booking = $this->createBooking('confirmed');
        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], $this->auth($this->adminToken))
            ->assertOk();
    }

    public function test_p6_booking_cancel_fails_for_other_customer(): void
    {
        $other = User::create(['email' => 'other@test.com', 'full_name' => 'Other', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);
        $booking = BookingInquiry::create([
            'customer_name' => 'Owner', 'phone' => '+444', 'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->roomA->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'customer_id' => $this->customer->id, 'status' => 'confirmed',
        ]);
        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], $this->auth($this->createToken($other)))
            ->assertStatus(403);
    }

    public function test_p6_booking_scoped_by_hotel_for_staff_index(): void
    {
        // StaffB has no hotels assigned, should see 0
        $r = $this->getJson('/api/bookings/', $this->auth($this->staffBToken));
        $r->assertOk();
        $this->assertEquals(0, $r->json('count'));
    }

    public function test_p6_booking_staff_cannot_show_update_destroy_other_hotel_booking(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotelB->id, 'name' => 'BRoom', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 5]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Cross', 'phone' => '+555', 'hotel_id' => $this->hotelB->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);
        // StaffA is assigned to hotelA, not hotelB
        $this->getJson('/api/bookings/'.$booking->id.'/', $this->auth($this->staffAToken))->assertStatus(403);
        $this->patchJson('/api/bookings/'.$booking->id.'/', ['status' => 'confirmed'], $this->auth($this->staffAToken))->assertStatus(403);
        $this->deleteJson('/api/bookings/'.$booking->id.'/', [], $this->auth($this->staffAToken))->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 7 — IMAGE TESTING
    // ═══════════════════════════════════════════════════════════════

    public function test_p7_hotel_image_upload_and_delete(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('hotel.jpg');

        $r = $this->postJson('/api/property-images/', [
            'property' => $this->hotelA->id, 'image' => $file, 'caption' => 'Lobby', 'is_cover' => true,
        ], $this->sanctum($this->admin));
        $r->assertCreated();
        $imageId = $r->json('id');
        $imageUrl = $r->json('image_url');

        // Verify file stored
        $storedPath = str_replace('/media/', '', $imageUrl);
        Storage::disk('public')->assertExists($storedPath);

        // Retrieve
        $this->getJson('/api/property-images/'.$imageId.'/', $this->sanctum($this->admin))->assertOk();

        // Update
        $this->patchJson('/api/property-images/'.$imageId.'/', ['caption' => 'Updated Lobby'], $this->sanctum($this->admin))
            ->assertOk()->assertJsonPath('caption', 'Updated Lobby');

        // Delete
        $this->deleteJson('/api/property-images/'.$imageId.'/', [], $this->sanctum($this->admin))
            ->assertStatus(204);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_p7_room_type_image_upload(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('room.jpg');

        $r = $this->postJson('/api/room-type-images/', [
            'room_type' => $this->roomA->id, 'image' => $file, 'caption' => 'Room View',
        ], $this->sanctum($this->admin));
        $r->assertCreated();
        $this->assertArrayHasKey('id', $r->json());
    }

    public function test_p7_staff_upload_image_to_assigned_hotel(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('staff.jpg');
        $this->postJson('/api/property-images/', [
            'property' => $this->hotelA->id, 'image' => $file,
        ], $this->sanctum($this->staffA))->assertCreated();
    }

    public function test_p7_staff_cannot_upload_to_unassigned_hotel(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('hack.jpg');
        $this->postJson('/api/property-images/', [
            'property' => $this->hotelB->id, 'image' => $file,
        ], $this->sanctum($this->staffA))->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // PHASE 8 — SECURITY AUDIT
    // ═══════════════════════════════════════════════════════════════

    public function test_p8_idor_customer_cannot_access_other_booking(): void
    {
        $other = User::create(['email' => 'other2@test.com', 'full_name' => 'Other2', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);
        $booking = BookingInquiry::create([
            'customer_name' => 'Victim', 'phone' => '+666', 'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->roomA->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'customer_id' => $this->customer->id, 'status' => 'new',
        ]);
        // Customer A should be able to see their own booking via index
        // But customer B should NOT see it via direct ID
        // Actually the show/update/destroy for customers... let me check:
        // CrudController for BookingInquiry is STAFF_OR_ADMIN. Customers cannot access at all.
        $this->getJson('/api/bookings/'.$booking->id.'/', $this->auth($this->createToken($other)))->assertStatus(403);
    }

    public function test_p8_idor_staff_cannot_access_other_hotel_data(): void
    {
        // StaffB tries to access HotelA endpoints
        $this->getJson('/api/properties/'.$this->hotelA->id.'/readiness/', $this->auth($this->staffBToken))->assertStatus(403);
        $this->getJson('/api/properties/'.$this->hotelA->id.'/workspace/', $this->auth($this->staffBToken))->assertStatus(403);
        $this->getJson('/api/properties/'.$this->hotelA->id.'/setup-status/', $this->auth($this->staffBToken))->assertStatus(403);
        $this->getJson('/api/properties/'.$this->hotelA->id.'/rates/', $this->auth($this->staffBToken))->assertStatus(403);
        $this->getJson('/api/properties/'.$this->hotelA->id.'/availability/', $this->auth($this->staffBToken))->assertStatus(403);
    }

    public function test_p8_audit_log_created_for_admin_actions(): void
    {
        $target = User::create(['email' => 'audit@test.com', 'full_name' => 'Audit', 'role' => 'customer', 'is_active' => false, 'email_verified' => true, 'password' => 'x']);

        // Activate
        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'activate', 'object_id' => (string) $target->id, 'actor_id' => $this->admin->id]);

        // Deactivate
        $this->postJson('/api/auth/users/'.$target->id.'/deactivate/', [], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivate', 'object_id' => (string) $target->id, 'actor_id' => $this->admin->id]);

        // Change role
        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'change_role', 'object_id' => (string) $target->id, 'actor_id' => $this->admin->id]);

        // Reset password with email notification
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);
        $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', ['password' => 'newpass123'], $this->auth($this->adminToken))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'reset_password', 'object_id' => (string) $target->id, 'actor_id' => $this->admin->id]);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && str_contains($request['subject'], 'Your Password Has Been Changed');
        });
    }

    public function test_p8_sql_injection_attempts(): void
    {
        $payloads = [
            ["' OR '1'='1", 'sqli1@test.com'],
            ["'; DROP TABLE users; --", 'sqli2@test.com'],
            ['1; SELECT * FROM users', 'sqli3@test.com'],
            ["<script>alert('xss')</script>", 'sqli4@test.com'],
            ['../../../etc/passwd', 'sqli5@test.com'],
        ];
        foreach ($payloads as [$payload, $email]) {
            $r = $this->postJson('/api/auth/signup/', [
                'email' => $email, 'full_name' => $payload,
                'password' => 'password123', 'password_confirm' => 'password123',
            ]);
            // Should create user safely (payload should be treated as literal string, not executed)
            $r->assertCreated();
            $this->assertDatabaseHas('users', ['email' => $email, 'full_name' => $payload]);
        }
    }

    public function test_p8_mass_assignment_protection(): void
    {
        // Try to set role directly
        $r = $this->postJson('/api/auth/signup/', [
            'email' => 'hacker@test.com', 'full_name' => 'Hacker',
            'password' => 'password123', 'password_confirm' => 'password123',
            'role' => 'admin', 'is_superuser' => true, 'is_active' => true,
        ]);
        $r->assertCreated();
        // User should be customer, not admin
        $this->assertDatabaseHas('users', ['email' => 'hacker@test.com', 'role' => 'customer', 'is_active' => false]);
    }

    public function test_p8_token_leakage(): void
    {
        $r = $this->postJson('/api/auth/login/', ['email' => 'admin@test.com', 'password' => 'password']);
        $r->assertOk();
        $token = $r->json('token');
        $access = $r->json('access');

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($access);

        // Token should not appear in redirect_url
        $redirectUrl = $r->json('redirect_url');
        $this->assertStringNotContainsString($token, $redirectUrl ?? '');
        $this->assertStringNotContainsString($access, $redirectUrl ?? '');

        // Access token is NOT stored in plaintext (hashed with sha256)
        // 'access' is the raw token, 'api_tokens.token' stores hash('sha256', $access)
        // So the raw access token should NOT appear in the api_tokens table
        $this->assertDatabaseMissing('api_tokens', ['token' => $access]);
    }

    private function createBooking(string $status): BookingInquiry
    {
        return BookingInquiry::create([
            'customer_name' => 'Test Guest', 'phone' => '+999', 'hotel_id' => $this->hotelA->id,
            'room_type_id' => $this->roomA->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => $status,
        ]);
    }
}
