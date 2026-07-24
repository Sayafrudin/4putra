<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->boolean('terkirim')->default(true)->after('sumber_balasan');
        });
    }

    public function down(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->dropColumn('terkirim');
        });
    }
};
