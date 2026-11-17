<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        // URI callback mengikuti host saat ini: admin subdomain, domain publik,
        // maupun localhost semua jalan tanpa env terpisah (Console Google sudah
        // mendaftarkan URI tiap domain).
        $uri = config('services.google.redirect') ?: request()->getSchemeAndHttpHost().'/auth/google/callback';

        return Socialite::driver('google')->redirectUrl($uri)->redirect();
    }

    public function callback()
    {
        $uri = config('services.google.redirect') ?: request()->getSchemeAndHttpHost().'/auth/google/callback';
        $googleUser = Socialite::driver('google')->redirectUrl($uri)->user();
        $email = strtolower($googleUser->getEmail());

        // Whitelist = tabel users: hanya email yang sudah terdaftar (dibuat
        // admin via Kelola Akun User) yang boleh masuk. Google tidak pernah
        // membuat akun baru — Gmail asing ditolak tanpa menyimpan apa pun.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            Log::warning('Login Google ditolak: email tidak terdaftar', [
                'email' => $email,
                'ip' => request()->ip(),
            ]);

            return redirect()->route('login')->with('error', 'Akses ditolak. Email Anda belum terdaftar sebagai admin 4PUTRA.');
        }

        if (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        // Remember-me 14 hari (ala website besar) agar sesi admin dapat
        // dipulihkan otomatis tanpa login ulang. Logout menghapusnya.
        Auth::setRememberDuration(14 * 24 * 60);
        Auth::login($user, true);

        // Regenerate session untuk keamanan
        request()->session()->regenerate();

        // Update last_login_at langsung ke database
        User::where('id', $user->id)->update(['last_login_at' => now()]);

        // Non-admin diarahkan ke beranda, bukan /admin (jebakan 403).
        if (! $user->isAdmin()) {
            return redirect('/');
        }

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }
}
