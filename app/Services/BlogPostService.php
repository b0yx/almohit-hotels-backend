<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostService
{
    private array $dbColumns = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 'featured_image_alt',
        'meta_title', 'meta_description', 'meta_title_ar', 'meta_description_ar',
        'status', 'published_at', 'locale', 'author_id', 'category_id', 'hotel_id',
    ];

    public function create(array $data): BlogPost
    {
        $filtered = array_intersect_key($data, array_flip($this->dbColumns));
        $post = BlogPost::query()->create($filtered);
        AuditService::log('created', 'blog_post', $post);

        if (array_key_exists('faqs', $data)) {
            FaqService::syncFaqs($post, $data['faqs']);
        }

        return $post;
    }

    public function update(BlogPost $post, array $data): BlogPost
    {
        $filtered = array_intersect_key($data, array_flip($this->dbColumns));

        $oldImage = $post->featured_image;
        $newImage = $filtered['featured_image'] ?? null;

        $changes = AuditService::changes($post, $filtered);
        $post->fill($filtered)->save();
        AuditService::log('updated', 'blog_post', $post, $changes);

        if (array_key_exists('faqs', $data)) {
            FaqService::syncFaqs($post, $data['faqs']);
        }

        if ($newImage !== null && $oldImage !== $newImage) {
            $this->deleteImageIfUnused($oldImage, $post->id);
        }

        return $post;
    }

    public function delete(BlogPost $post): void
    {
        $oldImage = $post->featured_image;
        $postId = $post->id;

        AuditService::log('deleted', 'blog_post', $post);
        $post->delete();

        $this->deleteImageIfUnused($oldImage, $postId);
    }

    private function deleteImageIfUnused(?string $imageString, int $excludePostId): void
    {
        $storagePath = $this->getStoragePath($imageString);
        if (! $storagePath) {
            return;
        }

        if (! Storage::disk('public')->exists($storagePath)) {
            return;
        }

        $filename = basename($storagePath);
        $isReferenced = BlogPost::query()
            ->whereKeyNot($excludePostId)
            ->where('featured_image', 'like', '%'.$filename.'%')
            ->exists();

        if (! $isReferenced) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function getStoragePath(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (str_contains($image, '/storage/')) {
            $path = Str::after($image, '/storage/');
        } elseif (str_starts_with($image, 'storage/')) {
            $path = Str::after($image, 'storage/');
        } else {
            $path = ltrim($image, '/');
        }

        if (str_starts_with($path, 'blog/')) {
            return $path;
        }

        return null;
    }
}
