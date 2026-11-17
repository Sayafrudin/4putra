<?php

namespace App\Http\Controllers\Auth;

use App\Models\GoogleWhitelist;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        // Hanya Gmail di whitelist (dikelola admin di Kelola Akun User → Akses Google)
        // yang boleh masuk. Gmail tak dikenal ditolak dan TIDAK membuat akun.
        $whitelist = GoogleWhitelist::where('email', $email)->first();
        if (! $whitelist) {
            Log::warning('Login Google ditolak: email tidak terdaftar di whitelist', [
                'email' => $email,
                'ip' => request()->ip(),
            ]);

            return redirect()->route('login')->with('error', 'Email Google Anda tidak terdaftar. Hubungi admin 4PUTRA.');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update(['google_id' => $googleUser->getId()]);
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'username' => $this->usernameUnik($email),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(32)),
                    'role' => $whitelist->role,
                ]);
            }
        }

        // Role mengikuti whitelist (admin bisa promosi/demosi petinggi kapan saja).
        if ($user->role !== $whitelist->role) {
            $user->update(['role' => $whitelist->role]);
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

    private function usernameUnik(string $email): string
    {
        $base = substr(preg_replace('/[^a-zA-Z0-9._-]/', '', Str::of($email)->before('@')) ?: 'user', 0, 46);
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base.($i++);
        }

        return $username;
    }
}
