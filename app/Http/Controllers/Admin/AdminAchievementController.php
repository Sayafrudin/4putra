<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AchievementImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAchievementController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $achievements = Achievement::with('images')->latest()->get();

        return view('admin.achievements.index', compact('achievements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'year' => 'required|integer',
            'date' => 'required|date',
            'description' => 'required|string',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,webm,avi|max:51200',
        ]);

        // Proses external link: pastikan format JSON array
        $externalLinks = $request->external_link;
        if (is_string($externalLinks)) {
            $externalLinks = json_decode($externalLinks, true) ?: [];
        }
        if (!is_array($externalLinks)) {
            $externalLinks = $externalLinks ? [$externalLinks] : [];
        }
        $externalLinks = array_values(array_filter($externalLinks, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

        // Proses video URL: pastikan format JSON array
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

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension());

                if (in_array($ext, ['mp4', 'mov', 'webm', 'avi'])) {
                    $file->storeAs('public/achievements/videos', $filename);
                    $achievement->update(['video_file' => $filename]);
                } else {
                    $file->storeAs('public/achievements', $filename);
                    AchievementImage::create([
                        'achievement_id' => $achievement->id,
                        'image_path' => $filename,
                    ]);
                }
            }
        }

        $this->logDataChange(
            $request, 'create',
            'Menambahkan pencapaian: '.$request->title,
            'Achievements',
            null,
            $achievement->getAttributes(),
            $achievement->images->pluck('image_path')->map(fn ($p) => 'storage/achievements/'.$p)->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Data portofolio berhasil disimpan',
        ], 200);
    }

    public function destroy($id)
    {
        $achievement = Achievement::findOrFail($id);

        $imagePreviews = [];
        foreach ($achievement->images as $image) {
            $imagePreviews[] = 'storage/achievements/'.$image->image_path;
            Storage::delete('public/achievements/'.$image->image_path);
        }
        if ($achievement->video_file) {
            Storage::delete('public/achievements/videos/'.$achievement->video_file);
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

        return redirect()->route('admin.achievements.index')->with('success', 'Data berhasil dihapus');
    }

    public function update(Request $request, $id)
    {
        $achievement = Achievement::findOrFail($id);
        $oldValues = $achievement->getOriginal();

        // Proses external link: pastikan format JSON array
        $externalLinks = $request->external_link;
        if (is_string($externalLinks)) {
            $externalLinks = json_decode($externalLinks, true) ?: [];
        }
        if (!is_array($externalLinks)) {
            $externalLinks = $externalLinks ? [$externalLinks] : [];
        }
        $externalLinks = array_values(array_filter($externalLinks, fn ($l) => filter_var($l, FILTER_VALIDATE_URL)));

        // Proses video URL: pastikan format JSON array
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
            Storage::delete('public/achievements/videos/'.$achievement->video_file);
            $achievement->update(['video_file' => null]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension());

                if (in_array($ext, ['mp4', 'mov', 'webm', 'avi'])) {
                    if ($achievement->video_file) {
                        Storage::delete('public/achievements/videos/'.$achievement->video_file);
                    }
                    $file->storeAs('public/achievements/videos', $filename);
                    $achievement->update(['video_file' => $filename]);
                } else {
                    $file->storeAs('public/achievements', $filename);
                    AchievementImage::create(['achievement_id' => $achievement->id, 'image_path' => $filename]);
                    $imagePreviews[] = 'storage/achievements/'.$filename;
                }
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

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui!']);
    }
}