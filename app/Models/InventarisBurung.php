<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarisBurung extends Model
{
    protected $table = 'inventaris_burung';

    protected $fillable = [
        'nama_spesies',
        'fase',
        'harga',
        'stok',
        'deskripsi',
        'aktif',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiChatbot::class, 'inventaris_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true)->where('stok', '>', 0);
    }
}
