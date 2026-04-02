<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MediaLibraryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportS3Media extends Command
{
    protected $signature = 'media:import-s3 {--fresh : Clear existing records first}';
    protected $description = 'Import existing S3 images into the Spatie media library';

    public function handle(): int
    {
        $this->info('Scanning S3 bucket for images...');

        if ($this->option('fresh')) {
            $this->warn('Clearing existing media library records...');
            Media::query()->delete();
            MediaLibraryItem::query()->delete();
        }

        $disk = Storage::disk('s3');
        $bucket = config('filesystems.disks.s3.bucket');
        $baseUrl = "https://{$bucket}.s3.amazonaws.com";
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imported = 0;
        $skipped = 0;

        // Scan root and known directories
        $directories = ['', 'general'];

        foreach ($directories as $dir) {
            $this->info("Scanning: " . ($dir ?: 'root') . "/");

            try {
                $files = $disk->files($dir);
            } catch (\Exception $e) {
                $this->warn("  Could not scan '{$dir}': " . $e->getMessage());
                continue;
            }

            // Filter to images only, skip resized variants
            $imageFiles = collect($files)->filter(function ($file) use ($imageExtensions) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, $imageExtensions)) {
                    return false;
                }
                // Skip Botble resized variants like -600x480
                if (preg_match('/-\d+x\d+\./', $file)) {
                    return false;
                }
                return true;
            });

            $bar = $this->output->createProgressBar($imageFiles->count());

            foreach ($imageFiles as $file) {
                $url = "{$baseUrl}/{$file}";
                $filename = basename($file);

                // Skip if already imported (check by file_name in media table)
                if (Media::where('file_name', $filename)->exists()) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                try {
                    // Create container
                    $item = MediaLibraryItem::create([
                        'user_id' => 1,
                        'name' => $filename,
                    ]);

                    // Register the existing S3 file with Spatie (no re-upload)
                    $mimeMap = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                    ];
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $size = 0;
                    try {
                        $size = $disk->size($file);
                    } catch (\Exception $e) {
                    }

                    // Create the media record directly pointing to existing S3 file
                    $media = new Media();
                    $media->model_type = MediaLibraryItem::class;
                    $media->model_id = $item->id;
                    $media->collection_name = 'default';
                    $media->name = pathinfo($filename, PATHINFO_FILENAME);
                    $media->file_name = $filename;
                    $media->mime_type = $mimeMap[$ext] ?? 'image/' . $ext;
                    $media->disk = 's3';
                    $media->size = $size;
                    $media->manipulations = [];
                    $media->custom_properties = ['source' => 's3-import'];
                    $media->generated_conversions = [];
                    $media->responsive_images = [];
                    $media->order_column = $imported + 1;

                    // Set the correct path on S3
                    // Spatie stores files as: {model_id}/{file_name} by default
                    // But our files are already at root or in directories
                    // We need to tell Spatie where the file actually is
                    $media->save();

                    // Now update the media record to point to the actual S3 path
                    // Spatie expects files at: {media_id}/{file_name}
                    // But our files are at the root. We can copy or just set the path.
                    // Easiest: update the id-based path to match where the file actually is
                    // Actually, Spatie constructs the URL from disk + model_id/collection/filename
                    // Let's just use addMediaFromUrl to properly import
                    $media->delete();
                    $item->delete();

                    $item = MediaLibraryItem::create([
                        'user_id' => 1,
                        'name' => $filename,
                    ]);

                    $item->addMediaFromUrl($url)
                        ->usingFileName($filename)
                        ->withCustomProperties(['source' => 's3-import'])
                        ->toMediaCollection('default');

                    $imported++;
                } catch (\Exception $e) {
                    $this->line("  Skipped {$filename}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("Done! Imported {$imported} images, skipped {$skipped} duplicates.");

        return self::SUCCESS;
    }
}
