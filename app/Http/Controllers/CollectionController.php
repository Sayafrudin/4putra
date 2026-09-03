<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Support\Facades\Cache;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Cache::remember('public.collections', 60 * 60, function () {
            return Collection::select('id', 'name', 'name_en', 'scientific_name', 'category', 'category_en', 'image_path', 'sort_order')
                ->whereNull('parent_id')
                ->with('variants:id,parent_id,name,name_en,scientific_name,image_path,sort_order')
                ->orderBy('sort_order')->get()->groupBy('category');
        });

        return view('collections', compact('collections'));
    }
}
