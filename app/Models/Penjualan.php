<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Snapshot penjualan burung: uang masuk Kas Umum (keuangan_id), data burung tetap tersimpan walau baris burung dihapus. */
class Penjualan extends Model
{
    protected $fillable = [
        'burung_id', 'nama_burung', 'nomor_ring', 'induk_label', 'usia_hari',
        'harga', 'tanggal_terjual', 'keuangan_id', 'keterangan',
    ];

    protected $casts = ['tanggal_terjual' => 'date'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    public function keuangan(): BelongsTo
    {
        return $this->belongsTo(Keuangan::class);
    }
}
