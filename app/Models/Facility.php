<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    protected $table = 'facilities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'advance_booking_required' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'facility_hotel', 'facility_id', 'hotel_id')->withTimestamps();
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'facility_room_type', 'facility_id', 'room_type_id')->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(FacilityImage::class);
    }
}
