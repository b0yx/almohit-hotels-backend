<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyTest extends TestCase
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

    private function admin(): User
    {
        return User::query()->create([
            'email' => 'currency-admin@example.com',
            'full_name' => 'Currency Admin',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    private function customer(): User
    {
        return User::query()->create([
            'email' => 'currency-customer@example.com',
            'full_name' => 'Currency Customer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);
    }

    public function test_admin_can_create_currency_and_customer_cannot(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $payload = [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'symbol_position' => 'before',
            'decimal_places' => 2,
            'is_default' => true,
        ];

        $this->postJson('/api/admin/finance/currencies/', $payload, $this->headersFor($customer))
            ->assertStatus(403);

        $this->postJson('/api/admin/finance/currencies/', $payload, $this->headersFor($admin))
            ->assertCreated()
            ->assertJsonPath('code', 'USD')
            ->assertJsonPath('is_default', true);
    }

    public function test_currency_code_must_be_uppercase_iso_4217(): void
    {
        $admin = $this->admin();

        $this->postJson('/api/admin/finance/currencies/', [
            'code' => 'usd',
            'name' => 'US Dollar',
        ], $this->headersFor($admin))->assertStatus(422);

        $this->postJson('/api/admin/finance/currencies/', [
            'code' => 'ZZZ',
            'name' => 'Invalid Currency',
        ], $this->headersFor($admin))->assertStatus(422);
    }

    public function test_exchange_rates_are_unique_by_pair_and_effective_date_and_immutable(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        $usd = Currency::query()->create(['code' => 'USD', 'name' => 'US Dollar', 'is_default' => true]);
        $sar = Currency::query()->create(['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol_position' => 'after']);

        $payload = [
            'from_currency_id' => $usd->id,
            'to_currency_id' => $sar->id,
            'exchange_rate' => '3.7500000000',
            'effective_from' => '2026-01-01 00:00:00',
            'source' => 'manual',
        ];

        $rateId = $this->postJson('/api/admin/finance/exchange-rates/', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('exchange_rate', '3.7500000000')
            ->json('id');

        $this->postJson('/api/admin/finance/exchange-rates/', $payload, $headers)
            ->assertStatus(422);

        $this->patchJson("/api/admin/finance/exchange-rates/{$rateId}/", [
            'exchange_rate' => '3.7600000000',
        ], $headers)->assertStatus(422);

        $this->patchJson("/api/admin/finance/exchange-rates/{$rateId}/", [
            'notes' => 'Verified manually',
        ], $headers)->assertOk()
            ->assertJsonPath('notes', 'Verified manually');
    }

    public function test_money_formatting_supports_english_and_arabic(): void
    {
        $currency = Currency::query()->create([
            'code' => 'YER',
            'name' => 'Yemeni Rial',
            'symbol' => 'ر.ي',
            'symbol_position' => 'after',
            'decimal_places' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
        ]);

        $service = app(CurrencyService::class);

        $this->assertSame('1,234.50 ر.ي', $service->format(1234.5, $currency, 'en'));
        $this->assertSame('١٬٢٣٤٫٥٠ ر.ي', $service->format(1234.5, $currency, 'ar'));
    }
}
