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
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Collection::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Collection::class, 'parent_id')->orderBy('sort_order');
    }
}
