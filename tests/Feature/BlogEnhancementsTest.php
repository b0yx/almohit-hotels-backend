<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $customer;

    private BlogCategory $categoryEn;

    private BlogCategory $categoryAr;

    private string $adminToken;

    private string $staffToken;

    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::query()->create([
            'email' => 'admin-enh@test.com',
            'full_name' => 'Admin Enh',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->staff = User::query()->create([
            'email' => 'staff-enh@test.com',
            'full_name' => 'Staff Enh',
            'role' => User::ROLE_STAFF,
            'is_staff' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->customer = User::query()->create([
            'email' => 'customer-enh@test.com',
            'full_name' => 'Customer Enh',
            'role' => User::ROLE_CUSTOMER,
            'is_staff' => false,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password',
        ]);

        $this->categoryEn = BlogCategory::query()->create([
            'name' => 'English Category',
            'slug' => 'english-category',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->categoryAr = BlogCategory::query()->create([
            'name' => 'التصنيف العربي',
            'slug' => 'arabic-category',
            'locale' => 'ar',
            'is_active' => true,
        ]);

        $this->adminToken = $this->createToken($this->admin);
        $this->staffToken = $this->createToken($this->staff);
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

    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sample Blog Post',
            'slug' => 'sample-blog-post',
            'excerpt' => 'Sample excerpt',
            'content' => 'Sample content for testing reading time and blog post creation.',
            'featured_image' => '/storage/blog/sample.jpg',
            'featured_image_alt' => 'Sample alt',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subHour()->toIso8601String(),
            'locale' => 'en',
            'author_id' => $this->admin->id,
            'category_id' => $this->categoryEn->id,
        ], $overrides);
    }

    public function test_admin_and_staff_can_upload_blog_image(): void
    {
        $file = UploadedFile::fake()->image('test_blog.jpg', 400, 400);

        $response = $this->postJson('/api/admin/blog/media', [
            'image' => $file,
        ], $this->authHeader($this->adminToken));

        $response->assertCreated()
            ->assertJsonStructure(['path', 'featured_image', 'url']);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    public function test_customer_cannot_upload_blog_image(): void
    {
        $file = UploadedFile::fake()->image('test_blog.jpg', 400, 400);

        $response = $this->postJson('/api/admin/blog/media', [
            'image' => $file,
        ], $this->authHeader($this->customerToken));

        $response->assertStatus(403);
    }

    public function test_locale_mismatch_is_rejected(): void
    {
        $payload = $this->postPayload([
            'locale' => 'en',
            'category_id' => $this->categoryAr->id,
        ]);

        $response = $this->postJson('/api/admin/blog/posts/', $payload, $this->authHeader($this->adminToken));

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Category locale must match post locale.');
    }

    public function test_pagination_supports_per_page(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            BlogCategory::query()->create([
                'name' => 'Category '.$i,
                'slug' => 'category-'.$i,
                'locale' => 'en',
                'is_active' => true,
            ]);
        }

        $response = $this->getJson('/api/admin/blog/categories?per_page=5', $this->authHeader($this->adminToken));
        $response->assertOk()
            ->assertJsonCount(5, 'results');
    }

    public function test_public_rooms_endpoint(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Grand Hotel',
            'name_ar' => 'فندق غراند',
            'slug' => 'grand-hotel',
            'subdomain' => 'grand',
            'country' => 'Turkey',
            'city' => 'Istanbul',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        RoomType::query()->create([
            'hotel_id' => $hotel->id,
            'name' => 'Deluxe Suite',
            'name_ar' => 'جناح فاخر',
            'description' => 'A luxury deluxe suite.',
            'description_ar' => 'جناح فاخر وواسع.',
            'max_adults' => 2,
            'max_children' => 1,
            'total_units' => 5,
            'base_price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/properties/grand-hotel/rooms');
        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Deluxe Suite')
            ->assertJsonPath('0.name_ar', 'جناح فاخر')
            ->assertJsonPath('0.capacity', 2)
            ->assertJsonPath('0.price', '150.00');
    }

    public function test_slug_uniqueness_validation_message(): void
    {
        $payload = $this->postPayload(['slug' => 'duplicate-slug']);
        $this->postJson('/api/admin/blog/posts/', $payload, $this->authHeader($this->adminToken))->assertCreated();

        $response = $this->postJson('/api/admin/blog/posts/', $payload, $this->authHeader($this->adminToken));
        $response->assertStatus(422)
            ->assertJsonPath('errors.slug.0', 'Slug already exists.');
    }

    public function test_slug_generation_endpoint(): void
    {
        $response = $this->postJson('/api/admin/blog/slug', [
            'text' => 'أفضل فنادق إسطنبول',
            'locale' => 'ar',
        ], $this->authHeader($this->adminToken));

        $response->assertOk()
            ->assertJsonPath('slug', 'afdl-fnadk-astnbol');
    }

    public function test_search_support_for_blog_posts_and_categories(): void
    {
        BlogPost::query()->create($this->postPayload(['title' => 'Istanbul Vacation Guide', 'slug' => 'istanbul-vacation']));
        BlogPost::query()->create($this->postPayload(['title' => 'Antalya Beach Guide', 'slug' => 'antalya-beach']));

        $response = $this->getJson('/api/admin/blog/posts?search=istanbul', $this->authHeader($this->adminToken));
        $response->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.slug', 'istanbul-vacation');
    }

    public function test_sorting_support_for_blog_posts(): void
    {
        BlogPost::query()->create($this->postPayload(['title' => 'Alpha Post', 'slug' => 'alpha-post']));
        BlogPost::query()->create($this->postPayload(['title' => 'Zeta Post', 'slug' => 'zeta-post']));

        $responseAsc = $this->getJson('/api/admin/blog/posts?sort=title', $this->authHeader($this->adminToken));
        $responseAsc->assertOk()
            ->assertJsonPath('results.0.title', 'Alpha Post');

        $responseDesc = $this->getJson('/api/admin/blog/posts?sort=-title', $this->authHeader($this->adminToken));
        $responseDesc->assertOk()
            ->assertJsonPath('results.0.title', 'Zeta Post');
    }

    public function test_image_cleanup_on_update_and_delete(): void
    {
        $file1 = UploadedFile::fake()->image('img1.jpg');
        $upload1 = $this->postJson('/api/admin/blog/media', ['image' => $file1], $this->authHeader($this->adminToken));
        $path1 = $upload1->json('path');
        $featImg1 = $upload1->json('featured_image');

        $file2 = UploadedFile::fake()->image('img2.jpg');
        $upload2 = $this->postJson('/api/admin/blog/media', ['image' => $file2], $this->authHeader($this->adminToken));
        $path2 = $upload2->json('path');
        $featImg2 = $upload2->json('featured_image');

        $postRes = $this->postJson('/api/admin/blog/posts/', $this->postPayload([
            'featured_image' => $featImg1,
        ]), $this->authHeader($this->adminToken));
        $postId = $postRes->json('id');

        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);

        // Update post with img2 -> img1 should be deleted from storage
        $this->patchJson('/api/admin/blog/posts/'.$postId.'/', [
            'featured_image' => $featImg2,
        ], $this->authHeader($this->adminToken))->assertOk();

        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        // Delete post -> img2 should be deleted from storage
        $this->deleteJson('/api/admin/blog/posts/'.$postId.'/', [], $this->authHeader($this->adminToken))->assertNoContent();
        Storage::disk('public')->assertMissing($path2);
    }
}
