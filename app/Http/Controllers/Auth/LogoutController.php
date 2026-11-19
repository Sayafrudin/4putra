<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->last_login_at = null;
            // Sesi tunggal: tiket dibatalkan (sentinel 'revoked' — bukan null,
            // supaya sesi lain yang masih hidup TIDAK bisa mengklaim tiket kembali).
            $user->active_session_token = 'revoked';
            $user->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
