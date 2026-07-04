<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityCategory extends Model
{
    protected $table = 'facility_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
