<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FaqSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private BlogCategory $category;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'email' => 'admin-faq@test.com',
            'full_name' => 'Admin FAQ',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->category = BlogCategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->adminToken = $this->createToken($this->admin);
    }

    private function createToken(User $user): string
    {
        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return $plain;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->adminToken];
    }

    public function test_create_property_with_faqs(): void
    {
        $payload = [
            'name' => 'FAQ Hotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Downtown Dubai',
            'stars' => 5,
            'faqs' => [
                [
                    'question' => 'What is the check-in time?',
                    'answer' => 'Check-in time is 3:00 PM.',
                    'sort_order' => 1,
                ],
                [
                    'question' => 'Is breakfast included?',
                    'answer' => 'Yes, breakfast is served daily.',
                    'sort_order' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/properties/', $payload, $this->authHeader());
        $response->assertCreated()
            ->assertJsonCount(2, 'faqs')
            ->assertJsonPath('faqs.0.question', 'What is the check-in time?');

        $this->assertDatabaseHas('faqs', [
            'faqable_type' => Hotel::class,
            'question' => 'What is the check-in time?',
        ]);
    }

    public function test_update_property_faqs(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Update FAQ Hotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Dubai Marina',
            'stars' => 4,
        ]);

        $faq1 = $hotel->faqs()->create(['question' => 'Q1', 'answer' => 'A1', 'sort_order' => 1]);
        $faq2 = $hotel->faqs()->create(['question' => 'Q2', 'answer' => 'A2', 'sort_order' => 2]);

        $payload = [
            'faqs' => [
                [
                    'id' => $faq1->id,
                    'question' => 'Updated Q1',
                    'answer' => 'Updated A1',
                    'sort_order' => 1,
                ],
                [
                    'question' => 'New Q3',
                    'answer' => 'New A3',
                    'sort_order' => 3,
                ],
            ],
        ];

        $response = $this->patchJson('/api/properties/' . $hotel->id . '/', $payload, $this->authHeader());
        $response->assertOk()
            ->assertJsonCount(2, 'faqs')
            ->assertJsonPath('faqs.0.question', 'Updated Q1')
            ->assertJsonPath('faqs.1.question', 'New Q3');

        $this->assertDatabaseMissing('faqs', ['id' => $faq2->id]);
        $this->assertDatabaseHas('faqs', ['id' => $faq1->id, 'question' => 'Updated Q1']);
    }

    public function test_delete_property_faqs(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Delete FAQ Hotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'JBR',
            'stars' => 4,
        ]);
        $hotel->faqs()->create(['question' => 'Q1', 'answer' => 'A1']);

        $response = $this->patchJson('/api/properties/' . $hotel->id . '/', ['faqs' => []], $this->authHeader());
        $response->assertOk()->assertJsonCount(0, 'faqs');

        $this->assertEquals(0, $hotel->faqs()->count());
    }

    public function test_create_blog_post_with_faqs(): void
    {
        $payload = [
            'title' => 'Blog with FAQs',
            'slug' => 'blog-with-faqs',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'featured_image' => '/storage/blog/sample.jpg',
            'featured_image_alt' => 'Alt',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay()->toIso8601String(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'faqs' => [
                [
                    'question' => 'Is visa required?',
                    'answer' => 'Depends on nationality.',
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/admin/blog/posts/', $payload, $this->authHeader());
        $response->assertCreated()
            ->assertJsonCount(1, 'faqs')
            ->assertJsonPath('faqs.0.question', 'Is visa required?');
    }

    public function test_update_blog_post_faqs(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Blog Post',
            'slug' => 'blog-post',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'featured_image' => '/storage/blog/sample.jpg',
            'featured_image_alt' => 'Alt',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
        ]);
        $faq = $post->faqs()->create(['question' => 'Old Question', 'answer' => 'Old Answer']);

        $response = $this->patchJson('/api/admin/blog/posts/' . $post->id . '/', [
            'faqs' => [
                [
                    'id' => $faq->id,
                    'question' => 'New Question',
                    'answer' => 'New Answer',
                ],
            ],
        ], $this->authHeader());

        $response->assertOk()
            ->assertJsonPath('faqs.0.question', 'New Question');
    }

    public function test_faq_validation(): void
    {
        $payload = [
            'title' => 'Validation Blog',
            'slug' => 'validation-blog',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'featured_image' => '/storage/blog/sample.jpg',
            'featured_image_alt' => 'Alt',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay()->toIso8601String(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'faqs' => [
                [
                    'question' => '', // Required string
                    'answer' => 'Answer',
                ],
            ],
        ];

        $response = $this->postJson('/api/admin/blog/posts/', $payload, $this->authHeader());
        $response->assertStatus(422)
            ->assertJsonValidationErrors('faqs.0.question');
    }

    public function test_faq_ordering_and_only_active_faqs_returned_on_public_endpoint(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Public FAQ Blog',
            'slug' => 'public-faq-blog',
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'featured_image' => '/storage/blog/sample.jpg',
            'featured_image_alt' => 'Alt',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
        ]);

        $post->faqs()->create(['question' => 'Third Q', 'answer' => 'A3', 'sort_order' => 30, 'is_active' => true]);
        $post->faqs()->create(['question' => 'Inactive Q', 'answer' => 'A2', 'sort_order' => 10, 'is_active' => false]);
        $post->faqs()->create(['question' => 'First Q', 'answer' => 'A1', 'sort_order' => 5, 'is_active' => true]);

        $response = $this->getJson('/api/blog/posts/' . $post->slug . '/');
        $response->assertOk()
            ->assertJsonCount(2, 'faqs')
            ->assertJsonPath('faqs.0.question', 'First Q')
            ->assertJsonPath('faqs.1.question', 'Third Q');
    }

    public function test_cascade_deletion_when_parent_is_deleted(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Cascade Hotel',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Dubai Marina',
            'stars' => 4,
        ]);
        $faq = $hotel->faqs()->create(['question' => 'Q1', 'answer' => 'A1']);

        $hotel->delete();

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
