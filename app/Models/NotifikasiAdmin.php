<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiAdmin extends Model
{
    protected $table = 'notifikasi_admins';

    protected $fillable = [
        'tipe',
        'judul',
        'isi',
        'pelanggan_id',
        'dibaca',
    ];

    protected $casts = [
        'dibaca' => 'boolean',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function scopeBelumDibaca($query)
    {
        return $query->where('dibaca', false);
    }
}
