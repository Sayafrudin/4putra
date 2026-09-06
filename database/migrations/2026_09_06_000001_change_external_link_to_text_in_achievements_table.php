<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            // JSON array {url, label} bisa melebihi 255 karakter VARCHAR lama
            $table->text('external_link')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('external_link')->nullable()->change();
        });
    }
};
