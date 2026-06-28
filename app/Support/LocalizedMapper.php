<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Models\HotelPolicy;
use App\Models\HotelService;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Model;

class LocalizedMapper
{
    private static array $modelFieldMap = [
        Hotel::class => ['name', 'description', 'short_description', 'meta_title', 'meta_description'],
        RoomType::class => ['name', 'description'],
        HotelService::class => ['name', 'short_description', 'description'],
        HotelAmenity::class => ['name'],
        HotelPolicy::class => ['cancellation_policy', 'children_policy', 'pet_policy', 'smoking_policy', 'extra_bed_policy'],
        BlogPost::class => ['meta_title', 'meta_description'],
    ];

    public static function mapInputForSave(string|object $model, array $data, ?string $locale = null, bool $isUpdate = false): array
    {
        $locale = $locale ?: LocalizationContext::getLocale();
        if (is_object($model) && $model instanceof Model) {
            $modelClass = get_class($model);
            if (! $isUpdate && $model->exists) {
                $isUpdate = true;
            }
        } else {
            $modelClass = (string) $model;
        }

        $fields = self::$modelFieldMap[$modelClass] ?? [];

        foreach ($fields as $field) {
            $arField = $field . '_ar';
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($locale === 'ar') {
                    $data[$arField] = $value;
                    // Only preserve non-nullable fields like 'name' on create to satisfy DB constraints
                    if ($isUpdate || $field !== 'name') {
                        unset($data[$field]);
                    }
                }
            }
        }

        return $data;
    }

    public static function mapOutput(Model $model, array $data, ?string $locale = null): array
    {
        $locale = $locale ?: LocalizationContext::getLocale();
        $modelClass = get_class($model);
        $fields = self::$modelFieldMap[$modelClass] ?? [];

        foreach ($fields as $field) {
            $arField = $field . '_ar';
            if (array_key_exists($arField, $data)) {
                if ($locale === 'ar') {
                    $data[$field] = $data[$arField] !== null && $data[$arField] !== '' ? $data[$arField] : ($data[$field] ?? null);
                }
            }
        }

        return $data;
    }
}
