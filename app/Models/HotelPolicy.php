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
            'check_in_time' => 'time',
            'check_out_time' => 'time',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
