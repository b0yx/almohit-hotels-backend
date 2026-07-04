<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(array $overrides = []): User
    {
        return User::create(array_merge([
            'email' => 'customer@test.com',
            'full_name' => 'Test Customer',
            'role' => 'customer',
            'password' => 'password123',
            'is_active' => true,
            'email_verified' => true,
        ], $overrides));
    }

    private function createHotel(array $overrides = []): Hotel
    {
        return Hotel::create(array_merge([
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'publishing_status' => 'published',
            'is_active' => true,
        ], $overrides));
    }

    private function headers(User $user): array
    {
        $token = $user->createToken('test_token')->plainTextToken;

        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ─── Guest Access ──────────────────────────────────────────

    public function test_guest_cannot_add_favorite(): void
    {
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/")
            ->assertStatus(401);
    }

    public function test_guest_cannot_list_favorites(): void
    {
        $this->getJson('/api/favorites/')
            ->assertStatus(401);
    }

    public function test_guest_cannot_remove_favorite(): void
    {
        $hotel = $this->createHotel();

        $this->deleteJson("/api/favorites/{$hotel->id}/")
            ->assertStatus(401);
    }

    // ─── Add Favorite ──────────────────────────────────────────

    public function test_authenticated_user_can_add_favorite(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(201)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('hotel_id', $hotel->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
    }

    public function test_add_favorite_for_non_existent_hotel_returns_404(): void
    {
        $user = $this->createCustomer();

        $this->postJson('/api/favorites/99999/', [], $this->headers($user))
            ->assertStatus(404)
            ->assertJsonPath('detail', 'Hotel not found.');
    }

    public function test_duplicate_favorite_is_prevented(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(201);

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(409)
            ->assertJsonPath('detail', 'Hotel is already in favorites.');
    }

    // ─── Remove Favorite ──────────────────────────────────────────

    public function test_user_can_remove_favorite(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(201);

        $this->deleteJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(204);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
    }

    public function test_remove_non_existent_favorite_returns_404(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->deleteJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))
            ->assertStatus(404)
            ->assertJsonPath('detail', 'Favorite not found.');
    }

    // ─── List Favorites ──────────────────────────────────────────

    public function test_user_can_list_favorites(): void
    {
        $user = $this->createCustomer();
        $hotel1 = $this->createHotel(['name' => 'Hotel One', 'slug' => 'hotel-one']);
        $hotel2 = $this->createHotel(['name' => 'Hotel Two', 'slug' => 'hotel-two']);

        $this->postJson("/api/favorites/{$hotel1->id}/", [], $this->headers($user))->assertStatus(201);
        $this->postJson("/api/favorites/{$hotel2->id}/", [], $this->headers($user))->assertStatus(201);

        $response = $this->getJson('/api/favorites/', $this->headers($user))
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->assertCount(2, $response->json('results'));
    }

    public function test_user_only_sees_own_favorites(): void
    {
        $user1 = $this->createCustomer(['email' => 'user1@test.com']);
        $user2 = $this->createCustomer(['email' => 'user2@test.com']);
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user1))->assertStatus(201);

        $response = $this->getJson('/api/favorites/', $this->headers($user2))
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    // ─── is_favorite in Hotel Responses ──────────────────────────

    public function test_hotel_detail_includes_is_favorite(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $response = $this->getJson("/api/properties/{$hotel->id}/", $this->headers($user))
            ->assertOk();
        $this->assertFalse($response->json('is_favorite'));

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))->assertStatus(201);

        $response = $this->getJson("/api/properties/{$hotel->id}/", $this->headers($user))
            ->assertOk();
        $this->assertTrue($response->json('is_favorite'));
    }

    public function test_hotel_list_includes_is_favorite(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))->assertStatus(201);

        $response = $this->getJson('/api/properties/', $this->headers($user))
            ->assertOk();

        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $this->assertTrue($results[0]['is_favorite']);
    }

    public function test_hotel_detail_returns_is_favorite_false_for_guest(): void
    {
        $hotel = $this->createHotel();

        $response = $this->getJson("/api/properties/{$hotel->id}/")
            ->assertOk();
        $this->assertFalse($response->json('is_favorite'));
    }

    // ─── Cascade Delete ──────────────────────────────────────────

    public function test_favorite_is_deleted_when_hotel_is_deleted(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))->assertStatus(201);

        $hotel->delete();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
    }

    public function test_favorite_is_deleted_when_user_is_deleted(): void
    {
        $user = $this->createCustomer();
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))->assertStatus(201);

        $user->delete();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
    }

    // ─── Authorization ──────────────────────────────────────────

    public function test_user_cannot_modify_another_users_favorite(): void
    {
        $user1 = $this->createCustomer(['email' => 'user1@test.com']);
        $user2 = $this->createCustomer(['email' => 'user2@test.com']);
        $hotel = $this->createHotel();

        $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user1))->assertStatus(201);

        $this->deleteJson("/api/favorites/{$hotel->id}/", [], $this->headers($user2))
            ->assertStatus(404);
    }

    public function test_favorite_list_is_paginated(): void
    {
        $user = $this->createCustomer();

        for ($i = 1; $i <= 5; $i++) {
            $hotel = $this->createHotel(['name' => "Hotel $i", 'slug' => "hotel-$i"]);
            $this->postJson("/api/favorites/{$hotel->id}/", [], $this->headers($user))->assertStatus(201);
        }

        $response = $this->getJson('/api/favorites/?page_size=2', $this->headers($user))
            ->assertOk();

        $this->assertCount(2, $response->json('results'));
        $this->assertEquals(5, $response->json('count'));
    }
}
