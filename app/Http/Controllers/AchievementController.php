<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AchievementImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = Achievement::with('images:id,achievement_id,image_path')
            ->select('id', 'title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'video_url', 'video_file', 'external_link')
            ->latest()->get();

        return view('admin.achievements.index', compact('achievements'));
    }

    public function publicIndex()
    {
        $achievements = Cache::remember('public.achievements', 300, function () {
            return Achievement::with('images:id,achievement_id,image_path')
                ->select('id', 'title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'video_url', 'video_file', 'external_link')
                ->orderBy('year', 'desc')
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('year');
        });

        return view('achievements', compact('achievements'));
    }

    public function destroyImage(AchievementImage $image)
    {
        // Hapus dari Cloudinary jika URL cloudinary
        if (str_starts_with($image->image_path, 'http')) {
            try {
                // Ekstrak public_id dari URL Cloudinary
                $url = $image->image_path;
                $parts = parse_url($url);
                $path = $parts['path'] ?? '';
                // Hapus versi dan ekstensi: /image/upload/v1234/4putra/achievements/filename.jpg
                $path = preg_replace('#^/image/upload/v\d+/#', '', $path);
                $publicId = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME);
                $publicId = trim($publicId, '/');

                \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::destroy($publicId);
            } catch (\Exception $e) {
                \Log::warning('Gagal hapus gambar Cloudinary: '.$e->getMessage());
            }
        }
        // Hapus dari storage lokal jika file lokal
        elseif (Storage::disk('public')->exists('achievements/'.$image->image_path)) {
            Storage::disk('public')->delete('achievements/'.$image->image_path);
        }

        $image->delete();

        return response()->json(['success' => true, 'message' => 'Foto berhasil dihapus.']);
    }
}
