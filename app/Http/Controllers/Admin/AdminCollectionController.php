<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminCollectionController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $collections = Collection::orderBy('category')->orderBy('sort_order')->get();

        return view('admin.collections.index', compact('collections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $uploaded = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => '4putra/collections',
            ]);
            $imagePath = $uploaded->getSecurePath();
        }

        Collection::create([
            'name' => $request->name,
            'name_en' => $request->name_en,
            'scientific_name' => $request->scientific_name,
            'category' => $request->category,
            'category_en' => $request->category_en,
            'image_path' => $imagePath,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        $this->logDataChange(
            $request, 'create',
            'Menambahkan koleksi: '.$request->name,
            'Collections',
            null,
            ['name' => $request->name, 'category' => $request->category, 'scientific_name' => $request->scientific_name],
            $imagePath ? [$imagePath] : null
        );

        return response()->json(['success' => true, 'message' => 'Koleksi berhasil ditambahkan.']);
    }

    public function update(Request $request, Collection $collection)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $oldValues = $collection->getOriginal();
        $data = $request->only(['name', 'name_en', 'scientific_name', 'category', 'category_en', 'sort_order']);
        $imagePreviews = [];

        if ($request->input('remove_image') == '1') {
            // Hapus gambar lama dari Cloudinary
            if ($collection->image_path && str_starts_with($collection->image_path, 'http')) {
                $this->deleteCloudinaryImage($collection->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $uploaded = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => '4putra/collections',
            ]);
            $data['image_path'] = $uploaded->getSecurePath();
            $imagePreviews[] = $data['image_path'];
        }

        $collection->update($data);

        $this->logDataChange(
            $request, 'update',
            'Memperbarui koleksi: '.$collection->name,
            'Collections',
            $oldValues,
            $collection->fresh()->getAttributes(),
            ! empty($imagePreviews) ? $imagePreviews : null
        );

        return response()->json(['success' => true, 'message' => 'Koleksi berhasil diperbarui.']);
    }

    public function destroy(Collection $collection)
    {
        $imagePreviews = [];
        if ($collection->image_path) {
            $imagePreviews[] = $collection->image_path;
            // Hapus gambar dari Cloudinary
            if (str_starts_with($collection->image_path, 'http')) {
                $this->deleteCloudinaryImage($collection->image_path);
            }
        }

        $this->logDataChange(
            request(), 'delete',
            'Menghapus koleksi: '.$collection->name,
            'Collections',
            $collection->getOriginal(),
            null,
            ! empty($imagePreviews) ? $imagePreviews : null
        );

        $collection->delete();

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
            $path = preg_replace('#^/image/upload/v\d+/#', '', $path);
            $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
            $publicId = trim($publicId, '/');
            \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::destroy($publicId);
        } catch (\Exception $e) {
            \Log::warning('Gagal hapus gambar Cloudinary: ' . $e->getMessage());
        }
    }
}
