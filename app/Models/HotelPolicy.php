<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPolicy extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime:H:i',
            'check_out_time' => 'datetime:H:i',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
