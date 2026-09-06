<?php

// app/Models/AboutPage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    // Single-row settings (id = 1 selalu)
    protected $table = 'about_page';
    protected $fillable = ['media_type', 'media_path'];

    public static function current(): self
    {
        // Fallback aman kalau row belum di-seed (Vercel/produksi)
        return static::find(1) ?? new static(['media_type' => 'image', 'media_path' => 'img/achievement1.jpg']);
    }

    // URL siap render: path lokal -> asset(), lainnya (Cloudinary) -> apa adanya
    public function mediaUrl(): string
    {
        return str_starts_with($this->media_path, 'http') ? $this->media_path : asset($this->media_path);
    }

    /**
     * Link eksternal (media_type = embed) -> URL embed siap iframe.
     * Pola sama dengan render video link di achievements.blade.php,
     * plus TikTok & Instagram. Return null jika link tidak dikenali.
     */
    public function embedUrl(): ?string
    {
        $url = $this->media_path;
        if (! $url || ! str_starts_with($url, 'http')) {
            return null;
        }

        // YouTube
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        // Google Drive
        if (preg_match('/drive\.google\.com\/file\/d\/([^\/\?]+)/', $url, $m)) {
            return 'https://drive.google.com/file/d/'.$m[1].'/preview';
        }
        if (preg_match('/drive\.google\.com\/.*[?&]id=([^&]+)/', $url, $m)) {
            return 'https://drive.google.com/file/d/'.$m[1].'/preview';
        }
        // Vimeo
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }
        // Dailymotion
        if (preg_match('/dailymotion\.com\/video\/([^_\?]+)/', $url, $m)) {
            return 'https://www.dailymotion.com/embed/video/'.$m[1];
        }
        // TikTok
        if (preg_match('/tiktok\.com\/(?:@[a-z0-9_.]+\/)?(?:video|photo)\/(\d+)/i', $url, $m)) {
            return 'https://www.tiktok.com/embed/v2/'.$m[1];
        }
        // Instagram (post/reel/tv)
        if (preg_match('/instagram\.com\/(p|reel|tv)\/([a-z0-9_-]+)/i', $url, $m)) {
            return 'https://www.instagram.com/'.$m[1].'/'.$m[2].'/embed';
        }

        return null;
    }
}
