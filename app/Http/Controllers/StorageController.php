<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageController extends Controller
{
    /**
     * Serve file dari storage dengan proteksi referer.
     * Mencegah akses langsung ke URL gambar (buka di tab baru, save as).
     */
    public function serve(Request $request, $path)
    {
        $fullPath = 'public/' . $path;

        // Cek file exists
        if (!Storage::exists($fullPath)) {
            abort(404);
        }

        // Proteksi referer: tolak jika bukan dari domain sendiri
        $referer = $request->header('referer', '');
        $host = $request->getHost();

        // Domain yang diizinkan: host saat ini + APP_URL + domain tambahan
        $allowedDomains = [$host, 'localhost', '127.0.0.1'];

        // Tambahkan domain dari APP_URL config
        $appUrl = config('app.url');
        if ($appUrl) {
            $appUrlHost = parse_url($appUrl, PHP_URL_HOST);
            if ($appUrlHost) {
                $allowedDomains[] = $appUrlHost;
            }
        }

        // Domain produksi yang diizinkan
        $productionDomains = [
            '4putra.vercel.app',
            'admin4putra.vercel.app',
        ];
        $allowedDomains = array_unique(array_merge($allowedDomains, $productionDomains));

        // Cek apakah referer berasal dari domain yang diizinkan
        $refererAllowed = false;
        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            foreach ($allowedDomains as $domain) {
                if ($refererHost === $domain || str_ends_with($refererHost, '.' . $domain)) {
                    $refererAllowed = true;
                    break;
                }
            }
        }

        // Jika tidak ada referer atau referer tidak diizinkan, tolak
        if (!$refererAllowed) {
            return response('', 403);
        }

        // Serve file
        $absolutePath = Storage::path($fullPath);
        $mimeType = Storage::mimeType($fullPath);

        $response = new BinaryFileResponse($absolutePath);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Cache-Control', 'public, max-age=86400');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}