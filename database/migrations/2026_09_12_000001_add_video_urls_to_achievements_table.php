<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $t) {
            $t->json('video_urls')->nullable()->after('video_file');
        });

        // Backfill: video_file tunggal lama menjadi array berisi satu elemen
        DB::table('achievements')
            ->whereNotNull('video_file')
            ->where('video_file', '!=', '')
            ->update(['video_urls' => DB::raw('JSON_ARRAY(video_file)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('achievements')
            ->whereNotNull('video_urls')
            ->update(['video_file' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(video_urls, '$[0]'))")]);

        Schema::table('achievements', function (Blueprint $t) {
            $t->dropColumn('video_urls');
        });
    }
};