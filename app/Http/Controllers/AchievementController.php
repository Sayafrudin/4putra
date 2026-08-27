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
            ->select('id', 'title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'date_end', 'location', 'video_url', 'video_file', 'external_link')
            ->latest()->get();

        return view('admin.achievements.index', compact('achievements'));
    }

    public function publicIndex()
    {
        $achievements = Cache::remember('public.achievements', 60 * 60, function () {
            return Achievement::with('images:id,achievement_id,image_path')
                ->select('id', 'title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'date_end', 'location', 'video_url', 'video_file', 'external_link')
                ->orderBy('year', 'desc')
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('year');
        });

        return view('achievements', compact('achievements'));
    }

    public function destroyImage($id)
    {
        try {
            $image = AchievementImage::findOrFail($id);

            // Hapus dari Cloudinary jika URL cloudinary
            if (str_starts_with($image->image_path, 'http')) {
                try {
                    $url = $image->image_path;
                    $parts = parse_url($url);
                    $path = $parts['path'] ?? '';
                    $path = preg_replace('#^/image/upload/v\d+/#', '', $path);
                    $publicId = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME);
                    $publicId = trim($publicId, '/');

                    $cloudinary = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi();
                    $cloudinary->destroy($publicId, ['resource_type' => 'image']);
                } catch (\Exception $e) {
                    \Log::warning('Gagal hapus gambar Cloudinary: '.$e->getMessage());
                }
            }
            // Hapus dari storage lokal jika file lokal
            elseif (Storage::disk('public')->exists('achievements/'.$image->image_path)) {
                Storage::disk('public')->delete('achievements/'.$image->image_path);
            }

            $image->delete();

            // Cache gambar achievement berubah → buang cache publik & admin
            Cache::forget('public.achievements');
            Cache::forget('admin.achievements');

            return response()->json(['success' => true, 'message' => 'Foto berhasil dihapus.']);
        } catch (\Exception $e) {
            \Log::error('Gagal hapus foto achievement: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus foto: '.$e->getMessage()], 500);
        }
    }
}
