<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingInquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'extra_bed_needed' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookingCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'booking_currency_id');
    }

    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class);
    }
}
