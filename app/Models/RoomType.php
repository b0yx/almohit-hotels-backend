<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'smoking_allowed' => 'boolean',
            'extra_bed_allowed' => 'boolean',
            'breakfast_included' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomTypeImage::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(RoomPrice::class);
    }
}
