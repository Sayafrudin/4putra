<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::orderBy('sort_order')->get()->groupBy('category');

        return view('collections', compact('collections'));
    }
}
