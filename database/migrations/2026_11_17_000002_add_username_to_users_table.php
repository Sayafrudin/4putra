<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Login dashboard memakai username (bukan email). Backfill otomatis dari
        // prefix email; bila bentrok, diberi angka berturut (syafrudintdm@gmail.com → syafrudintdm).
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
            $table->unique('username');
        });

        $terpakai = DB::table('users')->whereNotNull('username')->pluck('username')->all();
        foreach (DB::table('users')->whereNull('username')->get(['id', 'email']) as $u) {
            $base = substr(preg_replace('/[^a-zA-Z0-9._-]/', '', explode('@', $u->email ?? 'user')[0]) ?: 'user', 0, 50);
            $username = $base;
            $i = 1;
            while (in_array($username, $terpakai, true)) {
                $username = substr($base, 0, 46).($i++);
            }
            $terpakai[] = $username;
            DB::table('users')->where('id', $u->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
