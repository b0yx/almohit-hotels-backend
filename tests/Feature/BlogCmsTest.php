<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogCmsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private BlogCategory $category;
    private string $adminToken;
    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'admin-blog@test.com',
            'full_name' => 'Admin Blog',
            'role' => 'admin',
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);
        $this->customer = User::query()->create([
            'email' => 'customer-blog@test.com',
            'full_name' => 'Customer Blog',
            'role' => 'customer',
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->category = BlogCategory::query()->create([
            'name' => 'Travel Guides',
            'slug' => 'travel-guides',
            'description' => 'Useful travel guides.',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->adminToken = $this->createToken($this->admin);
        $this->customerToken = $this->createToken($this->customer);
    }

    private function createToken(User $user): string
    {
        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return $plain;
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Best Hotels in Dubai',
            'slug' => 'best-hotels-in-dubai',
            'excerpt' => 'A practical guide to choosing hotels in Dubai.',
            'content' => 'Long-form travel content for Dubai hotel planning.',
            'featured_image' => '/media/blog/dubai.jpg',
            'featured_image_alt' => 'Dubai skyline near hotels',
            'meta_title' => 'Best Hotels in Dubai | Almohit Hotels',
            'meta_description' => 'Compare Dubai hotel areas and find the right stay.',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay()->toIso8601String(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'hotel_id' => null,
        ], $overrides);
    }

    public function test_public_blog_index_returns_only_published_posts(): void
    {
        BlogPost::query()->create($this->payload());
        BlogPost::query()->create($this->payload([
            'title' => 'Draft Guide',
            'slug' => 'draft-guide',
            'status' => BlogPost::STATUS_DRAFT,
        ]));
        BlogPost::query()->create($this->payload([
            'title' => 'Future Guide',
            'slug' => 'future-guide',
            'published_at' => now()->addDay(),
        ]));

        $this->getJson('/api/blog/posts/')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.slug', 'best-hotels-in-dubai');
    }

    public function test_public_blog_show_returns_post_relationships(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Dubai Hotel',
            'slug' => 'dubai-hotel',
            'subdomain' => 'dubai',
            'country' => 'UAE',
            'city' => 'Dubai',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);
        BlogPost::query()->create($this->payload(['hotel_id' => $hotel->id]));

        $this->getJson('/api/blog/posts/best-hotels-in-dubai/')
            ->assertOk()
            ->assertJsonPath('slug', 'best-hotels-in-dubai')
            ->assertJsonPath('category.slug', 'travel-guides')
            ->assertJsonPath('author.email', 'admin-blog@test.com')
            ->assertJsonPath('hotel.slug', 'dubai-hotel');
    }

    public function test_admin_can_create_update_and_delete_blog_post(): void
    {
        $create = $this->postJson('/api/admin/blog/posts/', $this->payload(), $this->authHeader($this->adminToken));
        $create->assertCreated()->assertJsonPath('slug', 'best-hotels-in-dubai');

        $postId = $create->json('id');

        $this->patchJson('/api/admin/blog/posts/'.$postId.'/', $this->payload([
            'title' => 'Best Dubai Hotels for Families',
            'slug' => 'best-dubai-hotels-for-families',
        ]), $this->authHeader($this->adminToken))
            ->assertOk()
            ->assertJsonPath('slug', 'best-dubai-hotels-for-families');

        $this->deleteJson('/api/admin/blog/posts/'.$postId.'/', [], $this->authHeader($this->adminToken))
            ->assertNoContent();

        $this->assertDatabaseMissing('blog_posts', ['id' => $postId]);
    }

    public function test_customer_cannot_manage_blog_posts(): void
    {
        $this->postJson('/api/admin/blog/posts/', $this->payload(), $this->authHeader($this->customerToken))
            ->assertStatus(403);
    }

    public function test_admin_can_manage_blog_categories(): void
    {
        $response = $this->postJson('/api/admin/blog/categories/', [
            'name' => 'Booking Tips',
            'slug' => 'booking-tips',
            'description' => 'Advice for travelers.',
            'locale' => 'en',
            'is_active' => true,
        ], $this->authHeader($this->adminToken));

        $response->assertCreated()->assertJsonPath('slug', 'booking-tips');
    }
    public function test_admin_can_partially_patch_blog_post(): void
    {
        $post = BlogPost::query()->create($this->payload());

        $this->patchJson('/api/admin/blog/posts/'.$post->id.'/', [
            'title' => 'Updated Blog Title',
        ], $this->authHeader($this->adminToken))
            ->assertOk()
            ->assertJsonPath('title', 'Updated Blog Title')
            ->assertJsonPath('slug', 'best-hotels-in-dubai');
    }

    public function test_public_blog_page_size_is_capped(): void
    {
        foreach (range(1, 105) as $i) {
            BlogPost::query()->create($this->payload([
                'title' => 'Published Guide '.$i,
                'slug' => 'published-guide-'.$i,
                'published_at' => now()->subMinutes($i),
            ]));
        }

        $this->getJson('/api/blog/posts/?page_size=500')
            ->assertOk()
            ->assertJsonCount(100, 'results');
    }
}
