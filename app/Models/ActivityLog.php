<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'module',
        'ip_address',
        'admin_comment',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ambil nilai lama dari metadata.
     */
    public function getOldValuesAttribute(): ?array
    {
        return $this->metadata['old_values'] ?? null;
    }

    /**
     * Ambil nilai baru dari metadata.
     */
    public function getNewValuesAttribute(): ?array
    {
        return $this->metadata['new_values'] ?? null;
    }

    /**
     * Ambil preview gambar dari metadata.
     */
    public function getImagePreviewsAttribute(): ?array
    {
        return $this->metadata['image_previews'] ?? null;
    }
}
