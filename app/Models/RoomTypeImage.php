<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTypeImage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean', 'is_active' => 'boolean'];
    }
}
