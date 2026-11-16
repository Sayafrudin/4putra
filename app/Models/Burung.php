<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Burung extends Model
{
    protected $fillable = ['nama', 'spesies', 'fase', 'gender', 'nomor_dna', 'kandang_id', 'catatan'];

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Kandang::class);
    }

    // Istilah antarmuka wajib "Baby", database tetap 'anakan'
    public function getFaseLabelAttribute(): string
    {
        return $this->fase === 'anakan' ? 'Baby' : 'Dewasa';
    }

    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender ? ($this->gender === 'jantan' ? 'Jantan' : 'Betina') : null;
    }
}
