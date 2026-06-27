<?php

namespace App\Http\Requests\Api;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isStaffRole());
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id ?? $this->route('post');
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('blog_posts', 'slug')->ignore($postId)],
            'excerpt' => [$required, 'string'],
            'content' => [$required, 'string'],
            'featured_image' => [$required, 'string', 'max:500'],
            'featured_image_alt' => [$required, 'string', 'max:255'],
            'meta_title' => [$required, 'string', 'max:255'],
            'meta_description' => [$required, 'string', 'max:320'],
            'status' => [$required, 'string', Rule::in(BlogPost::statuses())],
            'published_at' => [$required, 'date'],
            'locale' => [$required, 'string', Rule::in(BlogPost::LOCALES)],
            'author_id' => [$required, 'integer', 'exists:users,id'],
            'category_id' => [$required, 'integer', 'exists:blog_categories,id'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
        ];
    }
}
