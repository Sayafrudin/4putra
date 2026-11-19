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
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Akun yang didaftarkan lewat Google (whitelist) hanya bisa login Google:
        // satu pintu per metode pendaftaran, password-nya acak dan tidak dipakai.
        $gUser = User::where('username', $data['username'])->first();
        if ($gUser && $gUser->google_id) {
            return back()->withErrors([
                'username' => 'Akun ini terdaftar via Google. Gunakan tombol "Masuk dengan Google".',
            ])->onlyInput('username');
        }

        // Tanpa remember-me (sesi ala MyBCA): sesi mati = login ulang saat
        // dibuka lagi. Tidak ada cookie remember yang diterbitkan.
        if (Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
            $request->session()->regenerate();

            // Update last_login_at langsung ke database
            User::where('id', Auth::id())->update(['last_login_at' => now()]);

            // Sesi tunggal: tiket baru menggantikan tiket lama (perangkat lama dikick)
            Auth::user()->terbitkanTiketSesi();

            // Non-admin tidak boleh ke /admin oleh intended (jebakan 403);
            // url.intended bisa berisi URL admin, jadi abaikan intended untuk non-admin.
            if (! Auth::user()->isAdmin()) {
                return redirect('/');
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
        }

        Log::warning('Percobaan login gagal', [
            'username' => $data['username'],
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }
}
