<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telur extends Model
{
    protected $fillable = [
        'burung_id', 'sesi', 'jumlah', 'tanggal_diambil', 'diambil_oleh',
        'kondisi', 'status', 'tanggal_menetas', 'catatan',
    ];

    protected $casts = ['tanggal_diambil' => 'date', 'tanggal_menetas' => 'date'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    /** Usia telur dihitung sejak tanggal diambil (sesuai alur laporan farm). */
    public function getUsiaHariAttribute(): int
    {
        return (int) $this->tanggal_diambil->diffInDays(now());
    }

    /** Umur anak sejak menetas (masa incubator sampai ±3 bulan). */
    public function getUsiaMenetasHariAttribute(): ?int
    {
        return $this->tanggal_menetas ? (int) $this->tanggal_menetas->diffInDays(now()) : null;
    }

    /** Anak boleh dilepas setelah ±90 hari (bulu tumbuh lengkap). */
    public function getSiapDilepasAttribute(): bool
    {
        return $this->status === 'menetas'
            && $this->tanggal_menetas !== null
            && $this->tanggal_menetas->diffInDays(now()) >= 90;
    }
}
