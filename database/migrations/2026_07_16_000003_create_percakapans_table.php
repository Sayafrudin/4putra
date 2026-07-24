<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('percakapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->text('pesan_pengirim');
            $table->text('pesan_balasan')->nullable();
            $table->enum('sumber_balasan', ['groq_ai', 'apriori', 'manual', 'menu']);
            $table->timestamps();

            $table->index(['pelanggan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('percakapan');
    }
};
