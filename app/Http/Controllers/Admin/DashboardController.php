<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function ping(Request $request)
    {
        $request->session()->put('last_activity', time());

        return response()->json([
            'ok' => true,
            'csrf' => csrf_token(),
        ]);
    }
}
