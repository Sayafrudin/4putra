<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('title_highlight_en')->nullable()->after('title_highlight');
            $table->string('video_file')->nullable()->after('video_url');
            $table->string('external_link')->nullable()->after('video_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['title_highlight_en', 'video_file', 'external_link']);
        });
    }
};
