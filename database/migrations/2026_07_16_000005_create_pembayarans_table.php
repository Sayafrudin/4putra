<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi_chatbot');
            $table->string('midtrans_txn_id', 100)->nullable();
            $table->string('metode', 50)->nullable();
            $table->decimal('nominal', 12, 2)->nullable();
            $table->enum('status', ['pending', 'settlement', 'expire', 'cancel', 'deny'])->default('pending');
            $table->json('raw_webhook')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
