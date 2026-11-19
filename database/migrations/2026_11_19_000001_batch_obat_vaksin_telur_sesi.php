<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 19 Nov 2026 (8 item):
 * 1. users.active_session_token — sesi tunggal (login baru = kick perangkat lama).
 * 2. vaksinasis.metode + dosis — obat & vaksinasi (suntik 1x/tahun, oral 3 bulan).
 * 3. telurs.butir_ke — monitoring telur per butir (1 baris = 1 butir); data lama
 *    yang tergabung (jumlah > 1) dipecah.
 * 4. Kandang blok C sebenarnya 12 kandang — hapus C-13.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_session_token', 60)->nullable()->after('password');
        });

        Schema::table('vaksinasis', function (Blueprint $table) {
            $table->string('metode', 10)->default('suntik')->after('jenis'); // suntik (1 tahun) | oral (3 bulan)
            $table->string('dosis', 100)->nullable()->after('metode');
        });

        Schema::table('telurs', function (Blueprint $table) {
            $table->unsignedTinyInteger('butir_ke')->default(1)->after('jumlah'); // butir ke-x dari sesi
        });

        // Pecah catatan telur lama tergabung: 1 baris jumlah=N → N baris jumlah=N butir_ke=1..N
        // (status/tanggal_menetas/kondisi disalin — info terbaik yang tersedia).
        $telurs = DB::table('telurs')->where('jumlah', '>', 1)->get();
        foreach ($telurs as $t) {
            DB::table('telurs')->where('id', $t->id)->update(['butir_ke' => 1]);
            for ($i = 2; $i <= (int) $t->jumlah; $i++) {
                DB::table('telurs')->insert([
                    'burung_id' => $t->burung_id,
                    'burung_pasangan_id' => $t->burung_pasangan_id,
                    'sesi' => $t->sesi,
                    'jumlah' => $t->jumlah,
                    'butir_ke' => $i,
                    'tanggal_diambil' => $t->tanggal_diambil,
                    'diambil_oleh' => $t->diambil_oleh,
                    'kondisi' => $t->kondisi,
                    'status' => $t->status,
                    'tanggal_menetas' => $t->tanggal_menetas,
                    'catatan' => $t->catatan,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                ]);
            }
        }

        // Kandang C-13 ternyata tidak ada di lapangan (blok C hanya 12 kandang).
        $c13 = DB::table('kandangs')->where('blok', 'C')->where('nomor', 13)->first();
        if ($c13 && DB::table('burungs')->where('kandang_id', $c13->id)->count() === 0) {
            DB::table('kandangs')->where('id', $c13->id)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_session_token');
        });
        Schema::table('vaksinasis', function (Blueprint $table) {
            $table->dropColumn(['metode', 'dosis']);
        });
        Schema::table('telurs', function (Blueprint $table) {
            $table->dropColumn('butir_ke');
        });
    }
};
