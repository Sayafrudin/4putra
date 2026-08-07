<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $keyType = 'string';

    public $incrementing = true;

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
            'id' => 'string',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ambil metadata sebagai array (handle string JSON dari PDO).
     */
    private function getMetadataArray(): array
    {
        $raw = $this->attributes['metadata'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && !empty($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Ambil nilai lama dari metadata.
     */
    public function getOldValuesAttribute(): ?array
    {
        return $this->getMetadataArray()['old_values'] ?? null;
    }

    /**
     * Ambil nilai baru dari metadata.
     */
    public function getNewValuesAttribute(): ?array
    {
        return $this->getMetadataArray()['new_values'] ?? null;
    }

    /**
     * Ambil preview gambar dari metadata.
     */
    public function getImagePreviewsAttribute(): ?array
    {
        return $this->getMetadataArray()['image_previews'] ?? null;
    }
}
