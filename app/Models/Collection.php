<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

    protected $fillable = [
        'name',
        'name_en',
        'scientific_name',
        'category',
        'category_en',
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }
}
