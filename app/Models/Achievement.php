<?php

// app/Models/Achievement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'video_url', 'video_file', 'external_link'];

    // Relasi ke banyak gambar
    public function images()
    {
        return $this->hasMany(AchievementImage::class);
    }
}
