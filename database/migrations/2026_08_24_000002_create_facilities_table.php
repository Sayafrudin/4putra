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
        Schema::create('facilities', function (Blueprint $table) {
            // bigIncrements dipakai eksplisit: Blueprint global ter-bind ke
            // Colopl\TiDB\Schema\Blueprint yang id()-nya tak dikenal grammar mysql
            // (koneksi proyek = mysql), sehingga PK + AUTO_INCREMENT hilang.
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('category');
            $table->string('category_en')->nullable();
            $table->index('category');
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->string('video_url')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
