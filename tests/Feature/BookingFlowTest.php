<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BookingInquiry;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private RoomType $room;

    private User $customer;

    private User $staff;

    private User $admin;

    private string $customerToken;

    private string $staffToken;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create(['name' => 'Test Hotel', 'slug' => 'test-hotel', 'publishing_status' => 'published', 'is_active' => true]);
        $this->room = RoomType::create([
            'hotel_id' => $this->hotel->id, 'name' => 'Standard', 'max_adults' => 2,
            'base_price' => 100, 'total_units' => 5,
        ]);

        $this->customer = User::create([
            'email' => 'cust@test.com', 'full_name' => 'Customer', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->staff = User::create([
            'email' => 'staff@test.com', 'full_name' => 'Staff', 'role' => 'staff',
            'is_staff' => true, 'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $this->admin = User::create([
            'email' => 'admin@test.com', 'full_name' => 'Admin', 'role' => 'admin',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->customerToken = $this->createToken($this->customer);
        $this->staffToken = $this->createToken($this->staff);
        $this->adminToken = $this->createToken($this->admin);
    }

    private function createToken(User $user): string
    {
        $plain = Str::random(64);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return $plain;
    }

    public function test_public_booking_inquiry(): void
    {
        $this->postJson('/api/bookings/inquiry/', [
            'customer_name' => 'Guest', 'phone' => '+123', 'property' => $this->hotel->id,
            'room_type' => $this->room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2,
        ])->assertCreated()->assertJsonPath('estimated_total', '200.00');
    }

    public function test_booking_confirm_requires_staff(): void
    {
        $this->hotel->assignedStaff()->attach($this->staff->id);

        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'status' => 'new',
        ]);

        $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], ['Authorization' => 'Bearer '.$this->customerToken])
            ->assertStatus(403);

        $this->postJson('/api/bookings/confirm/', ['booking_id' => $booking->id], ['Authorization' => 'Bearer '.$this->staffToken])
            ->assertOk()->assertJsonPath('status', 'confirmed');
    }

    public function test_booking_cancel_checks_ownership(): void
    {
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'customer_id' => $this->customer->id, 'status' => 'confirmed',
        ]);

        $otherCustomer = User::create([
            'email' => 'other@test.com', 'full_name' => 'Other', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);
        $otherToken = $this->createToken($otherCustomer);

        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], ['Authorization' => 'Bearer '.$otherToken])
            ->assertStatus(403);

        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], ['Authorization' => 'Bearer '.$this->customerToken])
            ->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public function test_admin_can_cancel_any_booking(): void
    {
        $booking = BookingInquiry::create([
            'customer_name' => 'Guest', 'phone' => '+123', 'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->room->id, 'check_in' => '2026-08-01', 'check_out' => '2026-08-03',
            'adults' => 2, 'customer_id' => $this->customer->id, 'status' => 'confirmed',
        ]);

        $this->postJson('/api/bookings/'.$booking->id.'/cancel/', [], ['Authorization' => 'Bearer '.$this->adminToken])
            ->assertOk();
    }
}
