<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'languages_spoken' => 'array',
            'parking_available' => 'boolean',
            'airport_transfer' => 'boolean',
            'shuttle_service' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(HotelAmenity::class, 'hotel_amenity_hotel');
    }

    public function assignedStaff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hotel_user_assignments')->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(HotelImage::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(HotelService::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function coverImage(): ?HotelImage
    {
        return $this->images()->where('is_active', true)->orderByDesc('is_cover')->orderBy('display_order')->first();
    }

    public function policy(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(HotelPolicy::class);
    }

    public function socialMedia(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PropertySocialMedia::class);
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PropertyContacts::class);
    }

    public function setupStatus(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PropertySetupStatus::class);
    }
}
