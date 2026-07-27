<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AchievementImage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminAchievementController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $achievements = Cache::remember('admin.achievements', 120, function () {
            return Achievement::with('images:id,achievement_id,image_path')
                ->select('id', 'title', 'title_en', 'title_highlight', 'title_highlight_en', 'year', 'description', 'description_en', 'date', 'video_url', 'video_file', 'external_link')
                ->latest()->get();
        });

        return view('admin.achievements.index', compact('achievements'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string',
                'year' => 'required|integer',
                'date' => 'required|date',
                'description' => 'required|string',
            ]);

            $externalLinks = $request->external_link;
            if (is_string($externalLinks)) {
                $externalLinks = json_decode($externalLinks, true) ?: [];
            }
            if (!is_array($externalLinks)) {
                $externalLinks = $externalLinks ? [$externalLinks] : [];
            }
            $externalLinks = array_values(array_filter($externalLinks, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

            $videoUrls = $request->video_url;
            if (is_string($videoUrls)) {
                $videoUrls = json_decode($videoUrls, true) ?: [];
            }
            if (!is_array($videoUrls)) {
                $videoUrls = $videoUrls ? [$videoUrls] : [];
            }
            $videoUrls = array_values(array_filter($videoUrls, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

            $achievement = Achievement::create([
                'title' => $request->title,
                'title_en' => $request->title_en,
                'title_highlight' => $request->title_highlight,
                'title_highlight_en' => $request->title_highlight_en,
                'year' => $request->year,
                'date' => $request->date,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'video_url' => !empty($videoUrls) ? json_encode($videoUrls) : null,
                'external_link' => !empty($externalLinks) ? json_encode($externalLinks) : null,
            ]);

            // Gambar/video di-upload langsung dari browser ke Cloudinary
            $cloudUrls = $request->input('cloudinary_urls', []);
            $cloudTypes = $request->input('cloudinary_types', []);

            foreach ($cloudUrls as $i => $url) {
                $type = $cloudTypes[$i] ?? 'image';
                if ($type === 'video') {
                    $achievement->update(['video_file' => $url]);
                } else {
                    AchievementImage::create([
                        'achievement_id' => $achievement->id,
                        'image_path' => $url,
                    ]);
                }
            }

            $this->logDataChange(
                $request, 'create',
                'Menambahkan pencapaian: '.$request->title,
                'Achievements',
                null,
                $achievement->getAttributes(),
                $achievement->images->pluck('image_path')->toArray()
            );

            Cache::forget('admin.achievements');
            Cache::forget('public.achievements');

            return response()->json([
                'success' => true,
                'message' => 'Data portofolio berhasil disimpan',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Achievement store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $achievement = Achievement::findOrFail($id);

        $imagePreviews = [];
        foreach ($achievement->images as $image) {
            $imagePreviews[] = $image->image_path;
        }

        $this->logDataChange(
            request(), 'delete',
            'Menghapus pencapaian: '.$achievement->title,
            'Achievements',
            $achievement->getOriginal(),
            null,
            ! empty($imagePreviews) ? $imagePreviews : null
        );

        $achievement->delete();

        Cache::forget('admin.achievements');
        Cache::forget('public.achievements');

        return redirect()->route('admin.achievements.index')->with('success', 'Data berhasil dihapus');
    }

    public function update(Request $request, $id)
    {
        try {
            $achievement = Achievement::findOrFail($id);
            $oldValues = $achievement->getOriginal();

            $externalLinks = $request->external_link;
            if (is_string($externalLinks)) {
                $externalLinks = json_decode($externalLinks, true) ?: [];
            }
            if (!is_array($externalLinks)) {
                $externalLinks = $externalLinks ? [$externalLinks] : [];
            }
            $externalLinks = array_values(array_filter($externalLinks, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

            $videoUrls = $request->video_url;
            if (is_string($videoUrls)) {
                $videoUrls = json_decode($videoUrls, true) ?: [];
            }
            if (!is_array($videoUrls)) {
                $videoUrls = $videoUrls ? [$videoUrls] : [];
            }
            $videoUrls = array_values(array_filter($videoUrls, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

            $data = $request->only([
                'title', 'title_en', 'title_highlight', 'title_highlight_en',
                'year', 'date', 'description', 'description_en',
            ]);
            $data['video_url'] = !empty($videoUrls) ? json_encode($videoUrls) : null;
            $data['external_link'] = !empty($externalLinks) ? json_encode($externalLinks) : null;

            $achievement->update($data);

            $imagePreviews = [];

            if ($request->input('remove_video') == '1' && $achievement->video_file) {
                $achievement->update(['video_file' => null]);
            }

            // Gambar/video di-upload langsung dari browser ke Cloudinary
            $cloudUrls = $request->input('cloudinary_urls', []);
            $cloudTypes = $request->input('cloudinary_types', []);

            foreach ($cloudUrls as $i => $url) {
                $type = $cloudTypes[$i] ?? 'image';
                if ($type === 'video') {
                    $achievement->update(['video_file' => $url]);
                } else {
                    AchievementImage::create(['achievement_id' => $achievement->id, 'image_path' => $url]);
                    $imagePreviews[] = $url;
                }
            }

            $this->logDataChange(
                $request, 'update',
                'Memperbarui pencapaian: '.$achievement->title,
                'Achievements',
                $oldValues,
                $achievement->fresh()->getAttributes(),
                ! empty($imagePreviews) ? $imagePreviews : null
            );

            Cache::forget('admin.achievements');
            Cache::forget('public.achievements');

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui!']);
        } catch (\Exception $e) {
            \Log::error('Achievement update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage(),
            ], 500);
        }
    }
}