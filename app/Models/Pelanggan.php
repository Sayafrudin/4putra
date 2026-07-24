<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'nomor_wa',
        'nama',
        'sesi_aktif',
        'riwayat_konteks',
        'pesan_terakhir',
    ];

    protected $casts = [
        'riwayat_konteks' => 'array',
        'pesan_terakhir' => 'datetime',
    ];

    public function percakapan(): HasMany
    {
        return $this->hasMany(Percakapan::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiChatbot::class);
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(NotifikasiAdmin::class);
    }

    public function percakapanTerakhir(int $limit = 10)
    {
        return $this->percakapan()->latest()->limit($limit)->get()->reverse();
    }
}
