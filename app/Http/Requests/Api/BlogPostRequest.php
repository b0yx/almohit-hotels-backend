<?php

namespace App\Http\Requests\Api;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isStaffRole());
    }

    protected function failedAuthorization(): void
    {
        $message = $this->user()
            ? 'You do not have permission to perform this action.'
            : 'Authentication credentials were not provided.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'detail' => $message,
        ], $this->user() ? 403 : 401));
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
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_title_ar' => ['nullable', 'string', 'max:60'],
            'meta_description_ar' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'string', 'url', 'max:255'],
            'robots' => ['nullable', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'status' => [$required, 'string', Rule::in(BlogPost::statuses())],
            'published_at' => [$required, 'date'],
            'locale' => [$required, 'string', Rule::in(BlogPost::LOCALES)],
            'author_id' => [$required, 'integer', 'exists:users,id'],
            'category_id' => [$required, 'integer', 'exists:blog_categories,id'],
            'hotel_id' => ['nullable', 'integer', 'exists:hotels,id'],
            'faqs' => ['sometimes', 'nullable', 'array', 'max:50'],
            'faqs.*.id' => ['sometimes', 'nullable', 'integer', 'exists:faqs,id'],
            'faqs.*.question' => ['required_with:faqs', 'string', 'max:255'],
            'faqs.*.answer' => ['required_with:faqs', 'string'],
            'faqs.*.sort_order' => ['nullable', 'integer'],
            'faqs.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug already exists.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $locale = $this->input('locale');
            $categoryId = $this->input('category_id');

            $postId = $this->route('post')?->id ?? $this->route('post');
            if ($postId && (! $locale || ! $categoryId)) {
                $existing = BlogPost::query()->find($postId);
                if ($existing) {
                    $locale = $locale ?? $existing->locale;
                    $categoryId = $categoryId ?? $existing->category_id;
                }
            }

            if ($categoryId && $locale) {
                $category = BlogCategory::query()->find($categoryId);
                if ($category && $category->locale !== $locale) {
                    $validator->errors()->add('category_id', 'Category locale must match post locale.');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        $firstMessage = $errors->first();

        $response = response()->json([
            'message' => $firstMessage,
            'errors' => $errors->toArray(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
