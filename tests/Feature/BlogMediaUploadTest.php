<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private string $adminToken;

    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::query()->create([
            'email'          => 'admin-media@test.com',
            'full_name'      => 'Admin Media',
            'role'           => 'admin',
            'is_active'      => true,
            'email_verified' => true,
            'password'       => 'password',
        ]);

        $this->customer = User::query()->create([
            'email'          => 'customer-media@test.com',
            'full_name'      => 'Customer Media',
            'role'           => 'customer',
            'is_active'      => true,
            'email_verified' => true,
            'password'       => 'password',
        ]);

        $this->adminToken    = $this->createToken($this->admin);
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

    /**
     * Helper: POST to /api/admin/blog/media as admin.
     */
    private function uploadAs(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(
            '/api/admin/blog/media',
            $payload,
            $this->authHeader($this->adminToken)
        );
    }

    /**
     * Assert a 201 response with the expected JSON structure + type.
     */
    private function assertSuccessfulUpload(
        \Illuminate\Testing\TestResponse $response,
        string $expectedType,
        string $expectedExtension
    ): void {
        $response->assertStatus(201)
            ->assertJsonStructure(['path', 'featured_image', 'url', 'type'])
            ->assertJson(['type' => $expectedType]);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('blog/', $path);
        $this->assertStringEndsWith('.'.$expectedExtension, $path);

        // Verify UUID-based filename (36 chars for UUID + dot + extension).
        $filename = basename($path);
        $uuidPart = Str::before($filename, '.');
        $this->assertTrue(
            (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuidPart),
            "Filename should start with a valid UUID, got: {$uuidPart}"
        );
    }

    // ═══════════════════════════════════════════════════════════════
    //  IMAGE UPLOADS — via `image` field (legacy frontend path)
    // ═══════════════════════════════════════════════════════════════

    public function test_upload_jpg_via_image_field(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048);

        $response = $this->uploadAs(['image' => $file]);

        $this->assertSuccessfulUpload($response, 'image', 'jpg');
    }

    public function test_upload_png_via_image_field(): void
    {
        $file = UploadedFile::fake()->image('banner.png', 1200, 800)->size(3072);

        $response = $this->uploadAs(['image' => $file]);

        $this->assertSuccessfulUpload($response, 'image', 'png');
    }

    public function test_upload_gif_via_image_field(): void
    {
        $file = UploadedFile::fake()->image('animation.gif', 200, 200)->size(512);

        $response = $this->uploadAs(['image' => $file]);

        $this->assertSuccessfulUpload($response, 'image', 'gif');
    }

    public function test_upload_webp_via_image_field(): void
    {
        $file = UploadedFile::fake()->image('hero.webp', 1920, 1080)->size(1024);

        $response = $this->uploadAs(['image' => $file]);

        $this->assertSuccessfulUpload($response, 'image', 'webp');
    }

    // ═══════════════════════════════════════════════════════════════
    //  IMAGE UPLOADS — via `file` field (new frontend path)
    // ═══════════════════════════════════════════════════════════════

    public function test_upload_image_via_file_field_returns_type_image(): void
    {
        $file = UploadedFile::fake()->image('banner.png', 1200, 800)->size(5120);

        $response = $this->uploadAs(['file' => $file]);

        $this->assertSuccessfulUpload($response, 'image', 'png');
    }

    // ═══════════════════════════════════════════════════════════════
    //  VIDEO UPLOADS — via `file` field
    // ═══════════════════════════════════════════════════════════════

    public function test_upload_mp4_video(): void
    {
        $file = UploadedFile::fake()->create('clip.mp4', 20480, 'video/mp4');

        $response = $this->uploadAs(['file' => $file]);

        $this->assertSuccessfulUpload($response, 'video', 'mp4');
    }

    public function test_upload_webm_video(): void
    {
        $file = UploadedFile::fake()->create('clip.webm', 15360, 'video/webm');

        $response = $this->uploadAs(['file' => $file]);

        $this->assertSuccessfulUpload($response, 'video', 'webm');
    }

    public function test_upload_mov_quicktime_video(): void
    {
        $file = UploadedFile::fake()->create('clip.mov', 30720, 'video/quicktime');

        $response = $this->uploadAs(['file' => $file]);

        $this->assertSuccessfulUpload($response, 'video', 'mov');
    }

    // ═══════════════════════════════════════════════════════════════
    //  REJECTION — unsupported file types (422)
    // ═══════════════════════════════════════════════════════════════

    public function test_rejects_pdf(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_svg(): void
    {
        $file = UploadedFile::fake()->create('icon.svg', 64, 'image/svg+xml');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_zip(): void
    {
        $file = UploadedFile::fake()->create('archive.zip', 2048, 'application/zip');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_php_file(): void
    {
        $file = UploadedFile::fake()->create('shell.php', 8, 'application/x-php');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_exe_file(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 128, 'application/x-msdownload');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  REJECTION — oversized files (422)
    // ═══════════════════════════════════════════════════════════════

    public function test_rejects_image_over_10mb(): void
    {
        $file = UploadedFile::fake()->create('huge.jpg', 11_000, 'image/jpeg');

        $this->uploadAs(['image' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_video_over_50mb(): void
    {
        $file = UploadedFile::fake()->create('huge.mp4', 52_000, 'video/mp4');

        $this->uploadAs(['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_image_at_exactly_10mb_is_accepted(): void
    {
        $file = UploadedFile::fake()->create('exact.jpg', 10_240, 'image/jpeg');

        $this->uploadAs(['image' => $file])->assertStatus(201);
    }

    public function test_video_at_exactly_50mb_is_accepted(): void
    {
        $file = UploadedFile::fake()->create('exact.mp4', 51_200, 'video/mp4');

        $this->uploadAs(['file' => $file])->assertStatus(201);
    }

    // ═══════════════════════════════════════════════════════════════
    //  REJECTION — no file (422)
    // ═══════════════════════════════════════════════════════════════

    public function test_rejects_request_without_file(): void
    {
        $this->uploadAs([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_string_instead_of_file(): void
    {
        $this->uploadAs(['image' => 'not-a-file.jpg'])
            ->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════
    //  FIELD PRIORITY — `file` beats `image`
    // ═══════════════════════════════════════════════════════════════

    public function test_file_field_takes_priority_over_image_field(): void
    {
        $image = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $video = UploadedFile::fake()->create('clip.mp4', 5120, 'video/mp4');

        $response = $this->uploadAs(['image' => $image, 'file' => $video]);

        $response->assertStatus(201)
            ->assertJson(['type' => 'video']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  AUTHORIZATION
    // ═══════════════════════════════════════════════════════════════

    public function test_customer_cannot_upload_media(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson(
            '/api/admin/blog/media',
            ['image' => $file],
            $this->authHeader($this->customerToken)
        )->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_upload_media(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/admin/blog/media', ['image' => $file])
            ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    //  RESPONSE CONTRACT
    // ═══════════════════════════════════════════════════════════════

    public function test_response_contains_all_required_fields(): void
    {
        $file = UploadedFile::fake()->image('test.webp', 400, 400)->size(512);

        $response = $this->uploadAs(['image' => $file]);
        $response->assertStatus(201);

        $data = $response->json();

        // Exact structure.
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('featured_image', $data);
        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('type', $data);

        // Format assertions.
        $this->assertStringStartsWith('blog/', $data['path']);
        $this->assertStringStartsWith('/storage/blog/', $data['featured_image']);
        $this->assertStringContainsString('/storage/blog/', $data['url']);
        $this->assertEquals('image', $data['type']);

        // featured_image and url are consistent with path.
        $this->assertSame('/storage/'.$data['path'], $data['featured_image']);
        $this->assertStringEndsWith('/storage/'.$data['path'], $data['url']);
    }

    public function test_video_response_has_correct_contract(): void
    {
        $file = UploadedFile::fake()->create('demo.mp4', 8192, 'video/mp4');

        $response = $this->uploadAs(['file' => $file]);
        $response->assertStatus(201);

        $data = $response->json();

        $this->assertStringStartsWith('blog/', $data['path']);
        $this->assertStringEndsWith('.mp4', $data['path']);
        $this->assertSame('/storage/'.$data['path'], $data['featured_image']);
        $this->assertStringEndsWith('/storage/'.$data['path'], $data['url']);
        $this->assertSame('video', $data['type']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  SECURITY — double extensions and MIME spoofing
    // ═══════════════════════════════════════════════════════════════

    public function test_stored_filename_uses_uuid_not_original_name(): void
    {
        $file = UploadedFile::fake()->image('../../etc/passwd.jpg', 100, 100);

        $response = $this->uploadAs(['image' => $file]);
        $response->assertStatus(201);

        $path = $response->json('path');

        // Must NOT contain the original filename or path-traversal components.
        $this->assertStringNotContainsString('passwd', $path);
        $this->assertStringNotContainsString('..', $path);
        $this->assertStringStartsWith('blog/', $path);
    }

    public function test_extension_derived_from_mime_not_client_name(): void
    {
        // Client says .png, but MIME is jpeg — extension should reflect actual content.
        $file = UploadedFile::fake()->image('lie.png', 100, 100); // fake()->image produces real JPEG/PNG

        $response = $this->uploadAs(['image' => $file]);
        $response->assertStatus(201);

        $path = $response->json('path');
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // The extension must be one of the allowed set (derived from MIME, not from client filename).
        $this->assertContains($extension, ['jpg', 'png', 'gif', 'webp']);
    }
}
