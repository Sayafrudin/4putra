<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesi tunggal: login baru (perangkat apa pun) menggantikan tiket sesi akun.
 * Perangkat lama yang datang lagi dengan tiket basi di-logout paksa dengan
 * pemberitahuan jelas — ala Google/Instagram (opsi A yang disetujui bos).
 * - Request biasa → redirect ke login + banner "telah login di perangkat lain".
 * - AJAX → 401 JSON (handler error yang sudah ada menampilkan toast).
 * Multi-tab browser yang sama aman: satu cookie = satu sesi = satu tiket.
 */
class SingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $tiketSesi = $request->session()->get('sesi_token');
            $tiketDb = Auth::user()->active_session_token;

            if ($tiketDb === null) {
                // Belum ada tiket (akun baru / sesi pra-fitur) → sesi valid ini
                // mengklaim tiket sekali. Klaim = write DB 1x, request berikutnya cocok.
                Auth::user()->terbitkanTiketSesi();
            } elseif (
                $tiketDb === 'revoked' ||
                (string) $tiketSesi !== (string) $tiketDb
            ) {
                // Tiket basi (login baru di perangkat lain) ATAU tiket sudah
                // direvoke (logout) — keduanya berarti sesi ini bukan lagi yang teraktif.
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $pesan = 'Akun Anda telah login di perangkat lain. Sesi ini diakhiri otomatis.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => $pesan,
                        'sesi_digantikan' => true,
                    ], 401);
                }

                return redirect()->route('login')->with('sesi_digantikan', $pesan);
            }
        }

        return $next($request);
    }
}
