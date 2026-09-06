<?php

// app/Models/Leadership.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leadership extends Model
{
    protected $fillable = ['name', 'role', 'role_en', 'photo_path', 'sort_order'];

    public function photoUrl(): string
    {
        return str_starts_with($this->photo_path, 'http') ? $this->photo_path : asset($this->photo_path);
    }

    // Role sesuai locale (EN fallback ke ID)
    public function roleForLocale(string $locale): string
    {
        return $locale === 'en' && $this->role_en ? $this->role_en : $this->role;
    }
}
