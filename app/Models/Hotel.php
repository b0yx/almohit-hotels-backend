<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Hotel extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleting(function ($hotel) {
            $hotel->faqs()->delete();
        });
    }

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

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_hotel')->withTimestamps();
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }

    public function assignedStaff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hotel_user_assignments')->withTimestamps();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(HotelImage::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_hotel')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function coverImage(): ?HotelImage
    {
        return $this->images()->where('is_active', true)->orderByDesc('is_cover')->orderBy('display_order')->first();
    }

    public function policy(): HasOne
    {
        return $this->hasOne(HotelPolicy::class);
    }

    public function socialMedia(): HasOne
    {
        return $this->hasOne(PropertySocialMedia::class);
    }

    public function contacts(): HasOne
    {
        return $this->hasOne(PropertyContacts::class);
    }

    public function setupStatus(): HasOne
    {
        return $this->hasOne(PropertySetupStatus::class);
    }
}
