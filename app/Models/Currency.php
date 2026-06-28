<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Currency extends Model
{
    use SoftDeletes;

    public const SYMBOL_BEFORE = 'before';

    public const SYMBOL_AFTER = 'after';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fromExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function toExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    public function bookingInquiries(): HasMany
    {
        return $this->hasMany(BookingInquiry::class, 'booking_currency_id');
    }
}
