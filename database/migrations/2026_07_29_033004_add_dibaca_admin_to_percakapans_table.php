<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->boolean('dibaca_admin')->default(false)->after('terkirim');
        });
    }

    public function down(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->dropColumn('dibaca_admin');
        });
    }
};