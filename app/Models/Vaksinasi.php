<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaksinasi extends Model
{
    protected $fillable = ['burung_id', 'jenis', 'tanggal_vaksin', 'catatan'];

    protected $casts = ['tanggal_vaksin' => 'date'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    /**
     * Vaksin terakhir tiap burung, beserta status jatuh tempo (booster bulanan).
     */
    public static function vaksinTerakhirPerBurung()
    {
        return static::with('burung')
            ->orderByDesc('tanggal_vaksin')
            ->get()
            ->unique('burung_id')
            ->values();
    }

    public function getSudahJatuhTempoAttribute(): bool
    {
        return $this->tanggal_vaksin->copy()->addMonth()->isPast();
    }
}
