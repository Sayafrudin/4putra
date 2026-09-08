<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Remember-me selalu aktif 14 hari (ala website besar): cookie remember
        // memungkinkan sesi admin dipulihkan otomatis tanpa login ulang saat
        // sesi web kedaluwarsa — klik "Perpanjang Sesi" tetap menyelamatkan sesi.
        // Logout (manual maupun timeout) menghapus cookie ini.
        Auth::setRememberDuration(14 * 24 * 60);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            // Update last_login_at langsung ke database
            $userId = Auth::id();
            User::where('id', $userId)->update(['last_login_at' => now()]);

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
        }

        Log::warning('Percobaan login gagal', [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }
}
