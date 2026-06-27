<?php

namespace App\Services;

use App\Models\BlogPost;

class BlogPostService
{
    public function create(array $data): BlogPost
    {
        $post = BlogPost::query()->create($data);
        AuditService::log('created', 'blog_post', $post);

        return $post;
    }

    public function update(BlogPost $post, array $data): BlogPost
    {
        $changes = AuditService::changes($post, $data);
        $post->fill($data)->save();
        AuditService::log('updated', 'blog_post', $post, $changes);

        return $post;
    }

    public function delete(BlogPost $post): void
    {
        AuditService::log('deleted', 'blog_post', $post);
        $post->delete();
    }
}
