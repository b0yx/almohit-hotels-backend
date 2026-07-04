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
            'check_in_from' => 'string',
            'check_in_to' => 'string',
            'check_out_from' => 'string',
            'check_out_to' => 'string',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
