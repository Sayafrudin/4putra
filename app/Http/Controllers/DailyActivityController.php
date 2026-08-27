<?php

namespace App\Http\Controllers;

use App\Models\DailyActivity;
use Illuminate\Support\Facades\Cache;

class DailyActivityController extends Controller
{
    public function index()
    {
        $activities = Cache::remember('public.daily_activities', 60 * 60, function () {
            return DailyActivity::select('id', 'title', 'title_en', 'description', 'description_en', 'video_urls', 'activity_date', 'images')
                ->orderByDesc('activity_date')
                ->get();
        });

        return view('daily-activities', compact('activities'));
    }
}
