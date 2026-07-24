<?php

// app/Models/AchievementImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchievementImage extends Model
{
    protected $fillable = ['achievement_id', 'image_path'];

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }
}
