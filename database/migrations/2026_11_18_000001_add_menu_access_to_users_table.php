<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hak akses menu per akun admin (JSON array kunci menu).
            // NULL = akses penuh (akun lama & admin utama tanpa perubahan perilaku).
            $table->json('menu_access')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('menu_access');
        });
    }
};
