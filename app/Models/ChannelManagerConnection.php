<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelManagerConnection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_connected' => 'boolean',
            'last_sync_at' => 'datetime',
            'rates_synced_at' => 'datetime',
            'availability_synced_at' => 'datetime',
        ];
    }
}
