<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'user@example.com',
            'full_name' => 'Test User',
            'role' => 'customer',
            'is_active' => true,
            'email_verified' => true,
            'password' => 'oldpassword123',
        ]);
    }

    public function test_forgot_password_with_existing_email_returns_generic_response(): void
    {
        $response = $this->postJson('/api/auth/forgot-password/', [
            'email' => 'user@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('detail', 'If the email exists, an OTP has been sent.');
    }

    public function test_forgot_password_with_nonexisting_email_returns_same_response(): void
    {
        $response = $this->postJson('/api/auth/forgot-password/', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('detail', 'If the email exists, an OTP has been sent.');
    }

    public function test_forgot_password_creates_otp_for_existing_email(): void
    {
        $this->postJson('/api/auth/forgot-password/', [
            'email' => 'user@example.com',
        ]);

        $this->assertDatabaseHas('password_reset_otps', [
            'email' => 'user@example.com',
            'used_at' => null,
        ]);

        $otp = PasswordResetOtp::query()->where('email', 'user@example.com')->first();
        $this->assertNotNull($otp);
        $this->assertNotNull($otp->otp_hash);
        $this->assertTrue($otp->expires_at->isFuture());
        $this->assertEquals(0, $otp->attempts);
    }

    public function test_forgot_password_does_not_create_otp_for_nonexisting_email(): void
    {
        $this->postJson('/api/auth/forgot-password/', [
            'email' => 'nonexistent@example.com',
        ]);

        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_forgot_password_invalidates_previous_otps(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);
        $firstOtp = PasswordResetOtp::query()->where('email', 'user@example.com')->first();

        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);

        $firstOtp->refresh();
        $this->assertNotNull($firstOtp->used_at);

        $this->assertEquals(1, PasswordResetOtp::query()->where('email', 'user@example.com')->whereNull('used_at')->count());
    }

    public function test_successful_password_reset(): void
    {
        $response = $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);
        $code = $response->json('debug_code');
        $this->assertNotNull($code);

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => $code,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('detail', 'Password has been reset successfully.');

        $otp = PasswordResetOtp::query()->where('email', 'user@example.com')->first();
        $this->assertNotNull($otp->used_at);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
    }

    public function test_reset_password_with_invalid_otp_returns_error(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('detail', 'Invalid or expired OTP.')
            ->assertJsonPath('code', 'invalid_otp');
    }

    public function test_reset_password_with_expired_otp_returns_error(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);
        $otp = PasswordResetOtp::query()->where('email', 'user@example.com')->first();
        $otp->forceFill(['expires_at' => now()->subMinute()])->save();

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('detail', 'Invalid or expired OTP.');
    }

    public function test_reset_password_with_used_otp_returns_error(): void
    {
        $forgot = $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);
        $code = $forgot->json('debug_code');

        $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => $code,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => $code,
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('detail', 'Invalid or expired OTP.');
    }

    public function test_reset_password_locks_after_five_attempts(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/reset-password/', [
                'email' => 'user@example.com',
                'code' => '000000',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);
        }

        $otp = PasswordResetOtp::query()->where('email', 'user@example.com')->first();
        $this->assertEquals(5, $otp->attempts);

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('code', 'otp_locked');
    }

    public function test_reset_password_validates_password_confirmation(): void
    {
        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '123456',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_with_nonexisting_email_returns_generic_error(): void
    {
        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'nonexistent@example.com',
            'code' => '123456',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('detail', 'Invalid or expired OTP.');
    }

    public function test_reset_password_validates_minimum_length(): void
    {
        $response = $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '123456',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_rate_limiting_on_forgot_password(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com'])
                ->assertOk();
        }

        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com'])
            ->assertStatus(429);
    }

    public function test_rate_limiting_on_reset_password(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/reset-password/', [
                'email' => 'user@example.com',
                'code' => '000000',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);
        }

        $this->postJson('/api/auth/reset-password/', [
            'email' => 'user@example.com',
            'code' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(429);
    }

    public function test_email_is_sent_when_forgot_password_is_called(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/forgot-password/', ['email' => 'user@example.com']);

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return $mail->hasTo('user@example.com');
        });
    }

    public function test_email_is_not_sent_for_nonexisting_email(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/forgot-password/', ['email' => 'nonexistent@example.com']);

        Mail::assertNothingSent();
    }
}
