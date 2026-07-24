<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Konversi data existing: bungkus URL lama ke dalam format JSON array
        $rows = DB::table('achievements')->whereNotNull('external_link')->where('external_link', '!=', '')->get();
        foreach ($rows as $row) {
            // Skip jika sudah JSON
            if (json_decode($row->external_link) !== null) {
                continue;
            }
            DB::table('achievements')->where('id', $row->id)->update([
                'external_link' => json_encode([$row->external_link]),
            ]);
        }
    }

    public function down(): void
    {
        // Kembalikan dari JSON array ke URL tunggal (ambil elemen pertama)
        $rows = DB::table('achievements')->whereNotNull('external_link')->where('external_link', '!=', '')->get();
        foreach ($rows as $row) {
            $decoded = json_decode($row->external_link, true);
            if (is_array($decoded) && count($decoded) > 0) {
                DB::table('achievements')->where('id', $row->id)->update([
                    'external_link' => $decoded[0],
                ]);
            }
        }
    }
};