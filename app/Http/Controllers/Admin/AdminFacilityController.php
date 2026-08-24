<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminFacilityController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $facilities = Cache::remember('admin.facilities', 120, function () {
            return Facility::select('id', 'title', 'title_en', 'category', 'category_en', 'description', 'description_en', 'video_url', 'images')
                ->orderByDesc('id')
                ->get();
        });

        return view('admin.facilities.index', compact('facilities'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'description' => 'required|string',
            ]);

            // Gambar di-upload langsung dari browser ke Cloudinary
            $cloudUrls = $this->validCloudUrls($request);

            if (empty($cloudUrls)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal unggah satu foto fasilitas',
                ], 422);
            }

            $facility = Facility::create([
                'title' => $request->title,
                'title_en' => $request->title_en,
                'category' => $request->category,
                'category_en' => $request->category_en,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'video_url' => $this->nullableUrl($request->video_url),
                'images' => $cloudUrls,
            ]);

            $this->logDataChange(
                $request, 'create',
                'Menambahkan fasilitas: '.$request->title,
                'Facilities',
                null,
                $facility->getAttributes(),
                $cloudUrls
            );

            Cache::forget('admin.facilities');
            Cache::forget('public.facilities');

            return response()->json([
                'success' => true,
                'message' => 'Fasilitas berhasil disimpan',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Facility store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $facility = Facility::findOrFail($id);
            $oldValues = $facility->getOriginal();

            $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'description' => 'required|string',
            ]);

            // Foto lama minus yang dihapus admin, plus upload baru
            $removed = array_filter((array) $request->input('removed_images', []));
            $images = array_values(array_diff($facility->images ?? [], $removed));
            $cloudNew = $this->validCloudUrls($request);
            $images = array_merge($images, $cloudNew);

            if (empty($images)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fasilitas minimal memiliki satu foto',
                ], 422);
            }

            $facility->update([
                'title' => $request->title,
                'title_en' => $request->title_en,
                'category' => $request->category,
                'category_en' => $request->category_en,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'video_url' => $this->nullableUrl($request->video_url),
                'images' => $images,
            ]);

            $this->logDataChange(
                $request, 'update',
                'Memperbarui fasilitas: '.$facility->title,
                'Facilities',
                $oldValues,
                $facility->fresh()->getAttributes(),
                ! empty($cloudNew) ? $cloudNew : null
            );

            Cache::forget('admin.facilities');
            Cache::forget('public.facilities');

            return response()->json(['success' => true, 'message' => 'Fasilitas berhasil diperbarui!']);
        } catch (\Exception $e) {
            \Log::error('Facility update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $facility = Facility::findOrFail($id);

        foreach ($facility->images ?? [] as $url) {
            $this->destroyCloudImage($url);
        }

        $this->logDataChange(
            request(), 'delete',
            'Menghapus fasilitas: '.$facility->title,
            'Facilities',
            $facility->getOriginal(),
            null,
            $facility->images ?? null
        );

        $facility->delete();

        Cache::forget('admin.facilities');
        Cache::forget('public.facilities');

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil dihapus');
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
