<?php

namespace Database\Seeders;

use App\Models\EmailOTP;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CurrencySeeder::class);

        $accounts = [
            [
                'email' => 'admin@almohit.com',
                'full_name' => 'Admin User',
                'role' => User::ROLE_ADMIN,
                'is_staff' => true,
                'is_superuser' => true,
                'is_active' => true,
                'email_verified' => true,
                'password' => 'adminpass123',
            ],
            [
                'email' => 'staff@almohit.com',
                'full_name' => 'Staff User',
                'role' => User::ROLE_STAFF,
                'is_staff' => true,
                'is_active' => true,
                'email_verified' => true,
                'password' => 'staffpass123',
            ],
            [
                'email' => 'customer@almohit.com',
                'full_name' => 'Customer User',
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
                'email_verified' => true,
                'password' => 'customerpass123',
            ],
            [
                'email' => 'test@example.com',
                'full_name' => 'Test User',
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
                'email_verified' => true,
                'password' => 'testpass123',
            ],
        ];

        foreach ($accounts as $data) {
            $data['password'] = bcrypt($data['password']);

            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            if ($user->wasRecentlyCreated) {
                EmailOTP::query()->create([
                    'user_id' => $user->id,
                    'hashed_code' => bcrypt('123456'),
                    'expires_at' => now()->addMinutes(10),
                ]);
            }
        }
    }
}
