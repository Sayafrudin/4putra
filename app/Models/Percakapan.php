<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Percakapan extends Model
{
    protected $table = 'percakapan';

    protected $fillable = [
        'pelanggan_id',
        'pesan_pengirim',
        'pesan_balasan',
        'sumber_balasan',
        'terkirim',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }
}
