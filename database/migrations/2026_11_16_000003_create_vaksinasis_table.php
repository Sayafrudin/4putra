<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaksinasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burung_id')->constrained('burungs')->cascadeOnDelete();
            $table->string('jenis', 100);
            $table->date('tanggal_vaksin');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['burung_id', 'tanggal_vaksin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaksinasis');
    }
};
