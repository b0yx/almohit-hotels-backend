<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOTP extends Model
{
    protected $table = 'email_otps';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'resend_reset_at' => 'datetime',
        ];
    }
}
