<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Burung extends Model
{
    protected $fillable = [
        'nama', 'spesies', 'fase', 'gender', 'nomor_dna', 'nomor_ring', 'kandang_id', 'catatan',
        'telur_id', 'tanggal_lahir', 'induk_label', 'asal_label',
    ];

    protected $casts = ['tanggal_lahir' => 'date'];

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Kandang::class);
    }

    public function telur(): BelongsTo
    {
        return $this->belongsTo(Telur::class);
    }

    // Istilah antarmuka wajib "Baby", database tetap 'anakan'
    public function getFaseLabelAttribute(): string
    {
        return $this->fase === 'anakan' ? 'Baby' : 'Dewasa';
    }

    /** Umur realtime baby (hari sejak tanggal lahir); null untuk burung tanpa tanggal lahir. */
    public function getUsiaHariAttribute(): ?int
    {
        return $this->tanggal_lahir ? (int) $this->tanggal_lahir->diffInDays(now()) : null;
    }

    /** Umur baby dalam format "1 bulan 4 hari" (sama dengan menu Telur). */
    public function getUmurLabelAttribute(): ?string
    {
        return \App\Support\Umur::label($this->usia_hari);
    }

    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender ? ($this->gender === 'jantan' ? 'Jantan' : 'Betina') : null;
    }

    /** "Ring X" bila terisi; null bila kosong — field opsional tampil hanya bila terisi. */
    public function getLabelRingAttribute(): ?string
    {
        return $this->nomor_ring ? 'Ring '.$this->nomor_ring : null;
    }

    /** Identitas singkat: "Nama — Jantan · Ring X"; bagian opsional hilang bila kosong. */
    public function getIdentitasSingkatAttribute(): string
    {
        $bagian = array_filter([$this->gender_label, $this->label_ring]);

        return $bagian ? $this->nama.' — '.implode(' · ', $bagian) : $this->nama;
    }

    /** "Nama (Ring X)" untuk tabel/dropdown; tanpa "(...)" bila ring kosong. */
    public function getNamaRingAttribute(): string
    {
        return $this->nomor_ring ? $this->nama.' (Ring '.$this->nomor_ring.')' : $this->nama;
    }
}
