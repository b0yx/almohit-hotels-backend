<?php

namespace App\Http\Requests\Api;

use App\Models\BlogPost;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isStaffRole());
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');
        $required = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:150'],
            'slug' => [$required, 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('blog_categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'locale' => [$required, 'string', Rule::in(BlogPost::LOCALES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug already exists.',
        ];
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
