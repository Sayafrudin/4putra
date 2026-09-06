<?php

// app/Models/AboutPage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    // Single-row settings (id = 1 selalu)
    protected $table = 'about_page';
    protected $fillable = ['media_type', 'media_path'];

    public static function current(): self
    {
        // Fallback aman kalau row belum di-seed (Vercel/produksi)
        return static::find(1) ?? new static(['media_type' => 'image', 'media_path' => 'img/achievement1.jpg']);
    }

    // URL siap render: path lokal -> asset(), lainnya (Cloudinary) -> apa adanya
    public function mediaUrl(): string
    {
        return str_starts_with($this->media_path, 'http') ? $this->media_path : asset($this->media_path);
    }
}
