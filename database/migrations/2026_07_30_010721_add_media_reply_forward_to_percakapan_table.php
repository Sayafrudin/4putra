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
        Schema::table('percakapan', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->after('sumber_balasan')->constrained('percakapan')->nullOnDelete();
            $table->string('media_url', 500)->nullable()->after('reply_to_id');
            $table->string('media_type', 50)->nullable()->after('media_url'); // image, video, document, audio
            $table->boolean('is_forwarded')->default(false)->after('media_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['reply_to_id', 'media_url', 'media_type', 'is_forwarded']);
        });
    }
};
