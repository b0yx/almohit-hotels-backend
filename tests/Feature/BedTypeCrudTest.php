<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BedTypeCrudTest extends TestCase
{
    use RefreshDatabase;

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
            'email' => 'admin_bed_types@example.com',
            'full_name' => 'Admin User',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    private function createCustomer(): User
    {
        return User::query()->create([
            'email' => 'customer_bed_types@example.com',
            'full_name' => 'Customer User',
            'role' => User::ROLE_CUSTOMER,
            'is_staff' => false,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    public function test_seeded_bed_types_are_available_in_display_order(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson('/api/bed-types/', $this->headersFor($admin));

        $response->assertOk()
            ->assertJsonPath('count', 7)
            ->assertJsonPath('results.0.name', 'Single Bed')
            ->assertJsonPath('results.0.is_active', true);
    }

    public function test_admin_can_create_update_and_delete_bed_types(): void
    {
        $admin = $this->createAdmin();
        $headers = $this->headersFor($admin);

        $create = $this->postJson('/api/bed-types/', [
            'name' => 'California King',
            'name_ar' => 'California King AR',
            'display_order' => 20,
            'is_active' => true,
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('name', 'California King')
            ->assertJsonPath('display_order', 20);

        $bedTypeId = $create->json('id');

        $this->patchJson("/api/bed-types/$bedTypeId/", [
            'name' => 'California King Bed',
            'is_active' => false,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('name', 'California King Bed')
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('bed_types', [
            'id' => $bedTypeId,
            'name' => 'California King Bed',
            'is_active' => false,
        ]);

        $this->deleteJson("/api/bed-types/$bedTypeId/", [], $headers)
            ->assertStatus(204);

        $this->assertDatabaseMissing('bed_types', ['id' => $bedTypeId]);
    }

    public function test_customer_cannot_manage_bed_types(): void
    {
        $customer = $this->createCustomer();
        $headers = $this->headersFor($customer);

        $this->getJson('/api/bed-types/', $headers)
            ->assertStatus(403);

        $this->postJson('/api/bed-types/', ['name' => 'Guest Bed'], $headers)
            ->assertStatus(403);
    }
}
