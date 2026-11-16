<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telurs', function (Blueprint $table) {
            // Telur dicatat per PASANGAN induk dalam satu kandang (burung 1 × burung 2).
            // Nullable: catatan lama (satu induk) tetap sah tanpa backfill.
            $table->foreignId('burung_pasangan_id')->nullable()->after('burung_id')->constrained('burungs')->nullOnDelete();
            $table->index(['burung_id', 'burung_pasangan_id']);
        });
    }

    public function down(): void
    {
        Schema::table('telurs', function (Blueprint $table) {
            $table->dropIndex(['burung_id', 'burung_pasangan_id']);
            $table->dropConstrainedForeignId('burung_pasangan_id');
        });
    }
};
