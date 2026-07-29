<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->string('wa_message_id', 100)->nullable()->after('pelanggan_id');
            $table->boolean('deleted_for_admin')->default(false)->after('is_forwarded');
            $table->boolean('deleted_for_pelanggan')->default(false)->after('deleted_for_admin');
        });
    }

    public function down(): void
    {
        Schema::table('percakapan', function (Blueprint $table) {
            $table->dropColumn(['wa_message_id', 'deleted_for_admin', 'deleted_for_pelanggan']);
        });
    }
};
