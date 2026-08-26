<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Support\Facades\Cache;

class FacilityController extends Controller
{
    public function index()
    {
        $facilities = Cache::remember('public.facilities', 300, function () {
            return Facility::select('id', 'title', 'title_en', 'category', 'category_en', 'description', 'description_en', 'video_urls', 'images')
                ->orderByDesc('id')
                ->get();
        });

        return view('facilities', compact('facilities'));
    }
}
