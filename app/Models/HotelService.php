<?php

namespace App\Models;

class HotelService extends Facility
{
    private ?int $pendingHotelId = null;

    public function fill(array $attributes)
    {
        if (array_key_exists('hotel_id', $attributes)) {
            $this->pendingHotelId = $attributes['hotel_id'] ? (int) $attributes['hotel_id'] : null;
            unset($attributes['hotel_id']);
        }

        if (array_key_exists('service_category_id', $attributes)) {
            $attributes['facility_category_id'] = $attributes['service_category_id'];
            unset($attributes['service_category_id']);
        }

        return parent::fill($attributes);
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'hotel_id') {
            $this->pendingHotelId = $value ? (int) $value : null;

            return $this;
        }

        if ($key === 'service_category_id') {
            return parent::setAttribute('facility_category_id', $value);
        }

        return parent::setAttribute($key, $value);
    }

    public function save(array $options = [])
    {
        $hotelId = $this->pendingHotelId;
        $saved = parent::save($options);

        if ($saved && $hotelId) {
            $this->hotels()->syncWithoutDetaching([$hotelId]);
        }

        return $saved;
    }

    protected static function booted(): void
    {
        static::creating(function (HotelService $service): void {
            if (array_key_exists('hotel_id', $service->attributes)) {
                $service->pendingHotelId = (int) $service->attributes['hotel_id'];
                unset($service->attributes['hotel_id']);
            }

            if (array_key_exists('service_category_id', $service->attributes)) {
                $service->attributes['facility_category_id'] = $service->attributes['service_category_id'];
                unset($service->attributes['service_category_id']);
            }
        });

        static::created(function (HotelService $service): void {
            if ($service->pendingHotelId) {
                $service->hotels()->syncWithoutDetaching([$service->pendingHotelId]);
            }
        });
    }
}
