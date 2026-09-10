<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AdminCollectionController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $collections = Cache::remember('admin.collections', 120, function () {
            return Collection::select('id', 'name', 'name_en', 'scientific_name', 'category', 'category_en', 'image_path', 'images', 'sort_order', 'parent_id')
                ->orderBy('category')->orderBy('sort_order')->get();
        });
        $parents = $collections->filter(fn ($c) => empty($c->parent_id))->values();

        return view('admin.collections.index', compact('collections', 'parents'));
    }

    /**
     * Aturan validasi varian: induk harus ada, bukan diri sendiri, dan masih
     * top-level (kedalaman maksimum 1 level). Satu induk boleh banyak varian.
     */
    private function variantRules(?Collection $self = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'parent_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($self) {
                    $parent = Collection::find($value);
                    if (! $parent) {
                        $fail('Induk koleksi tidak ditemukan.');
                    } elseif ($self && $parent->getKey() == $self->getKey()) {
                        $fail('Koleksi tidak dapat menjadi induk dirinya sendiri.');
                    } elseif (! empty($parent->parent_id)) {
                        $fail('Hanya koleksi utama yang dapat dipilih sebagai induk.');
                    }
                },
            ],
        ];
    }

    public function store(Request $request)
    {
        try {
            $request->validate($this->variantRules());

            // Gambar di-upload langsung dari browser ke Cloudinary
            // Multi-foto: semua URL masuk images[], foto pertama jadi cover image_path
            $images = array_values(array_filter((array) $request->input('cloudinary_urls', [])));

            Collection::create([
                'name' => $request->name,
                'name_en' => $request->name_en,
                'scientific_name' => $request->scientific_name,
                'category' => $request->category,
                'category_en' => $request->category_en,
                'image_path' => $images[0] ?? null,
                'images' => $images ?: null,
                'sort_order' => (int) ($request->sort_order ?: 0),
                'parent_id' => $request->filled('parent_id') ? $request->input('parent_id') : null,
            ]);

            $this->logDataChange(
                $request, 'create',
                'Menambahkan koleksi: '.$request->name,
                'Collections',
                null,
                ['name' => $request->name, 'category' => $request->category, 'scientific_name' => $request->scientific_name],
                $images ?: null
            );

            Cache::forget('admin.collections');
            Cache::forget('public.collections.v2');

            return response()->json(['success' => true, 'message' => 'Koleksi berhasil ditambahkan.']);
        } catch (ValidationException $e) {
            throw $e; // 422 JSON, jangan tertelan catch umum
        } catch (\Exception $e) {
            \Log::error('Collection store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Collection $collection)
    {
        try {
            $request->validate($this->variantRules($collection));

            $oldValues = $collection->getOriginal();
            $data = $request->only(['name', 'name_en', 'scientific_name', 'category', 'category_en', 'sort_order']);
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0) ?: 0;
            $imagePreviews = [];

            // Set parent hanya bila field dikirim (aman untuk klien lama)
            if ($request->has('parent_id')) {
                $data['parent_id'] = $request->filled('parent_id') ? $request->input('parent_id') : null;
            }

            // Galeri multi-foto: seed dari data lama, hapus per indeks, tambah upload baru,
            // lalu sinkronkan cover image_path = foto pertama
            $images = $collection->images ?? [];
            if (empty($images) && $collection->image_path) {
                $images = [$collection->image_path];
            }

            if ($request->input('remove_image') == '1') {
                foreach ($images as $url) {
                    if (str_starts_with($url, 'http')) {
                        $this->deleteCloudinaryImage($url);
                    }
                }
                $images = [];
            }

            foreach (array_map('intval', (array) $request->input('remove_images', [])) as $idx) {
                if (isset($images[$idx])) {
                    if (str_starts_with($images[$idx], 'http')) {
                        $this->deleteCloudinaryImage($images[$idx]);
                    }
                    unset($images[$idx]);
                }
            }
            $images = array_values($images);

            // Gambar di-upload langsung dari browser ke Cloudinary
            $newUrls = array_values(array_filter((array) $request->input('cloudinary_urls', [])));
            foreach ($newUrls as $url) {
                $images[] = $url;
            }
            $imagePreviews = $newUrls;

            $data['image_path'] = $images[0] ?? null;
            $data['images'] = $images ?: null;

            $collection->update($data);

            $this->logDataChange(
                $request, 'update',
                'Memperbarui koleksi: '.$collection->name,
                'Collections',
                $oldValues,
                $collection->fresh()->getAttributes(),
                ! empty($imagePreviews) ? $imagePreviews : null
            );

            Cache::forget('admin.collections');
            Cache::forget('public.collections.v2');

            return response()->json(['success' => true, 'message' => 'Koleksi berhasil diperbarui.']);
        } catch (ValidationException $e) {
            throw $e; // 422 JSON, jangan tertelan catch umum
        } catch (\Exception $e) {
            \Log::error('Collection update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Collection $collection)
    {
        $urls = $collection->images ?? [];
        if (empty($urls) && $collection->image_path) {
            $urls = [$collection->image_path];
        }
        foreach ($urls as $url) {
            if (str_starts_with($url, 'http')) {
                $this->deleteCloudinaryImage($url);
            }
        }

        $this->logDataChange(
            request(), 'delete',
            'Menghapus koleksi: '.$collection->name,
            'Collections',
            $collection->getOriginal(),
            null,
            $urls ?: null
        );

        $collection->delete();

        Cache::forget('admin.collections');
        Cache::forget('public.collections.v2');

        return redirect()->route('admin.collections.index')->with('success', 'Koleksi berhasil dihapus.');
    }

    /**
     * Hapus gambar dari Cloudinary berdasarkan URL.
     */
    protected function deleteCloudinaryImage(string $url): void
    {
        try {
            $parts = parse_url($url);
            $path = $parts['path'] ?? '';
            $path = preg_replace('#^/[^/]+/upload/(?:v\d+/)?#', '', $path);
            $publicId = preg_replace('/\.\w+$/', '', ltrim($path, '/'));
            Cloudinary::uploadApi()->destroy($publicId);
        } catch (\Exception $e) {
            \Log::warning('Gagal hapus gambar Cloudinary: '.$e->getMessage());
        }
    }
}
