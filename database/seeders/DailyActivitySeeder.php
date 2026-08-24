<?php

namespace Database\Seeders;

use App\Models\AchievementImage;
use App\Models\Collection;
use App\Models\DailyActivity;
use Illuminate\Database\Seeder;

class DailyActivitySeeder extends Seeder
{
    /**
     * Gambar dummy diambil dari aset yang sudah ada di database lokal
     * (Collection & AchievementImage). Fallback kompresi hanya dipakai
     * bila database belum memiliki aset http sama sekali.
     */
    private function imagePool(): \Illuminate\Support\Collection
    {
        return collect()
            ->merge(Collection::query()->whereNotNull('image_path')->where('image_path', 'like', 'http%')->pluck('image_path'))
            ->merge(AchievementImage::query()->where('image_path', 'like', 'http%')->pluck('image_path'))
            ->unique()
            ->values();
    }

    private function pick(int $index, \Illuminate\Support\Collection $pool): string
    {
        if ($pool->isEmpty()) {
            // Fallback: kompresi otomatis WebP, kualitas adaptif, maks lebar 1200px
            return 'https://res.cloudinary.com/demo/image/upload/f_webp,q_auto:good,w_1200,c_fill/sample.jpg';
        }

        return $pool[$index % $pool->count()];
    }

    public function run(): void
    {
        $pool = $this->imagePool();

        DailyActivity::updateOrCreate(
            ['title' => 'Rotasi Pakan Pagi dan Penataan Ulang Enrichment Kandang'],
            [
                'title_en' => 'Morning Feed Rotation and Cage Enrichment Rearrangement',
                'description' => 'Pagi dimulai pukul 06.30 dengan penimbangan porsi biji untuk setiap kandang. Sun conure menerima campuran millet dan biji bunga matahari kecil, sementara pasangan macaw mendapat tambahan kacang brazil sebagai sumber lemak. Setelah pakan dikonsumsi, sisa porsi ditimbang ulang dan dicatat pada lembar monitoring harian. Talang minum disterilkan, lalu mainan kayu kunyah yang mulai aus diganti agar aktivitas mengunyah tetap terjaga.',
                'description_en' => 'The morning starts at 06.30 with weighing seed portions for every cage. Sun conures receive a millet and small sunflower seed mix, while the macaw pairs get extra brazil nuts as a fat source. Once feeding is done, leftovers are weighed again and recorded on the daily monitoring sheet. Drinking troughs are sterilized, then worn chew toys are replaced to keep beak activity consistent.',
                'activity_date' => '2026-08-22',
                'images' => [
                    $this->pick(0, $pool),
                    $this->pick(1, $pool),
                    $this->pick(2, $pool),
                    $this->pick(3, $pool),
                    $this->pick(4, $pool),
                ],
            ]
        );

        DailyActivity::updateOrCreate(
            ['title' => 'Grooming Mingguan dan Pemeriksaan Kondisi Bulu'],
            [
                'title_en' => 'Weekly Grooming and Feather Condition Check',
                'description' => 'Sesi grooming mingguan berfokus pada pemeriksaan bulu primer dan sekunder. Setiap burung ditangkap satu per satu dengan handuk khusus, kemudian staf memeriksa tumbuhnya bulu baru serta bekas gigitan bulu pada area dada. African grey bernama Kavi menunjukkan regenerasi bulu sayap yang sehat setelah empat minggu program nutrisi tinggi protein. Kuku panjang dirapikan seperlunya tanpa menembus quick.',
                'description_en' => 'The weekly grooming session focuses on primary and secondary feather inspection. Each bird is caught one by one with a dedicated towel, then staff check new feather growth and old feather-plucking marks around the chest. Kavi the African grey shows healthy wing feather regeneration after four weeks on a high-protein nutrition plan. Overgrown nails are trimmed carefully without touching the quick.',
                'activity_date' => '2026-08-21',
                'images' => [
                    $this->pick(5, $pool),
                    $this->pick(6, $pool),
                    $this->pick(7, $pool),
                ],
            ]
        );

        DailyActivity::updateOrCreate(
            ['title' => 'Audit Kesehatan Rutin Bersama Dokter Hewan Mitra'],
            [
                'title_en' => 'Routine Health Audit with Partner Veterinarian',
                'description' => 'Dokter hewan mitra datang untuk audit kesehatan triwulan. Feces sample dari setiap kandang diambil dan diperiksa mikroskopis untuk mendeteksi parasit sedini mungkin. Bobot badan seluruh koleksi dicatat dan dibandingkan dengan grafik bulan lalu; tidak ada penurunan signifikan. Kandang karantina disiapkan untuk dua ekor lovebird yang memerlukan observasi lanjutan selama seminggu.',
                'description_en' => 'Our partner veterinarian came for the quarterly health audit. Fecal samples from each cage were collected and examined microscopically for early parasite detection. Body weights across the whole collection were recorded and compared with last month chart; no significant drop was found. The quarantine cage is prepared for two lovebirds requiring a week of further observation.',
                'activity_date' => '2026-08-19',
                'images' => [
                    $this->pick(8, $pool),
                    $this->pick(9, $pool),
                    $this->pick(10, $pool),
                    $this->pick(11, $pool),
                ],
            ]
        );
    }
}
