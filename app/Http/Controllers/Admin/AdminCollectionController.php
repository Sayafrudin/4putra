<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
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
            $filename = time().'_'.$request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('public/collections', $filename);
            $imagePath = $filename;
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
            $imagePath ? ['storage/collections/'.$imagePath] : null
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
            if ($collection->image_path) {
                Storage::delete('public/collections/'.$collection->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($collection->image_path) {
                Storage::delete('public/collections/'.$collection->image_path);
            }
            $filename = time().'_'.$request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('public/collections', $filename);
            $data['image_path'] = $filename;
            $imagePreviews[] = 'storage/collections/'.$filename;
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
            $imagePreviews[] = 'storage/collections/'.$collection->image_path;
            Storage::delete('public/collections/'.$collection->image_path);
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
}
