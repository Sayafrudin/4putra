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

        // Pintu tunggal angka agregat (tersaring per hak akses + cache 60 detik)
        $stats = \App\Support\DashboardStats::untuk(auth()->user());
        $usersOnline = \App\Models\User::where('last_active_at', '>=', now()->subMinutes(5))->count();

        return view('admin.index', compact('vaksinJatuhTempo', 'stats', 'usersOnline'));
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
