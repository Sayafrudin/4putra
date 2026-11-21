<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Telur extends Model
{
    protected $fillable = [
        'burung_id', 'burung_pasangan_id', 'sesi', 'jumlah', 'butir_ke', 'tanggal_diambil', 'diambil_oleh',
        'kondisi', 'status', 'tanggal_menetas', 'catatan',
    ];

    protected $casts = ['tanggal_diambil' => 'date', 'tanggal_menetas' => 'date'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    /** Induk kedua dari pasangan (null pada catatan lama satu induk). */
    public function pasangan(): BelongsTo
    {
        return $this->belongsTo(Burung::class, 'burung_pasangan_id');
    }

    /** Label induk: "BNG × Military" untuk pasangan, satu nama untuk catatan lama. */
    public function getIndukLabelAttribute(): string
    {
        if (! $this->pasangan) {
            return $this->burung->nama;
        }

        return $this->burung->nama.' ('.$this->burung->spesies.($this->burung->gender_label ? ' - '.$this->burung->gender_label : '').') × '
            .$this->pasangan->nama.' ('.$this->pasangan->spesies.($this->pasangan->gender_label ? ' - '.$this->pasangan->gender_label : '').')';
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

    /** Umur anak sejak menetas dalam format "1 bulan 4 hari" — sinkron dengan menu Baby. */
    public function getUsiaMenetasLabelAttribute(): ?string
    {
        return \App\Support\Umur::label($this->usia_menetas_hari);
    }

    /** Anak siap diloloh setelah ±1 bulan (30 hari sejak menetas). */
    public function getSiapDilepasAttribute(): bool
    {
        return $this->status === 'menetas'
            && $this->tanggal_menetas !== null
            && $this->tanggal_menetas->diffInDays(now()) >= 30;
    }

    /** Baby hasil konversi butir ini (1 baby per butir, via burungs.telur_id). */
    public function baby(): HasOne
    {
        return $this->hasOne(Burung::class);
    }

    /** Label butir: "Butir 1/2" (1 baris = 1 butir). */
    public function getButirLabelAttribute(): string
    {
        return 'Butir '.$this->butir_ke.'/'.$this->jumlah;
    }
}
