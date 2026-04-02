<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageUploadController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        try {
            $media = $this->mediaService->upload(
                $request->file('image'),
                $request->user(),
            );

            return response()->json(['url' => $media->getUrl()]);
        } catch (\Throwable $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'file' => $request->file('image')?->getClientOriginalName(),
            ]);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
