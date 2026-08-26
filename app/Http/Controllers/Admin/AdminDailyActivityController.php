<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyActivity;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDailyActivityController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $activities = Cache::remember('admin.daily_activities', 120, function () {
            return DailyActivity::select('id', 'title', 'title_en', 'description', 'description_en', 'video_url', 'activity_date', 'images')
                ->orderByDesc('activity_date')
                ->get();
        });

        return view('admin.daily-activities.index', compact('activities'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'activity_date' => 'required|date',
                'video_url' => 'nullable|url|max:255',
            ]);

            // Gambar di-upload langsung dari browser ke Cloudinary
            $cloudUrls = $this->validCloudUrls($request);

            if (empty($cloudUrls)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal unggah satu foto aktivitas',
                ], 422);
            }

            $activity = DailyActivity::create([
                'title' => $request->title,
                'title_en' => $request->title_en,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'video_url' => $this->nullableUrl($request->video_url),
                'activity_date' => $request->activity_date,
                'images' => $cloudUrls,
            ]);

            $this->logDataChange(
                $request, 'create',
                'Menambahkan aktivitas harian: '.$request->title,
                'Daily Activities',
                null,
                $activity->getAttributes(),
                $cloudUrls
            );

            Cache::forget('admin.daily_activities');
            Cache::forget('public.daily_activities');

            return response()->json([
                'success' => true,
                'message' => 'Aktivitas harian berhasil disimpan',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Daily activity store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $activity = DailyActivity::findOrFail($id);
            $oldValues = $activity->getOriginal();

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'activity_date' => 'required|date',
                'video_url' => 'nullable|url|max:255',
            ]);

            // Foto lama minus yang dihapus admin, plus upload baru
            $removed = array_filter((array) $request->input('removed_images', []));
            $images = array_values(array_diff($activity->images ?? [], $removed));
            $cloudNew = $this->validCloudUrls($request);
            $images = array_merge($images, $cloudNew);

            if (empty($images)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aktivitas minimal memiliki satu foto',
                ], 422);
            }

            $activity->update([
                'title' => $request->title,
                'title_en' => $request->title_en,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'video_url' => $this->nullableUrl($request->video_url),
                'activity_date' => $request->activity_date,
                'images' => $images,
            ]);

            $this->logDataChange(
                $request, 'update',
                'Memperbarui aktivitas harian: '.$activity->title,
                'Daily Activities',
                $oldValues,
                $activity->fresh()->getAttributes(),
                ! empty($cloudNew) ? $cloudNew : null
            );

            Cache::forget('admin.daily_activities');
            Cache::forget('public.daily_activities');

            return response()->json(['success' => true, 'message' => 'Aktivitas harian berhasil diperbarui!']);
        } catch (\Exception $e) {
            \Log::error('Daily activity update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $activity = DailyActivity::findOrFail($id);

        foreach ($activity->images ?? [] as $url) {
            $this->destroyCloudImage($url);
        }

        $this->logDataChange(
            request(), 'delete',
            'Menghapus aktivitas harian: '.$activity->title,
            'Daily Activities',
            $activity->getOriginal(),
            null,
            $activity->images ?? null
        );

        $activity->delete();

        Cache::forget('admin.daily_activities');
        Cache::forget('public.daily_activities');

        return redirect()->route('admin.daily-activities.index')->with('success', 'Aktivitas harian berhasil dihapus');
    }

    private function validCloudUrls(Request $request): array
    {
        return collect($request->input('cloudinary_urls', []))
            ->filter(fn ($url) => filter_var($url, FILTER_VALIDATE_URL))
            ->values()
            ->all();
    }

    private function nullableUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /**
     * Hapus aset dari Cloudinary jika URL cloud (best-effort).
     */
    private function destroyCloudImage(string $url): void
    {
        if (! str_starts_with($url, 'http')) {
            return;
        }

        try {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            $path = preg_replace('#^/[^/]+/image/upload/v\d+/#', '', $path);
            $publicId = trim(pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME), '/');

            Cloudinary::uploadApi()->destroy($publicId, ['resource_type' => 'image']);
        } catch (\Exception $e) {
            \Log::warning('Gagal hapus gambar Cloudinary: '.$e->getMessage());
        }
    }
}
