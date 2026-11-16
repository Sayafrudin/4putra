<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fitur skripsi (chatbot WhatsApp) local-only: URL langsung di produksi → 404,
 * bukan sekadar disembunyikan dari menu. Di luar produksi (local/testing) tetap hidup.
 */
class LocalOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(404);
        }

        return $next($request);
    }
}
