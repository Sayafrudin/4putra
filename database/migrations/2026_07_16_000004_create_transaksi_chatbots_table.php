<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_chatbot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggan');
            $table->foreignId('inventaris_id')->nullable()->constrained('inventaris_burung');
            $table->decimal('nominal_dp', 12, 2)->nullable();
            $table->decimal('total_harga', 12, 2)->nullable();
            $table->enum('status', ['pending', 'paid', 'expired', 'cancelled'])->default('pending');
            $table->string('midtrans_order_id', 100)->unique()->nullable();
            $table->string('qr_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_chatbot');
    }
};
