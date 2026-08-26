<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // guest(): simpan URL yang dituju agar intended() mengembalikan
            // user ke halaman yang sama setelah login (email & Google)
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
