<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelImage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean', 'is_active' => 'boolean'];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
