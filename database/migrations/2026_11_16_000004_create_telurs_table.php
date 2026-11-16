<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burung_id')->constrained('burungs')->cascadeOnDelete();
            $table->unsignedTinyInteger('sesi');          // sesi bertelur ke-berapa
            $table->unsignedTinyInteger('jumlah');        // jumlah butir
            $table->date('tanggal_diambil');              // awal penghitungan usia telur
            $table->string('diambil_oleh', 100);          // pekerja yang mengambil telur
            $table->string('kondisi', 200)->default('Kondisi telur baik');
            $table->string('status', 20)->default('diinkubasi'); // diinkubasi|menetas|dilepas
            $table->date('tanggal_menetas')->nullable();  // awal penghitungan umur anak
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['burung_id', 'sesi']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telurs');
    }
};
