<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('burungs', function (Blueprint $table) {
            $table->string('nomor_dna', 50)->nullable()->after('gender');
            $table->index('nomor_dna');
        });
    }

    public function down(): void
    {
        Schema::table('burungs', function (Blueprint $table) {
            $table->dropIndex(['nomor_dna']);
            $table->dropColumn('nomor_dna');
        });
    }
};
