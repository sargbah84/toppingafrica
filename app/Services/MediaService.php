<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MediaLibraryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaService
{
    public function upload(UploadedFile $file, User $user, ?string $altText = null): Media
    {
        $item = MediaLibraryItem::create([
            'user_id' => $user->id,
            'name' => $file->getClientOriginalName(),
        ]);

        $filePath = $this->resolveFilePath($file);

        $media = $item->addMedia($filePath)
            ->usingFileName($file->getClientOriginalName())
            ->withCustomProperties([
                'alt_text' => $altText,
                'source' => 'upload',
                'uploaded_by' => $user->id,
            ])
            ->toMediaCollection('default');

        return $media;
    }

    public function storeFromUrl(
        string $url,
        string $source,
        User $user,
        ?string $altText = null,
        ?array $sourceMeta = null
    ): Media {
        $item = MediaLibraryItem::create([
            'user_id' => $user->id,
            'name' => basename(parse_url($url, PHP_URL_PATH)),
        ]);

        $customProperties = [
            'alt_text' => $altText,
            'source' => $source,
            'source_url' => $url,
            'uploaded_by' => $user->id,
        ];

        if ($sourceMeta) {
            if (isset($sourceMeta['photographer'])) {
                $customProperties['photographer'] = $sourceMeta['photographer'];
            }
            if (isset($sourceMeta['photographer_url'])) {
                $customProperties['photographer_url'] = $sourceMeta['photographer_url'];
            }
        }

        $media = $item->addMediaFromUrl($url)
            ->withCustomProperties($customProperties)
            ->toMediaCollection('default');

        return $media;
    }

    public function delete(Media $media): bool
    {
        if ($media->model instanceof MediaLibraryItem) {
            $media->model->delete();
        }

        $media->delete();

        return true;
    }

    private function resolveFilePath(UploadedFile $file): string
    {
        if ($file instanceof TemporaryUploadedFile) {
            $disk = config('livewire.temporary_file_upload.disk', 'local');

            $realPath = $file->getRealPath();
            if (! empty($realPath) && $realPath !== false && file_exists($realPath)) {
                return $realPath;
            }

            $path = $file->path();
            $fullPath = Storage::disk($disk)->path($path);
            if (! empty($fullPath) && file_exists($fullPath)) {
                return $fullPath;
            }

            $pathName = $file->getPathname();
            if (! empty($pathName) && file_exists($pathName)) {
                return $pathName;
            }

            throw new \RuntimeException('Could not resolve file path for temporary upload');
        }

        $pathName = $file->getPathname();
        if (! empty($pathName) && file_exists($pathName)) {
            return $pathName;
        }

        $realPath = $file->getRealPath();
        if (! empty($realPath) && $realPath !== false && file_exists($realPath)) {
            return $realPath;
        }

        $path = $file->path();
        if (! empty($path) && file_exists($path)) {
            return $path;
        }

        throw new \RuntimeException('Could not resolve file path for upload');
    }
}
