<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BookingInquiry;
use App\Models\Hotel;
use App\Models\PasswordResetOtp;
use App\Models\RoomType;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private string $adminToken;

    private string $customerToken;

    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);

        $this->admin = User::create([
            'email' => 'admin@test.com', 'full_name' => 'Admin', 'role' => 'admin',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->customer = User::create([
            'email' => 'customer@test.com', 'full_name' => 'Customer', 'role' => 'customer',
            'is_staff' => false, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->adminToken = $this->admin->createToken('test')->plainTextToken;
        $this->customerToken = $this->customer->createToken('test')->plainTextToken;

        $this->hotel = Hotel::create([
            'name' => 'Test Hotel', 'slug' => 'test-hotel', 'subdomain' => 'test',
            'country' => 'US', 'city' => 'NYC', 'address' => '123 St', 'property_type' => 'hotel',
            'stars' => 4, 'publishing_status' => 'draft', 'is_active' => true,
        ]);
    }

    private function auth(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    public function test_audit_on_user_signup(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'newuser@test.com', 'full_name' => 'New User',
            'password' => 'password123', 'password_confirm' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'registered', 'content_type' => 'user', 'object_repr' => 'New User',
        ]);
    }

    public function test_audit_on_profile_update(): void
    {
        $this->patchJson('/api/auth/me/', ['full_name' => 'Updated Name'], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'profile_updated', 'content_type' => 'user', 'actor_id' => $this->admin->id,
        ]);
    }

    public function test_audit_on_logout(): void
    {
        $this->postJson('/api/auth/logout/', [], $this->auth($this->adminToken))
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout', 'content_type' => 'session', 'actor_id' => $this->admin->id,
        ]);
    }

    public function test_audit_on_user_activate(): void
    {
        $target = User::create(['email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer', 'is_active' => false, 'email_verified' => true, 'password' => 'x']);

        $this->postJson("/api/auth/users/{$target->id}/activate/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activate', 'content_type' => 'user', 'object_id' => (string) $target->id,
        ]);
    }

    public function test_audit_on_user_deactivate(): void
    {
        $target = User::create(['email' => 'target2@test.com', 'full_name' => 'Target', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);

        $this->postJson("/api/auth/users/{$target->id}/deactivate/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deactivate', 'content_type' => 'user', 'object_id' => (string) $target->id,
        ]);
    }

    public function test_audit_on_user_change_role(): void
    {
        $target = User::create(['email' => 'target3@test.com', 'full_name' => 'Target', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);

        $this->postJson("/api/auth/users/{$target->id}/change-role/",
            ['role' => 'staff'], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'change_role', 'content_type' => 'user', 'object_id' => (string) $target->id,
        ]);
    }

    public function test_audit_on_user_reset_password(): void
    {
        $target = User::create(['email' => 'target4@test.com', 'full_name' => 'Target', 'role' => 'customer', 'is_active' => true, 'email_verified' => true, 'password' => 'x']);

        $this->postJson("/api/auth/users/{$target->id}/reset-password/",
            ['password' => 'newpassword123'], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reset_password', 'content_type' => 'user', 'object_id' => (string) $target->id,
        ]);
    }

    public function test_audit_on_hotel_create(): void
    {
        $this->postJson('/api/properties/', [
            'name' => 'New Hotel', 'slug' => 'new-hotel', 'subdomain' => 'new',
            'country' => 'France', 'city' => 'Paris', 'address' => '1 Rue', 'property_type' => 'hotel', 'stars' => 4,
        ], $this->auth($this->adminToken))->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'hotel', 'object_repr' => 'New Hotel',
        ]);
    }

    public function test_audit_on_hotel_update(): void
    {
        $this->patchJson("/api/properties/{$this->hotel->id}/",
            ['name' => 'Updated Hotel'], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated', 'content_type' => 'hotel', 'object_id' => (string) $this->hotel->id,
        ]);
    }

    public function test_audit_on_hotel_delete(): void
    {
        $this->deleteJson("/api/properties/{$this->hotel->id}/", [], $this->auth($this->adminToken))
            ->assertStatus(204);

        $logs = AuditLog::where('action', 'deleted')
            ->where('content_type', 'hotel')
            ->where('object_id', (string) $this->hotel->id)
            ->get();
        $this->assertCount(1, $logs, 'Hotel delete must produce exactly one audit entry (no duplicates)');
    }

    public function test_audit_on_hotel_publish(): void
    {
        $this->postJson("/api/properties/{$this->hotel->id}/publish/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'published', 'content_type' => 'hotel', 'object_id' => (string) $this->hotel->id,
        ]);
    }

    public function test_audit_on_hotel_unpublish(): void
    {
        $this->hotel->forceFill(['publishing_status' => 'published', 'is_active' => true])->save();

        $this->postJson("/api/properties/{$this->hotel->id}/unpublish/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'unpublished', 'content_type' => 'hotel', 'object_id' => (string) $this->hotel->id,
        ]);
    }

    public function test_audit_on_hotel_archive(): void
    {
        $this->postJson("/api/properties/{$this->hotel->id}/archive/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'archived', 'content_type' => 'hotel', 'object_id' => (string) $this->hotel->id,
        ]);
    }

    public function test_audit_on_hotel_unarchive(): void
    {
        $this->hotel->forceFill(['publishing_status' => 'archived', 'is_active' => false])->save();

        $this->postJson("/api/properties/{$this->hotel->id}/unarchive/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'unarchived', 'content_type' => 'hotel', 'object_id' => (string) $this->hotel->id,
        ]);
    }

    public function test_audit_on_booking_create(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Standard', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);

        $this->postJson('/api/bookings/', [
            'guest_name' => 'Guest', 'guest_phone' => '+123', 'property' => $this->hotel->id,
            'room_type' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2,
        ], $this->auth($this->adminToken))->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'booking',
        ]);
    }

    public function test_audit_on_booking_inquiry(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Standard', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);

        $this->postJson('/api/bookings/inquiry/', [
            'customer_name' => 'Inquirer', 'phone' => '+123', 'property' => $this->hotel->id,
            'room_type' => $room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2,
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inquiry_created', 'content_type' => 'booking',
        ]);
    }

    public function test_audit_on_booking_confirm(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Std', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Test', 'phone' => '+123', 'hotel_id' => $this->hotel->id, 'room_type_id' => $room->id,
            'check_in' => '2026-08-01', 'check_out' => '2026-08-03', 'adults' => 2,
            'status' => 'new', 'estimated_total' => 200,
        ]);

        $this->postJson('/api/bookings/confirm/',
            ['booking_id' => $booking->id], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'confirmed', 'content_type' => 'booking', 'object_id' => (string) $booking->id,
        ]);
    }

    public function test_audit_on_booking_cancel(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Std', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);
        $booking = BookingInquiry::create([
            'customer_name' => 'Test', 'phone' => '+123', 'hotel_id' => $this->hotel->id, 'room_type_id' => $room->id,
            'check_in' => '2026-08-01', 'check_out' => '2026-08-03', 'adults' => 2,
            'status' => 'confirmed', 'estimated_total' => 200,
        ]);

        $this->postJson("/api/bookings/{$booking->id}/cancel/", [], $this->auth($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cancelled', 'content_type' => 'booking', 'object_id' => (string) $booking->id,
        ]);
    }

    public function test_audit_on_review_create(): void
    {
        $this->postJson("/api/properties/{$this->hotel->id}/reviews/", [
            'guest_name' => 'Reviewer', 'rating' => 4, 'comment' => 'Great!',
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'review',
        ]);
    }

    public function test_audit_on_room_type_crud(): void
    {
        // Create
        $r = $this->postJson('/api/room-types/', [
            'name' => 'Deluxe', 'hotel_id' => $this->hotel->id,
            'max_adults' => 2, 'base_price' => 150, 'total_units' => 5,
        ], $this->auth($this->adminToken))->assertCreated();

        $roomTypeId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'room_type', 'object_id' => (string) $roomTypeId,
        ]);

        // Update
        $this->patchJson("/api/room-types/{$roomTypeId}/",
            ['name' => 'Deluxe Plus'], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated', 'content_type' => 'room_type', 'object_id' => (string) $roomTypeId,
        ]);

        // Delete
        $this->deleteJson("/api/room-types/{$roomTypeId}/", [], $this->auth($this->adminToken))
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted', 'content_type' => 'room_type', 'object_id' => (string) $roomTypeId,
        ]);
    }

    public function test_audit_on_hotel_amenity_crud(): void
    {
        $r = $this->postJson('/api/property-amenities/', [
            'name' => 'WiFi',
        ], $this->auth($this->adminToken))->assertCreated();

        $amenityId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'hotel_amenity', 'object_id' => (string) $amenityId,
        ]);

        $this->deleteJson("/api/property-amenities/{$amenityId}/", [], $this->auth($this->adminToken))
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted', 'content_type' => 'hotel_amenity', 'object_id' => (string) $amenityId,
        ]);
    }

    public function test_audit_on_room_price_crud(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Std', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);

        $r = $this->postJson('/api/room-prices/', [
            'room_type_id' => $room->id, 'season_name' => 'Peak', 'price_per_night' => 200,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
        ], $this->auth($this->adminToken))->assertCreated();

        $priceId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'room_price', 'object_id' => (string) $priceId,
        ]);
    }

    public function test_audit_on_service_category_crud(): void
    {
        $r = $this->postJson('/api/service-categories/', [
            'name' => 'Spa',
        ], $this->auth($this->adminToken))->assertCreated();

        $catId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'service_category', 'object_id' => (string) $catId,
        ]);
    }

    public function test_audit_on_hotel_service_crud(): void
    {
        $cat = ServiceCategory::create(['name' => 'Wellness']);

        $r = $this->postJson('/api/property-services/', [
            'name' => 'Pool', 'hotel_id' => $this->hotel->id, 'service_category_id' => $cat->id,
        ], $this->auth($this->adminToken))->assertCreated();

        $svcId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'hotel_service', 'object_id' => (string) $svcId,
        ]);
    }

    public function test_audit_on_availability_block_crud(): void
    {
        $room = RoomType::create(['hotel_id' => $this->hotel->id, 'name' => 'Std', 'max_adults' => 2, 'base_price' => 100, 'total_units' => 3]);

        $r = $this->postJson('/api/availability-blocks/', [
            'room_type_id' => $room->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-05', 'blocked_units' => 2,
        ], $this->auth($this->adminToken))->assertCreated();

        $blockId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'availability_block', 'object_id' => (string) $blockId,
        ]);
    }

    public function test_audit_on_contact_message_crud(): void
    {
        $r = $this->postJson('/api/contact-messages/', [
            'full_name' => 'Contact', 'email' => 'contact@test.com',
            'subject' => 'Hello', 'message' => 'Test message', 'hotel_id' => $this->hotel->id,
        ], $this->auth($this->adminToken))->assertCreated();

        $msgId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'contact_message', 'object_id' => (string) $msgId,
        ]);
    }

    public function test_audit_on_image_upload(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('hotel.jpg');

        $r = $this->postJson('/api/property-images/', [
            'property' => $this->hotel->id, 'image' => $file,
        ], $this->auth($this->adminToken))->assertCreated();

        $imgId = $r->json('id');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'content_type' => 'hotel_image', 'object_id' => (string) $imgId,
        ]);

        // Update
        $this->patchJson("/api/property-images/{$imgId}/",
            ['caption' => 'Updated caption'], $this->auth($this->adminToken)
        )->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated', 'content_type' => 'hotel_image', 'object_id' => (string) $imgId,
        ]);

        // Delete
        $this->deleteJson("/api/property-images/{$imgId}/", [], $this->auth($this->adminToken))
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted', 'content_type' => 'hotel_image', 'object_id' => (string) $imgId,
        ]);
    }

    public function test_audit_on_forgot_password(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => $this->customer->email])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_requested', 'content_type' => 'user',
            'object_id' => (string) $this->customer->id,
        ]);
    }

    public function test_audit_on_reset_password(): void
    {
        // Create OTP
        $code = '123456';
        PasswordResetOtp::create([
            'email' => $this->customer->email,
            'otp_hash' => bcrypt($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/auth/reset-password/', [
            'email' => $this->customer->email,
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset', 'content_type' => 'user',
            'object_id' => (string) $this->customer->id,
        ]);
    }

    public function test_no_duplicate_audit_logs(): void
    {
        $target = User::create(['email' => 'dup@test.com', 'full_name' => 'Dup', 'role' => 'customer', 'is_active' => false, 'email_verified' => true, 'password' => 'x']);

        $this->postJson("/api/auth/users/{$target->id}/activate/", [], $this->auth($this->adminToken))
            ->assertOk();

        $logs = AuditLog::where('action', 'activate')->where('object_id', (string) $target->id)->get();
        $this->assertCount(1, $logs, 'Duplicate audit log detected');
    }
}
