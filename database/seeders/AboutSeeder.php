<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use App\Models\Leadership;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    /**
     * Data awal About Us: media hero default + 3 leadership yang sebelumnya
     * hardcoded di about.blade.php / lang file.
     */
    public function run(): void
    {
        AboutPage::firstOrCreate(
            ['id' => 1],
            ['media_type' => 'image', 'media_path' => 'img/achievement1.jpg']
        );

        Leadership::insert([
            [
                'name' => 'Rachmad Hidayat',
                'role' => 'Pemilik',
                'role_en' => 'Owner',
                'photo_path' => 'img/manager.png',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dedy Murya Budi, SE',
                'role' => 'Direktur',
                'role_en' => 'Director',
                'photo_path' => 'img/direktur.png',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Syafrudin Hendra Lumanto',
                'role' => 'Manajer Pengembangan Bisnis',
                'role_en' => 'Business Development Manager',
                'photo_path' => 'img/komisaris.png',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
