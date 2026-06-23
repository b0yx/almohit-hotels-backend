<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $customer;
    private string $adminToken;
    private string $staffToken;
    private string $customerToken;

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
        $this->customer = User::create([
            'email' => 'customer@test.com', 'full_name' => 'Customer', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->adminToken = $this->createToken($this->admin);
        $this->staffToken = $this->createToken($this->staff);
        $this->customerToken = $this->createToken($this->customer);
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

    public function test_admin_can_access_users_index(): void
    {
        $this->getJson('/api/auth/users/', $this->authHeader($this->adminToken))
            ->assertOk();
    }

    public function test_staff_cannot_access_users_index(): void
    {
        $this->getJson('/api/auth/users/', $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_users_index(): void
    {
        $this->getJson('/api/auth/users/', $this->authHeader($this->customerToken))
            ->assertStatus(403);
    }

    public function test_anonymous_cannot_access_users_index(): void
    {
        $this->getJson('/api/auth/users/')->assertStatus(401);
    }

    public function test_admin_can_activate_user(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => false, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->authHeader($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    public function test_staff_cannot_activate_user(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => false, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/activate/', [], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_admin_can_change_user_role(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->authHeader($this->adminToken))
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'staff']);
    }

    public function test_staff_cannot_change_user_role(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/change-role/', ['role' => 'staff'], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_reset_user_password(): void
    {
        $target = User::create([
            'email' => 'target@test.com', 'full_name' => 'Target', 'role' => 'customer',
            'is_active' => true, 'email_verified' => true, 'password' => 'password',
        ]);

        $this->postJson('/api/auth/users/'.$target->id.'/reset-password/', ['password' => 'newpassword123'], $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_audit_logs(): void
    {
        $this->getJson('/api/audit-logs/', $this->authHeader($this->customerToken))
            ->assertStatus(403);
    }

    public function test_staff_cannot_access_audit_logs(): void
    {
        $this->getJson('/api/audit-logs/', $this->authHeader($this->staffToken))
            ->assertStatus(403);
    }

    public function test_sqlite_in_memory_database(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
    }
}
