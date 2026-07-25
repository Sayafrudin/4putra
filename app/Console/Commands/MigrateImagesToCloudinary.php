<?php

namespace App\Console\Commands;

use App\Models\AchievementImage;
use App\Models\Collection;
use Cloudinary\Cloudinary as CloudinarySDK;
use Cloudinary\Transformation\Format;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToCloudinary extends Command
{
    protected $signature = 'images:migrate-cloudinary {--dry-run : Hanya tampilkan tanpa upload}';
    protected $description = 'Migrasi semua gambar lokal (collections & achievements) ke Cloudinary dan update database';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('=== Migrasi Gambar ke Cloudinary ===');
        $this->line('');

        // Inisialisasi Cloudinary SDK
        $cloudinary = app(CloudinarySDK::class);

        // Migrasi Collections
        $this->info('[1/2] Migrasi Collections...');
        $collections = Collection::whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->get();

        $colSuccess = 0;
        $colFail = 0;
        $colSkip = 0;

        foreach ($collections as $col) {
            if (str_starts_with($col->image_path, 'http')) {
                $this->line("  SKIP (sudah URL): {$col->name}");
                $colSkip++;
                continue;
            }

            $localPath = 'public/collections/' . $col->image_path;

            if (!Storage::exists($localPath)) {
                $this->warn("  FAIL (file tidak ada): {$col->image_path}");
                $colFail++;
                continue;
            }

            if ($dryRun) {
                $this->line("  DRY-RUN: {$col->image_path} → Cloudinary/4putra/collections/");
                $colSuccess++;
                continue;
            }

            try {
                $absolutePath = Storage::path($localPath);
                $uploadResult = $cloudinary->uploadApi()->upload($absolutePath, [
                    'folder' => '4putra/collections',
                    'public_id' => pathinfo($col->image_path, PATHINFO_FILENAME),
                    'resource_type' => 'image',
                ]);

                $cloudinaryUrl = $uploadResult['secure_url'];
                $col->update(['image_path' => $cloudinaryUrl]);

                $this->info("  OK: {$col->image_path} → {$cloudinaryUrl}");
                $colSuccess++;
            } catch (\Exception $e) {
                $this->error("  FAIL: {$col->image_path} — {$e->getMessage()}");
                $colFail++;
            }
        }

        $this->line('');
        $this->info("Collections selesai: {$colSuccess} berhasil, {$colFail} gagal, {$colSkip} skip");
        $this->line('');

        // Migrasi Achievement Images
        $this->info('[2/2] Migrasi Achievement Images...');
        $images = AchievementImage::whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->get();

        $achSuccess = 0;
        $achFail = 0;
        $achSkip = 0;

        foreach ($images as $img) {
            if (str_starts_with($img->image_path, 'http')) {
                $this->line("  SKIP (sudah URL): {$img->image_path}");
                $achSkip++;
                continue;
            }

            $localPath = 'public/achievements/' . $img->image_path;

            if (!Storage::exists($localPath)) {
                $this->warn("  FAIL (file tidak ada): {$img->image_path}");
                $achFail++;
                continue;
            }

            if ($dryRun) {
                $this->line("  DRY-RUN: {$img->image_path} → Cloudinary/4putra/achievements/");
                $achSuccess++;
                continue;
            }

            try {
                $absolutePath = Storage::path($localPath);
                $uploadResult = $cloudinary->uploadApi()->upload($absolutePath, [
                    'folder' => '4putra/achievements',
                    'public_id' => pathinfo($img->image_path, PATHINFO_FILENAME),
                    'resource_type' => 'image',
                ]);

                $cloudinaryUrl = $uploadResult['secure_url'];
                $img->update(['image_path' => $cloudinaryUrl]);

                $this->info("  OK: {$img->image_path} → {$cloudinaryUrl}");
                $achSuccess++;
            } catch (\Exception $e) {
                $this->error("  FAIL: {$img->image_path} — {$e->getMessage()}");
                $achFail++;
            }
        }

        $this->line('');
        $this->info("Achievement Images selesai: {$achSuccess} berhasil, {$achFail} gagal, {$achSkip} skip");
        $this->line('');
        $this->info('=== Migrasi Selesai ===');

        return self::SUCCESS;
    }
}
