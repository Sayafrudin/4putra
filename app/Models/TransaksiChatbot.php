<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiChatbot extends Model
{
    protected $table = 'transaksi_chatbot';

    protected $fillable = [
        'pelanggan_id',
        'inventaris_id',
        'nominal_dp',
        'total_harga',
        'quantity',
        'status',
        'midtrans_order_id',
        'qr_url',
    ];

    protected $casts = [
        'nominal_dp' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(InventarisBurung::class, 'inventaris_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'transaksi_id');
    }
}
