<?php

namespace Database\Seeders;

use App\Models\InventarisBurung;
use Illuminate\Database\Seeder;

class ChatbotInventarisSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_spesies' => 'African Grey', 'fase' => 'anakan', 'harga' => 12000000, 'stok' => 3, 'deskripsi' => 'Baby African Grey umur 3-4 bulan, sudah loloh mandiri, sehat dan aktif.'],
            ['nama_spesies' => 'African Grey', 'fase' => 'dewasa', 'harga' => 18000000, 'stok' => 2, 'deskripsi' => 'African Grey dewasa umur 1-2 tahun, sudah jinak dan bisa bicara.'],
            ['nama_spesies' => 'Sun Conure', 'fase' => 'anakan', 'harga' => 2500000, 'stok' => 5, 'deskripsi' => 'Baby Sun Conure umur 2-3 bulan, warna cerah, sehat.'],
            ['nama_spesies' => 'Sun Conure', 'fase' => 'dewasa', 'harga' => 3500000, 'stok' => 3, 'deskripsi' => 'Sun Conure dewasa, jinak dan suka berinteraksi.'],
            ['nama_spesies' => 'BNG Macaw', 'fase' => 'anakan', 'harga' => 25000000, 'stok' => 2, 'deskripsi' => 'Baby BNG Macaw umur 3-4 bulan, sehat dan sudah loloh mandiri.'],
            ['nama_spesies' => 'BNG Macaw', 'fase' => 'dewasa', 'harga' => 35000000, 'stok' => 1, 'deskripsi' => 'BNG Macaw dewasa, jinak dan bulu sempurna.'],
            ['nama_spesies' => 'Monk Parakeet', 'fase' => 'anakan', 'harga' => 1800000, 'stok' => 4, 'deskripsi' => 'Baby Monk Parakeet umur 2-3 bulan, aktif dan sehat.'],
            ['nama_spesies' => 'Monk Parakeet', 'fase' => 'dewasa', 'harga' => 2500000, 'stok' => 2, 'deskripsi' => 'Monk Parakeet dewasa, sudah jinak dan bisa beberapa kata.'],
            ['nama_spesies' => 'Indian Ring Neck', 'fase' => 'anakan', 'harga' => 2000000, 'stok' => 4, 'deskripsi' => 'Baby IRN umur 2-3 bulan, warna hijau standar, sehat.'],
            ['nama_spesies' => 'Indian Ring Neck', 'fase' => 'dewasa', 'harga' => 3000000, 'stok' => 3, 'deskripsi' => 'IRN dewasa, sudah jinak dan bisa bicara beberapa kata.'],
        ];

        foreach ($data as $item) {
            InventarisBurung::create($item);
        }
    }
}
