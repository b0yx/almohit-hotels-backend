<?php

namespace Tests\Feature;

use App\Models\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_submit_review(): void
    {
        $hotel = Hotel::create(['name' => 'Test Hotel', 'slug' => 'test-hotel', 'publishing_status' => 'published', 'is_active' => true]);

        $this->postJson('/api/properties/'.$hotel->id.'/reviews/', [
            'guest_name' => 'John Doe',
            'rating' => 4,
            'comment' => 'Great hotel!',
            'cleanliness' => 5,
            'staff' => 4,
        ])->assertCreated()->assertJsonPath('guest_name', 'John Doe');
    }

    public function test_review_summary_returns_aggregates(): void
    {
        $hotel = Hotel::create(['name' => 'Test Hotel', 'slug' => 'test-hotel', 'publishing_status' => 'published', 'is_active' => true]);

        $this->postJson('/api/properties/'.$hotel->id.'/reviews/', [
            'guest_name' => 'John', 'rating' => 5, 'comment' => 'Great!',
        ]);

        $this->postJson('/api/properties/'.$hotel->id.'/reviews/', [
            'guest_name' => 'Jane', 'rating' => 3, 'comment' => 'Okay.',
        ]);

        $summary = $this->getJson('/api/properties/'.$hotel->id.'/reviews/summary/')
            ->assertOk();

        $this->assertEquals(2, $summary->json('total_reviews'));
        $this->assertEquals(4.0, $summary->json('average_rating'));
    }
}
