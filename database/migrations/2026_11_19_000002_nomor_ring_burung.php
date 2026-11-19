<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item 9: nomor ring kaki burung (opsional) — ditampilkan di card/chip.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('burungs', function (Blueprint $table) {
            $table->string('nomor_ring', 50)->nullable()->after('nomor_dna');
        });
    }

    public function down(): void
    {
        Schema::table('burungs', function (Blueprint $table) {
            $table->dropColumn('nomor_ring');
        });
    }
};
