<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leadership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminAboutController extends Controller
{
    use LogsActivity;

    // =============================================
    // MEDIA HERO ABOUT US (single-row)
    // =============================================
    public function updateMedia(Request $request)
    {
        try {
            $request->validate([
                'media_type' => 'required|in:image,video,embed',
                'media_path' => 'required|string',
            ]);

            $about = \App\Models\AboutPage::current();
            $old = $about->getOriginal();

            $about->media_type = $request->media_type;
            $about->media_path = $request->media_path;
            $about->save();

            $this->logDataChange(
                $request, 'update',
                'Mengganti media hero About Us (' . $request->media_type . ')',
                'About Us',
                $old,
                $about->getAttributes()
            );

            return response()->json(['success' => true, 'message' => 'Media About Us berhasil diperbarui']);
        } catch (\Exception $e) {
            \Log::error('About media update error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Gagal memperbarui media: ' . $e->getMessage()], 500);
        }
    }

    // =============================================
    // LEADERSHIP CRUD
    // =============================================
    public function storeLeader(Request $request)
    {
        try {
            $data = $this->validatedLeader($request);

            $leader = Leadership::create($data);

            $this->logDataChange(
                $request, 'create',
                'Menambahkan leadership: ' . $data['name'],
                'About Us',
                null,
                $leader->getAttributes()
            );

            Cache::forget('about.leaderships');

            return response()->json(['success' => true, 'message' => 'Leadership berhasil ditambahkan']);
        } catch (\Exception $e) {
            \Log::error('Leadership store error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function updateLeader(Request $request, $id)
    {
        try {
            $leader = Leadership::findOrFail($id);
            $old = $leader->getOriginal();

            $leader->update($this->validatedLeader($request));

            $this->logDataChange(
                $request, 'update',
                'Memperbarui leadership: ' . $leader->name,
                'About Us',
                $old,
                $leader->fresh()->getAttributes()
            );

            Cache::forget('about.leaderships');

            return response()->json(['success' => true, 'message' => 'Leadership berhasil diperbarui']);
        } catch (\Exception $e) {
            \Log::error('Leadership update error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
        }
    }

    public function destroyLeader(Request $request, $id)
    {
        $leader = Leadership::findOrFail($id);

        $this->logDataChange(
            $request, 'delete',
            'Menghapus leadership: ' . $leader->name,
            'About Us',
            $leader->getOriginal(),
            null
        );

        $leader->delete();

        Cache::forget('about.leaderships');

        return response()->json(['success' => true, 'message' => 'Leadership berhasil dihapus']);
    }

    private function validatedLeader(Request $request): array
    {
        $request->validate([
            'name' => 'required|string',
            'role' => 'required|string',
        ]);

        return [
            'name' => $request->name,
            'role' => $request->role,
            'role_en' => $request->role_en ?: null,
            'photo_path' => $request->photo_path,
            'sort_order' => (int) $request->sort_order ?: 0,
        ];
    }
}
