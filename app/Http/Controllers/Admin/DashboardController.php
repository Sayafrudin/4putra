<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Widget monitoring farm: burung yang perlu vaksin ulang (booster bulanan)
        $vaksinJatuhTempo = \App\Models\Vaksinasi::vaksinTerakhirPerBurung()
            ->filter(fn ($v) => $v->sudah_jatuh_tempo);

        return view('admin.index', compact('vaksinJatuhTempo'));
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
