<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keuangan extends Model
{
    protected $fillable = ['tipe', 'kategori', 'jumlah', 'tanggal', 'keterangan'];

    protected $casts = ['jumlah' => 'decimal:2', 'tanggal' => 'date'];

    public const KATEGORI = ['Gaji', 'Operasional', 'Pembangunan', 'Penjualan', 'Lainnya'];

    public function getJumlahFormatAttribute(): string
    {
        return 'Rp '.number_format((float) $this->jumlah, 0, ',', '.');
    }
}
