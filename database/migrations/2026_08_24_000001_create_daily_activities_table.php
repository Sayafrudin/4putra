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
        Schema::create('daily_activities', function (Blueprint $table) {
            // bigIncrements dipakai eksplisit: Blueprint global ter-bind ke
            // Colopl\TiDB\Schema\Blueprint yang id()-nya tak dikenal grammar mysql
            // (koneksi proyek = mysql), sehingga PK + AUTO_INCREMENT hilang.
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->date('activity_date');
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activities');
    }
};
