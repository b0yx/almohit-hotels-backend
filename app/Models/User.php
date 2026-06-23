<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'full_name',
    'email',
    'phone',
    'role',
    'is_active',
    'email_verified',
    'is_staff',
    'is_superuser',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_STAFF = 'staff';
    public const ROLE_ADMIN = 'admin';

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function assignedHotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'hotel_user_assignments')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && (bool) $this->is_active;
    }

    public function isStaffRole(): bool
    {
        return $this->role === self::ROLE_STAFF && (bool) $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified' => 'boolean',
            'is_staff' => 'boolean',
            'is_superuser' => 'boolean',
            'last_failed_login' => 'datetime',
            'lockout_until' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
