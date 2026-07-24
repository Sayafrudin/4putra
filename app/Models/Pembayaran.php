<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    protected $table = 'pembayarans';

    protected $fillable = [
        'transaksi_id',
        'midtrans_txn_id',
        'metode',
        'nominal',
        'status',
        'raw_webhook',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'raw_webhook' => 'array',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiChatbot::class, 'transaksi_id');
    }
}
