<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaksinasi extends Model
{
    /** Metode pemberian: 'suntik' (1x/tahun) | 'oral' (tiap 3 bulan, campur makanan/minuman). */
    public const METODE = ['suntik', 'oral'];

    /** Siklus ulang per metode (bulan) — aturan dokter hewan. */
    public const SIKLUS_BULAN = ['suntik' => 12, 'oral' => 3];

    protected $fillable = ['burung_id', 'jenis', 'metode', 'dosis', 'tanggal_vaksin', 'catatan'];

    protected $casts = ['tanggal_vaksin' => 'date'];

    public function burung(): BelongsTo
    {
        return $this->belongsTo(Burung::class);
    }

    /** Nama obat/vaksin standar + opsi Lainnya (input teks bebas). */
    public const JENIS_STANDAR = ['Obat Cacing', 'NDV', 'Avian Influenza', 'Cemvac', 'Vitamin'];

    /**
     * Catatan terakhir tiap burung (semua metode), beserta status jatuh tempo
     * yang dihitung dari siklus metode masing-masing.
     */
    public static function catatanTerakhirPerBurung()
    {
        return static::with('burung')
            ->orderByDesc('tanggal_vaksin')
            ->orderByDesc('id')
            ->get()
            ->unique('burung_id')
            ->values();
    }

    /** Jatuh tempo = tanggal terakhir + siklus metode sudah terlewat. */
    public function getSudahJatuhTempoAttribute(): bool
    {
        return $this->tanggal_vaksin->copy()->addMonths(self::SIKLUS_BULAN[$this->metode] ?? 12)->isPast();
    }

    /** Tanggal jatuh tempo berikutnya. */
    public function getJatuhTempoAttribute()
    {
        return $this->tanggal_vaksin->copy()->addMonths(self::SIKLUS_BULAN[$this->metode] ?? 12);
    }
}
