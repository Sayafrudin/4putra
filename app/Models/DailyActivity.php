<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyActivity extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

    protected $fillable = ['title', 'title_en', 'description', 'description_en', 'video_urls', 'activity_date', 'images'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'activity_date' => 'date',
            'images' => 'array',
            'video_urls' => 'array',
        ];
    }
}
