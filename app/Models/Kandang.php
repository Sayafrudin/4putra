<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kandang extends Model
{
    protected $fillable = ['blok', 'nomor', 'tipe', 'lokasi'];

    protected $casts = ['nomor' => 'integer'];

    public function burungs(): HasMany
    {
        return $this->hasMany(Burung::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->tipe === 'aviary' ? 'Aviary '.$this->nomor : $this->blok.'-'.$this->nomor;
    }
}
