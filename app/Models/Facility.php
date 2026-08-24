<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

    protected $fillable = ['title', 'title_en', 'category', 'category_en', 'description', 'description_en', 'video_url', 'images'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'images' => 'array',
        ];
    }
}
