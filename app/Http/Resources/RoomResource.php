<?php

namespace App\Http\Resources;

use App\Support\LocalizedMapper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'capacity' => (int) $this->max_adults,
            'price' => number_format((float) $this->base_price, 2, '.', ''),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'image' => $img->image,
                'thumbnail' => $img->thumbnail,
                'caption' => $img->caption,
                'alt_text' => $img->alt_text,
                'is_cover' => (bool) $img->is_cover,
            ])->values(), []),
        ];

        return LocalizedMapper::mapOutput($this->resource, $data);
    }
}
