<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMakanan extends Model
{
    protected $fillable = ['nama', 'jumlah_stok', 'satuan', 'konsumsi_per_hari', 'catatan'];

    protected $casts = ['jumlah_stok' => 'decimal:2', 'konsumsi_per_hari' => 'decimal:2'];

    /** Prediksi: hari terakhir stok, dihitung otomatis dari konsumsi harian. */
    public function getEstimasiHariAttribute(): ?int
    {
        $konsumsi = (float) $this->konsumsi_per_hari;

        return $konsumsi > 0 ? (int) floor((float) $this->jumlah_stok / $konsumsi) : null;
    }

    /** Tanggal perkiraan harus beli lagi. */
    public function getTanggalBeliUlangAttribute()
    {
        return $this->estimasi_hari !== null ? now()->addDays($this->estimasi_hari) : null;
    }

    /** Ambang bahaya: stok tinggal < 2 minggu → segera beli. */
    public function getPerluBeliAttribute(): bool
    {
        return $this->estimasi_hari !== null && $this->estimasi_hari <= 14;
    }
}
