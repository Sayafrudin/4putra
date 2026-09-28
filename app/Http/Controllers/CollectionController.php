<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Support\Facades\Cache;

class CollectionController extends Controller
{
    // ponytail: alias nama item transaksi.xlsx → nama koleksi (5 item); perluas bila isi xlsx berubah
    private const ALIAS_APRIORI = [
        'afgrey' => 'african grey',
        'bng macaw' => 'blue and gold macaw',
        'monk' => 'monk parakeet',
        'indian ring neck' => 'indian ringneck',
    ];

    public function index()
    {
        // v2: variant kini membawa images (multi-foto per variant)
        $collections = Cache::remember('public.collections.v2', 60 * 60, function () {
            return Collection::select('id', 'name', 'name_en', 'scientific_name', 'category', 'category_en', 'image_path', 'images', 'sort_order')
                ->whereNull('parent_id')
                ->with('variants:id,parent_id,name,name_en,scientific_name,image_path,images,sort_order')
                ->orderBy('sort_order')->get()->groupBy('category');
        });

        // Rekomendasi Apriori hanya di local (tugas akhir); produksi tidak pernah membaca file
        $rekomendasi = app()->environment('local') ? $this->rekomendasiApriori() : [];

        return view('collections', compact('collections', 'rekomendasi'));
    }

    /**
     * Burung hasil strong rules Apriori (public/chatbot/export-apriori.js →
     * storage/app/apriori-rekomendasi.json), dipetakan ke data koleksi.
     * Pool di-cache mentah (dua bahasa + URL gambar); pemilihan locale dilakukan per request.
     */
    private function rekomendasiApriori(): array
    {
        $file = storage_path('app/apriori-rekomendasi.json');
        if (! is_file($file)) {
            return [];
        }

        $pool = Cache::remember('public.apriori_pool', 60 * 60, function () use ($file) {
            $aturan = json_decode((string) file_get_contents($file), true)['aturan_kuat'] ?? [];
            if (! $aturan) {
                return [];
            }

            $koleksi = Collection::select('id', 'name', 'name_en', 'scientific_name', 'image_path')
                ->whereNull('parent_id')
                ->whereNotNull('image_path')
                ->get();

            $hasil = [];
            foreach ($aturan as $a) {
                // "Baby Afgrey" → "afgrey" → "african grey"
                $teks = mb_strtolower(trim(str_ireplace('baby', '', (string) ($a['consequents'] ?? ''))));
                $teks = self::ALIAS_APRIORI[$teks] ?? $teks;
                if ($teks === '') {
                    continue;
                }

                $c = $koleksi->first(fn ($k) => str_contains(mb_strtolower($k->name), $teks)
                    || str_contains(mb_strtolower((string) $k->name_en), $teks));

                if (! $c || isset($hasil[(string) $c->id])) {
                    continue;
                }

                $hasil[(string) $c->id] = [
                    'name' => $c->name,
                    'name_en' => (string) $c->name_en,
                    'scientific' => (string) $c->scientific_name,
                    'image' => str_starts_with($c->image_path, 'http')
                        ? str_replace('/upload/', '/upload/w_400,c_fill,q_auto,f_auto/', $c->image_path)
                        : asset('storage/collections/'.$c->image_path),
                    'confidence' => (string) ($a['confidence'] ?? ''),
                    'karena' => trim(preg_replace('/\bBaby\b\s*/i', '', (string) ($a['antecedents'] ?? ''))),
                ];

                if (count($hasil) >= 8) {
                    break;
                }
            }

            return array_values($hasil);
        });

        $isEn = app()->getLocale() === 'en';

        return array_map(fn ($r) => [
            'name' => $isEn && $r['name_en'] !== '' ? $r['name_en'] : $r['name'],
            'scientific' => $r['scientific'],
            'image' => $r['image'],
            'confidence' => $r['confidence'],
            'karena' => $r['karena'],
        ], $pool);
    }
}
