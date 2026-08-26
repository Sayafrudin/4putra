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
        foreach (['daily_activities', 'facilities'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('video_urls')->nullable()->after('description_en');
            });

            // Backfill: URL tunggal lama menjadi array berisi satu elemen
            DB::table($table)
                ->whereNotNull('video_url')
                ->where('video_url', '!=', '')
                ->update(['video_urls' => DB::raw('JSON_ARRAY(video_url)')]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('video_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['daily_activities', 'facilities'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('video_url')->nullable()->after('description_en');
            });

            DB::table($table)
                ->whereNotNull('video_urls')
                ->update(['video_url' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(video_urls, '$[0]'))")]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('video_urls');
            });
        }
    }
};
