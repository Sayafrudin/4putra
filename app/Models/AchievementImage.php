<?php

// app/Models/AchievementImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchievementImage extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

    protected $fillable = ['achievement_id', 'image_path'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }
}
