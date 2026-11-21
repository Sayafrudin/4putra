<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat perpindahan burung antar kandang/farm (ditempatkan|dikeluarkan).
 * Ditulis otomatis oleh KandangController saat letakkan/kosongkan.
 */
class BurungPindah extends Model
{
    protected $fillable = ['burung_id', 'kandang_id', 'aksi', 'keterangan'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Kandang::class);
    }
}
