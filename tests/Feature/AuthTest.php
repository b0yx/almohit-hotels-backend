<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(null, 201)]);
    }

    public function test_signup_creates_user_and_returns_debug_code_in_local(): void
    {
        $response = $this->postJson('/api/auth/signup/', [
            'email' => 'newuser@example.com',
            'full_name' => 'New User',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('detail', 'Account created. Please check your email for the verification code.')
            ->assertJsonStructure(['debug_code']);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com', 'role' => 'customer', 'is_active' => false]);
    }

    public function test_signup_validates_password_confirmation(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'newuser@example.com',
            'full_name' => 'New User',
            'password' => 'password123',
            'password_confirm' => 'different',
        ])->assertStatus(422);
    }

    public function test_verify_otp_with_valid_code_activates_user(): void
    {
        $signup = $this->postJson('/api/auth/signup/', [
            'email' => 'newuser@example.com',
            'full_name' => 'New User',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);
        $code = $signup->json('debug_code');

        $this->assertNotNull($code, 'debug_code should be returned in testing environment');

        $this->postJson('/api/auth/verify-otp/', [
            'email' => 'newuser@example.com',
            'code' => $code,
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com', 'is_active' => true, 'email_verified' => true]);
    }

    public function test_verify_otp_rejects_invalid_code(): void
    {
        $this->postJson('/api/auth/signup/', [
            'email' => 'newuser@example.com',
            'full_name' => 'New User',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ]);

        $this->postJson('/api/auth/verify-otp/', [
            'email' => 'newuser@example.com',
            'code' => '000000',
        ])->assertStatus(400)->assertJsonPath('code', 'invalid_otp');
    }

    public function test_complete_signup_and_login_flow(): void
    {
        // Signup
        $signup = $this->postJson('/api/auth/signup/', [
            'email' => 'customer@test.com',
            'full_name' => 'Test Customer',
            'password' => 'securepass123',
            'password_confirm' => 'securepass123',
        ]);
        $signup->assertStatus(201);
        $code = $signup->json('debug_code');

        // Verify OTP
        $this->postJson('/api/auth/verify-otp/', [
            'email' => 'customer@test.com',
            'code' => $code,
        ])->assertOk();

        // Login
        $login = $this->postJson('/api/auth/login/', [
            'email' => 'customer@test.com',
            'password' => 'securepass123',
        ]);
        $login->assertOk()
            ->assertJsonStructure(['token', 'access', 'token_type', 'role', 'user']);
        $this->assertEquals('customer', $login->json('role'));
    }

    public function test_admin_login_fails_for_customer(): void
    {
        User::create([
            'email' => 'customer@test.com',
            'full_name' => 'Customer',
            'role' => 'customer',
            'password' => 'password123',
            'is_active' => true,
            'email_verified' => true,
        ]);

        $this->postJson('/api/auth/admin/login/', [
            'email' => 'customer@test.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_admin_login_succeeds_for_admin(): void
    {
        User::create([
            'email' => 'admin@test.com',
            'full_name' => 'Admin',
            'role' => 'admin',
            'password' => 'password123',
            'is_active' => true,
            'email_verified' => true,
        ]);

        $this->postJson('/api/auth/admin/login/', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('role', 'admin');
    }
}
