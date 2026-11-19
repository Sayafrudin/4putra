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
        'menu_access',
        'active_session_token',
        'last_login_at',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'active_session_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'menu_access' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Sesi tunggal: login baru (perangkat apa pun) menggantikan tiket sesi
     * teraktif. Perangkat lama otomatis ter-logout oleh middleware SingleSession.
     * Token disimpan di DB (pembanding) dan session (pemilik).
     */
    public function terbitkanTiketSesi(): string
    {
        $token = bin2hex(random_bytes(20));
        $this->forceFill(['active_session_token' => $token])->save();
        session(['sesi_token' => $token]);

        return $token;
    }

    // Login dashboard memakai username; bila dibuat tanpa username, turunkan
    // otomatis dari prefix email, atau dari nama bila email kosong.
    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->username)) {
                $sumber = ! empty($user->email) ? explode('@', $user->email)[0] : ($user->name ?? '');
                $base = substr(preg_replace('/[^a-zA-Z0-9._-]/', '', $sumber) ?: 'user', 0, 50);
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
