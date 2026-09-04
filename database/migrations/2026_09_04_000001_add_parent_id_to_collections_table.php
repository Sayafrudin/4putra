<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // INT UNSIGNED agar cocok dengan PK hasil rebuild
            // 2026_08_28_000003_fix_auto_random_pk_to_safe_int
            $table->unsignedInteger('parent_id')->nullable()->after('sort_order');
            $table->foreign('parent_id')
                ->references('id')->on('collections')
                ->nullOnDelete(); // varian naik jadi koleksi utama saat induk dihapus
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
