<?php

namespace App\Http\Resources;

use App\Support\CompatResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'full_name' => $this->author?->full_name,
                'email' => $this->author?->email,
            ]),
            'category' => $this->whenLoaded('category', fn () => BlogCategoryResource::make($this->category)->resolve($request)),
            'hotel' => $this->whenLoaded('hotel', fn () => $this->hotel ? CompatResponse::hotel($this->hotel) : null),
            'created_at' => optional($this->created_at)->toJSON(),
            'updated_at' => optional($this->updated_at)->toJSON(),
        ];
    }
}
