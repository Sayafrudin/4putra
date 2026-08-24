<?php

namespace App\Http\Controllers;

use App\Models\DailyActivity;
use Illuminate\Support\Facades\Cache;

class DailyActivityController extends Controller
{
    public function index()
    {
        $activities = Cache::remember('public.daily_activities', 300, function () {
            return DailyActivity::select('id', 'title', 'title_en', 'description', 'description_en', 'activity_date', 'images')
                ->orderByDesc('activity_date')
                ->get();
        });

        return view('daily-activities', compact('activities'));
    }
}
