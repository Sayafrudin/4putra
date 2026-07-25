<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Support\Facades\Cache;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Cache::remember('public.collections', 300, function () {
            return Collection::orderBy('sort_order')->get()->groupBy('category');
        });

        return view('collections', compact('collections'));
    }
}
