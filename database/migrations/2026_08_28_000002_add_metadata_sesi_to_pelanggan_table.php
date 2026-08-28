<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan', function ($table) {
            $table->json('metadata_sesi')->nullable()->after('riwayat_konteks');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', function ($table) {
            $table->dropColumn('metadata_sesi');
        });
    }
};
