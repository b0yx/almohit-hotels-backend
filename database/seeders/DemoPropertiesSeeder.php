<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use Illuminate\Database\Seeder;

class DemoPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        $hotelsData = [
            [
                'hotel' => [
                    'slug' => 'demo-hotel',
                    'name' => 'Demo Hotel',
                    'subdomain' => 'demo',
                    'property_type' => 'hotel',
                    'country' => 'UAE',
                    'city' => 'Dubai',
                    'address' => 'Demo Street 1',
                    'stars' => 4,
                    'short_description' => 'Experience prime luxury in the heart of Dubai.',
                    'publishing_status' => 'published',
                    'is_active' => true,
                ],
                'images' => [
                    [
                        'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                        'is_cover' => true,
                        'display_order' => 1,
                        'caption' => 'Demo Hotel Exterior',
                        'alt_text' => 'Hotel Exterior',
                    ],
                    [
                        'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
                        'is_cover' => false,
                        'display_order' => 2,
                        'caption' => 'Demo Hotel Lobby',
                        'alt_text' => 'Hotel Lobby',
                    ],
                ],
                'room_types' => [
                    [
                        'name' => 'Standard Double Room',
                        'description' => 'Cozy double room with modern amenities.',
                        'max_adults' => 2,
                        'max_children' => 0,
                        'total_units' => 10,
                        'base_price' => 120.00,
                        'currency' => 'USD',
                        'cover_image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=80',
                    ],
                    [
                        'name' => 'Junior Suite',
                        'description' => 'Spacious suite with seating area.',
                        'max_adults' => 2,
                        'max_children' => 1,
                        'total_units' => 5,
                        'base_price' => 220.00,
                        'currency' => 'USD',
                        'cover_image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80',
                    ],
                ],
            ],
            [
                'hotel' => [
                    'slug' => 'grand-almohit-hotel',
                    'name' => 'Grand Almohit Resort & Spa',
                    'subdomain' => 'grand',
                    'property_type' => 'resort',
                    'country' => 'UAE',
                    'city' => 'Dubai',
                    'address' => 'Beachfront Marina Boulevard',
                    'stars' => 5,
                    'short_description' => 'Luxury resort offering full beachfront relaxation.',
                    'publishing_status' => 'published',
                    'is_active' => true,
                ],
                'images' => [
                    [
                        'image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80',
                        'is_cover' => true,
                        'display_order' => 1,
                        'caption' => 'Grand Resort Exterior',
                        'alt_text' => 'Resort Exterior',
                    ],
                    [
                        'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80',
                        'is_cover' => false,
                        'display_order' => 2,
                        'caption' => 'Infinity Pool View',
                        'alt_text' => 'Infinity Pool',
                    ],
                    [
                        'image' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1200&q=80',
                        'is_cover' => false,
                        'display_order' => 3,
                        'caption' => 'Luxury Lounge',
                        'alt_text' => 'Luxury Lounge',
                    ],
                ],
                'room_types' => [
                    [
                        'name' => 'Deluxe Ocean View Room',
                        'description' => 'Breathtaking ocean views with king bed and balcony.',
                        'max_adults' => 2,
                        'max_children' => 1,
                        'total_units' => 8,
                        'base_price' => 250.00,
                        'currency' => 'USD',
                        'cover_image' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1200&q=80',
                    ],
                    [
                        'name' => 'Royal Presidential Suite',
                        'description' => 'Ultra-luxurious suite with private pool and butler service.',
                        'max_adults' => 4,
                        'max_children' => 2,
                        'total_units' => 2,
                        'base_price' => 800.00,
                        'currency' => 'USD',
                        'cover_image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
                    ],
                ],
            ],
        ];

        foreach ($hotelsData as $entry) {
            $hotel = Hotel::query()->firstOrCreate(
                ['slug' => $entry['hotel']['slug']],
                $entry['hotel']
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

            foreach ($entry['images'] as $img) {
                HotelImage::query()->firstOrCreate(
                    [
                        'hotel_id' => $hotel->id,
                        'image' => $img['image'],
                    ],
                    [
                        'is_cover' => $img['is_cover'],
                        'display_order' => $img['display_order'],
                        'caption' => $img['caption'],
                        'alt_text' => $img['alt_text'],
                        'is_active' => true,
                    ]
                );
            }

            foreach ($entry['room_types'] as $rtData) {
                $coverUrl = $rtData['cover_image'];
                unset($rtData['cover_image']);

                $roomType = RoomType::query()->firstOrCreate(
                    [
                        'hotel_id' => $hotel->id,
                        'name' => $rtData['name'],
                    ],
                    array_merge($rtData, ['is_active' => true])
                );

                RoomTypeImage::query()->firstOrCreate(
                    [
                        'room_type_id' => $roomType->id,
                        'image' => $coverUrl,
                    ],
                    [
                        'is_cover' => true,
                        'is_active' => true,
                        'display_order' => 1,
                        'caption' => $roomType->name.' Cover',
                        'alt_text' => $roomType->name,
                    ]
                );
            }
        }
    }
}
