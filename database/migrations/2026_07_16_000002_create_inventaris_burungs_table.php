<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_burung', function (Blueprint $table) {
            $table->id();
            $table->string('nama_spesies', 100);
            $table->enum('fase', ['anakan', 'dewasa']);
            $table->decimal('harga', 12, 2)->nullable();
            $table->integer('stok')->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_burung');
    }
};
