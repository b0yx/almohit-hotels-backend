<?php

namespace App\Http\Resources;

use App\Support\LocalizedMapper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'featured_image_alt' => $this->featured_image_alt,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_title_ar' => $this->meta_title_ar,
            'meta_description_ar' => $this->meta_description_ar,
            'status' => $this->status,
            'published_at' => optional($this->published_at)->toJSON(),
            'locale' => $this->locale,
            'reading_time' => $this->calculateReadingTime($this->content),
            'faqs' => $this->whenLoaded('faqs', function () use ($request) {
                $faqs = $this->faqs;
                if (! $request->is('api/admin/*')) {
                    $faqs = $faqs->where('is_active', true);
                }
                return $faqs->sortBy('sort_order')->values()->map(function ($faq) use ($request) {
                    $item = [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                    if ($request->is('api/admin/*')) {
                        $item['sort_order'] = $faq->sort_order;
                        $item['is_active'] = (bool) $faq->is_active;
                    }
                    return $item;
                })->all();
            }),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'full_name' => $this->author?->full_name,
                'email' => $this->author?->email,
            ]),
            'category' => $this->whenLoaded('category', fn () => BlogCategoryResource::make($this->category)->resolve($request)),
            'hotel' => $this->whenLoaded('hotel', function () {
                if (! $this->hotel) {
                    return null;
                }
                $cover = $this->hotel->relationLoaded('images')
                    ? ($this->hotel->images->where('is_cover', true)->first()?->image ?? $this->hotel->images->first()?->image)
                    : null;

                return [
                    'id' => $this->hotel->id,
                    'name' => $this->hotel->name,
                    'slug' => $this->hotel->slug,
                    'featured_image' => $cover,
                ];
            }),
            'created_at' => optional($this->created_at)->toJSON(),
            'updated_at' => optional($this->updated_at)->toJSON(),
        ];

        return LocalizedMapper::mapOutput($this->resource, $data);
    }

    private function calculateReadingTime(?string $content): int
    {
        if (! $content) {
            return 1;
        }
        $text = strip_tags($content);
        $words = array_filter(preg_split('/\s+/u', trim($text)));
        $wordCount = count($words);

        return max(1, (int) ceil($wordCount / 200));
    }
}
