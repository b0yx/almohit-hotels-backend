<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
