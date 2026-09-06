<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PENTING: jangan pakai $table->id() — Blueprint package colopl/laravel-tidb
        // menggantinya menjadi bigInteger+autoRandom, padahal grammar TiDB modifier
        // hanya aktif di driver 'tidb' (koneksi kita 'mysql'), sehingga id jadi
        // bigint polos TANPA primary key/auto_increment. Eksplisit seperti ini
        // menghasilkan bigint unsigned auto_increment primary key di grammar MySQL.
        // Pengaturan halaman About Us (single-row): media hero (foto/video Cloudinary)
        Schema::create('about_page', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->string('media_type', 10)->default('image'); // image | video
            $table->string('media_path')->nullable();           // URL Cloudinary atau path asset lokal
            $table->timestamps();
        });

        // Tim leadership halaman About Us (multi-row)
        Schema::create('leaderships', function (Blueprint $table) {
            $table->bigInteger('id', true, true);
            $table->string('name');
            $table->string('role');                 // Role versi ID
            $table->string('role_en')->nullable();  // Role versi EN (fallback: role)
            $table->string('photo_path');           // URL Cloudinary atau path asset lokal
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderships');
        Schema::dropIfExists('about_page');
    }
};
