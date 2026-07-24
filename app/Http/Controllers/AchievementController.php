<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AchievementImage;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = Achievement::with('images')->latest()->get();

        return view('admin.achievements.index', compact('achievements'));
    }

    public function publicIndex()
    {
        $achievements = Achievement::with('images')
            ->orderBy('year', 'desc')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('year');

        return view('achievements', compact('achievements'));
    }

    public function destroyImage(AchievementImage $image)
    {
        if (Storage::disk('public')->exists('achievements/'.$image->image_path)) {
            Storage::disk('public')->delete('achievements/'.$image->image_path);
        }

        $image->delete();

        return response()->json(['success' => true, 'message' => 'Foto berhasil dihapus.']);
    }
}
