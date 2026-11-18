<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transfer dana antar-tab = dua baris berpasangan; hapus satu sisi
        // menghapus keduanya agar buku keuangan tidak pernah timpang.
        Schema::table('keuangans', function (Blueprint $table) {
            $table->string('transfer_id', 36)->nullable()->after('financial_tab_id');
            $table->index('transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->dropIndex(['transfer_id']);
            $table->dropColumn('transfer_id');
        });
    }
};
