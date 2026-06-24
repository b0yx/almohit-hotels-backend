<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BookingInquiry;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $customer;
    private User $otherStaff;
    private Hotel $hotel;
    private Hotel $otherHotel;
    private string $adminToken;
    private string $staffToken;
    private string $customerToken;
    private string $otherStaffToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'email' => 'admin@test.com', 'full_name' => 'Admin', 'role' => 'admin',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->staff = User::create([
            'email' => 'staff@test.com', 'full_name' => 'Staff', 'role' => 'staff',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->otherStaff = User::create([
            'email' => 'other-staff@test.com', 'full_name' => 'Other Staff', 'role' => 'staff',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->customer = User::create([
            'email' => 'customer@test.com', 'full_name' => 'Customer', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->hotel = Hotel::create([
            'name' => 'Staff Hotel', 'slug' => 'staff-hotel', 'publishing_status' => 'draft', 'is_active' => false,
        ]);
        $this->otherHotel = Hotel::create([
            'name' => 'Other Hotel', 'slug' => 'other-hotel', 'publishing_status' => 'draft', 'is_active' => false,
        ]);

        $this->hotel->assignedStaff()->attach($this->staff->id);

        $this->adminToken = $this->createToken($this->admin);
        $this->staffToken = $this->createToken($this->staff);
        $this->customerToken = $this->createToken($this->customer);
        $this->otherStaffToken = $this->createToken($this->otherStaff);
    }

    private function createToken(User $user): string
    {
        $plain = \Illuminate\Support\Str::random(64);
        \App\Models\ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);
        return $plain;
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    private function sanctumHeader(User $user): array
    {
        $token = $user->createToken('test_token')->plainTextToken;
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ─── IMAGE UPLOAD TESTS ──────────────────────────────────

    public function test_admin_can_upload_hotel_image(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('hotel.jpg');

        $response = $this->postJson('/api/property-images/', [
            'property' => $this->hotel->id,
            'image' => $file,
            'caption' => 'Lobby',
        ], $this->sanctumHeader($this->admin));

        $response->assertCreated();
        $response->assertJsonPath('caption', 'Lobby');
    }

    public function test_staff_can_upload_image_to_assigned_hotel(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('room.jpg');

        $response = $this->postJson('/api/property-images/', [
            'property' => $this->hotel->id,
            'image' => $file,
        ], $this->sanctumHeader($this->staff));

        $response->assertCreated();
    }

    public function test_staff_cannot_upload_image_to_unassigned_hotel(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('room.jpg');

        $response = $this->postJson('/api/property-images/', [
            'property' => $this->otherHotel->id,
            'image' => $file,
        ], $this->sanctumHeader($this->staff));

        $response->assertStatus(403);
    }

    // ─── HOTEL STATUS ENDPOINTS ──────────────────────────────────

    public function test_staff_can_publish_assigned_hotel(): void
    {
        $response = $this->postJson('/api/properties/'.$this->hotel->id.'/publish/', [], $this->authHeader($this->staffToken));
        $response->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotel->id, 'publishing_status' => 'published', 'is_active' => true]);
    }

    public function test_staff_cannot_publish_unassigned_hotel(): void
    {
        $response = $this->postJson('/api/properties/'.$this->otherHotel->id.'/publish/', [], $this->authHeader($this->staffToken));
        $response->assertStatus(403);
    }

    public function test_staff_can_unpublish_assigned_hotel(): void
    {
        $this->hotel->forceFill(['publishing_status' => 'published', 'is_active' => true])->save();

        $response = $this->postJson('/api/properties/'.$this->hotel->id.'/unpublish/', [], $this->authHeader($this->staffToken));
        $response->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotel->id, 'publishing_status' => 'draft']);
    }

    public function test_staff_can_archive_and_unarchive_assigned_hotel(): void
    {
        $this->postJson('/api/properties/'.$this->hotel->id.'/archive/', [], $this->authHeader($this->staffToken))
            ->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotel->id, 'publishing_status' => 'archived', 'is_active' => false]);

        $this->postJson('/api/properties/'.$this->hotel->id.'/unarchive/', [], $this->authHeader($this->staffToken))
            ->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->hotel->id, 'publishing_status' => 'draft', 'is_active' => true]);
    }

    public function test_admin_can_publish_any_hotel(): void
    {
        $response = $this->postJson('/api/properties/'.$this->otherHotel->id.'/publish/', [], $this->authHeader($this->adminToken));
        $response->assertOk();
        $this->assertDatabaseHas('hotels', ['id' => $this->otherHotel->id, 'publishing_status' => 'published']);
    }

    // ─── ADMIN PASSWORD RESET WORKFLOW ──────────────────────────

    public function test_admin_reset_user_password_creates_audit_log_and_sends_email(): void
    {
        Mail::fake();

        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'oldpass',
        ]);

        $response = $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', [
            'password' => 'newpassword123',
        ], $this->authHeader($this->adminToken));

        $response->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reset_password',
            'object_id' => (string) $target->id,
            'actor_id' => $this->admin->id,
        ]);
        Mail::assertSent(\App\Mail\PasswordChangedByAdminMail::class, function ($mail) use ($target) {
            return $mail->hasTo($target->email) && $mail->user->id === $target->id;
        });
    }

    public function test_staff_cannot_reset_user_password(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', [
            'password' => 'newpassword123',
        ], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    // ─── AUDIT LOG CREATION ──────────────────────────────────

    public function test_activate_user_creates_audit_log(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => false, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->authHeader($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activate',
            'object_id' => (string) $target->id,
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_deactivate_user_creates_audit_log(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/deactivate/', [], $this->authHeader($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deactivate',
            'object_id' => (string) $target->id,
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_change_user_role_creates_audit_log(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->authHeader($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'change_role',
            'object_id' => (string) $target->id,
            'actor_id' => $this->admin->id,
        ]);
    }

    // ─── STAFF AUTHORIZATION BOUNDARIES ──────────────────────────

    public function test_staff_cannot_show_unassigned_hotel(): void
    {
        $this->getJson('/api/properties/'.$this->otherHotel->id.'/', $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_update_unassigned_hotel(): void
    {
        $this->patchJson('/api/properties/'.$this->otherHotel->id.'/', ['name' => 'Hacked'], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_destroy_unassigned_hotel(): void
    {
        $this->deleteJson('/api/properties/'.$this->otherHotel->id.'/', [], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_can_show_assigned_hotel(): void
    {
        $this->getJson('/api/properties/'.$this->hotel->id.'/', $this->authHeader($this->staffToken))
            ->assertOk();
    }

    public function test_staff_can_update_assigned_hotel(): void
    {
        $this->patchJson('/api/properties/'.$this->hotel->id.'/', ['name' => 'Updated'], $this->authHeader($this->staffToken))
            ->assertOk()
            ->assertJsonPath('name', 'Updated');
    }

    public function test_staff_cannot_access_booking_of_other_hotel(): void
    {
        $room = RoomType::create([
            'hotel_id' => $this->otherHotel->id, 'name' => 'Room',
            'max_adults' => 2, 'base_price' => 100, 'total_units' => 3,
        ]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->otherHotel->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->getJson('/api/bookings/'.$booking->id.'/', $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_update_booking_of_other_hotel(): void
    {
        $room = RoomType::create([
            'hotel_id' => $this->otherHotel->id, 'name' => 'Room',
            'max_adults' => 2, 'base_price' => 100, 'total_units' => 3,
        ]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->otherHotel->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->patchJson('/api/bookings/'.$booking->id.'/', ['status' => 'confirmed'], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_destroy_booking_of_other_hotel(): void
    {
        $room = RoomType::create([
            'hotel_id' => $this->otherHotel->id, 'name' => 'Room',
            'max_adults' => 2, 'base_price' => 100, 'total_units' => 3,
        ]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->otherHotel->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->deleteJson('/api/bookings/'.$booking->id.'/', [], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_can_access_booking_of_own_hotel(): void
    {
        $room = RoomType::create([
            'hotel_id' => $this->hotel->id, 'name' => 'Room',
            'max_adults' => 2, 'base_price' => 100, 'total_units' => 3,
        ]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->hotel->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->getJson('/api/bookings/'.$booking->id.'/', $this->authHeader($this->staffToken))
            ->assertOk();
    }

    public function test_admin_can_access_any_booking(): void
    {
        $room = RoomType::create([
            'hotel_id' => $this->otherHotel->id, 'name' => 'Room',
            'max_adults' => 2, 'base_price' => 100, 'total_units' => 3,
        ]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->otherHotel->id,
            'room_type_id' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->getJson('/api/bookings/'.$booking->id.'/', $this->authHeader($this->adminToken))
            ->assertOk();
    }
}
