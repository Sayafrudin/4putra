<?php

// app/Models/Achievement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

    protected $fillable = ['title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'date_end', 'location', 'video_url', 'video_file', 'external_link'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }

    // Relasi ke banyak gambar
    public function images()
    {
        return $this->hasMany(AchievementImage::class);
    }
}
