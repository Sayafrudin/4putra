<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'google_id',
        'role',
        'last_login_at',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Login dashboard memakai username; bila dibuat tanpa username, turunkan
    // otomatis dari prefix email (syafrudintdm@gmail.com → syafrudintdm).
    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->username) && ! empty($user->email)) {
                $base = substr(preg_replace('/[^a-zA-Z0-9._-]/', '', explode('@', $user->email)[0]) ?: 'user', 0, 50);
                $username = $base;
                $i = 1;
                while (self::where('username', $username)->exists()) {
                    $username = substr($base, 0, 46).($i++);
                }
                $user->username = $username;
            }
        });
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->diffInMinutes(now()) < 5;
    }
}
