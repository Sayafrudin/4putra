<?php

namespace Database\Seeders;

use App\Models\AchievementImage;
use App\Models\Collection;
use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;

class FacilitySeeder extends Seeder
{
    /**
     * Gambar dummy diambil dari aset yang sudah ada di database lokal
     * (Collection & AchievementImage). Fallback kompresi hanya dipakai
     * bila database belum memiliki aset http sama sekali.
     */
    private function imagePool(): SupportCollection
    {
        return collect()
            ->merge(Collection::query()->whereNotNull('image_path')->where('image_path', 'like', 'http%')->pluck('image_path'))
            ->merge(AchievementImage::query()->where('image_path', 'like', 'http%')->pluck('image_path'))
            ->unique()
            ->values();
    }

    private function pick(int $index, SupportCollection $pool): string
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
        $imgs = fn (array $offsets) => collect($offsets)->map(fn ($o) => $this->pick($o, $pool))->all();

        Facility::updateOrCreate(
            ['title' => 'Kawasan Kandang Koloni Induk'],
            [
                'title_en' => 'Breeder Colony Cage Area',
                'category' => 'Area Penangkaran',
                'category_en' => 'Breeding Area',
                'description' => 'Kumpulan kandang koloni berukuran besar dengan ranting alami dan kotak sarang kayu. Setiap pasangan induk menempati satu kandang agar sifat teritorial tetap terjaga, dan jarak antar kandang dibuat renggang supaya jantan tidak saling memancing. Lantian dihamparkan serbuk kayu kering yang diganti dua hari sekali, sementara penutup sisi memberi privasi ekstra saat musim bertelur tiba.',
                'description_en' => 'A row of roomy colony cages fitted with natural branches and wooden nest boxes. Each breeding pair occupies one cage to keep territorial behavior in check, and the cages are spaced far apart so males cannot provoke one another. Floors are covered with dry wood shavings replaced every two days, while side covers give extra privacy once the laying season starts.',
                'video_urls' => null,
                'images' => $imgs([0, 1, 2, 3]),
            ]
        );

        Facility::updateOrCreate(
            ['title' => 'Ruang Nursery dan Inkubasi'],
            [
                'title_en' => 'Nursery and Incubation Room',
                'category' => 'Nursery & Inkubasi',
                'category_en' => 'Nursery & Incubation',
                'description' => 'Ruangan tertutup dengan suhu stabil 37 derajat Celsius dan kelembapan terkontrol menempatkan dua mesin inkubator sisi berlawanan. Telur diputar otomatis setiap empat jam sampai menjelang menetas, lalu dipindahkan ke broader berpengatur suhu sendiri. Kamera kecil terpasang di setiap rak sehingga staf bisa memantau kondisi tanpa membuka pintu terlalu sering.',
                'description_en' => 'A sealed room held at a stable 37 degrees Celsius with controlled humidity hosts two incubator machines on opposite sides. Eggs rotate automatically every four hours until close to hatching, then move to brooders with their own temperature settings. Small cameras are mounted on every rack so staff can monitor conditions without opening the door too often.',
                'video_urls' => ['https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
                'images' => $imgs([4, 5]),
            ]
        );

        Facility::updateOrCreate(
            ['title' => 'Klinik Kesehatan Avian'],
            [
                'title_en' => 'Avian Health Clinic',
                'category' => 'Medis',
                'category_en' => 'Medical',
                'description' => 'Klinik kecil lengkap dengan meja pemeriksaan stainless, lampu pemanas, dan lemari obat khusus avian. Dokter hewan mitra datang dua kali sebulan untuk pemeriksaan berkala, sementara staf yang terlatih menangani tindakan ringan seperti pembersihan luka dan pemberian cairan. Kandang karantina berdiri terpisah pada jarak aman dari area utama.',
                'description_en' => 'A compact clinic equipped with a stainless examination table, a heat lamp, and a cabinet of avian specific medicine. Our partner veterinarian visits twice a month for scheduled checks, while trained staff handle minor procedures such as wound cleaning and fluid support. The quarantine cage stands apart at a safe distance from the main area.',
                'video_urls' => null,
                'images' => $imgs([6, 7, 8]),
            ]
        );

        Facility::updateOrCreate(
            ['title' => 'Gudang Pakan dan Area Penyiapan Gizi'],
            [
                'title_en' => 'Feed Storage and Nutrition Prep Area',
                'category' => 'Umum',
                'category_en' => 'General',
                'description' => 'Gudang pakan memakai rak berlabel untuk setiap varian biji, dilengkapi ruang penyiapan campuran buah segar dan area cuci kandang bertekanan tinggi. Seluruh alur dirancang satu arah dari penyimpanan menuju penyiapan sehingga risiko kontaminasi silang tetap rendah. Stok dicatat harian dan pemesanan ulang dipicu otomatis saat ambang minimum tersentuh.',
                'description_en' => 'The feed store uses labeled racks for every seed variant, complete with a fresh fruit prep room and a high pressure cage washing bay. The whole flow runs one way from storage to preparation so the risk of cross contamination stays low. Stock is recorded daily and reorders trigger automatically once the minimum threshold is hit.',
                'video_urls' => null,
                'images' => $imgs([9, 10, 11, 12]),
            ]
        );
    }
}
