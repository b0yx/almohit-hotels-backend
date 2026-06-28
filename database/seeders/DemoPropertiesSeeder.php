<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class DemoPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::query()->firstOrCreate(
            ['slug' => 'demo-hotel'],
            [
                'name' => 'Demo Hotel',
                'subdomain' => 'demo',
                'property_type' => 'hotel',
                'country' => 'UAE',
                'city' => 'Dubai',
                'address' => 'Demo Street',
                'stars' => 4,
                'publishing_status' => 'published',
                'is_active' => true,
            ]
        );

        $hotel->policy()->updateOrCreate(
            ['hotel_id' => $hotel->id],
            [
                'check_in_from' => '14:00:00',
                'check_in_to' => '00:00:00',
                'check_out_from' => '06:00:00',
                'check_out_to' => '12:00:00',
            ]
        );
    }
}
