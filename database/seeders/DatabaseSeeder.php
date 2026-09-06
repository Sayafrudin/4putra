<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@4putra.com'],
            [
                'name' => 'Admin 4Putra',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->call([
            ChatbotInventarisSeeder::class,
            DailyActivitySeeder::class,
            FacilitySeeder::class,
            AboutSeeder::class,
        ]);
    }
}
