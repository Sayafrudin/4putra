<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kandang aviary: Blok A/B/C (kecil 15/14/13), D/E (medium 20/20)
        Schema::create('kandangs', function (Blueprint $table) {
            $table->id();
            $table->string('blok', 1);
            $table->unsignedSmallInteger('nomor');
            $table->string('tipe', 10)->default('kecil'); // kecil|medium
            $table->timestamps();

            $table->unique(['blok', 'nomor']);
            $table->index('tipe');
        });

        // Burung individu (fondasi kandang, vaksinasi, telur, DNA)
        // Fase memakai istilah DB 'anakan' = "Baby" di antarmuka
        Schema::create('burungs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('spesies', 100);
            $table->string('fase', 20)->default('anakan'); // anakan|dewasa
            $table->string('gender', 10)->nullable(); // jantan|betina|null (hasil DNA)
            $table->foreignId('kandang_id')->nullable()->constrained('kandangs')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['spesies', 'fase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('burungs');
        Schema::dropIfExists('kandangs');
    }
};
