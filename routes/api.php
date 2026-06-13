<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External Blog Content API
|--------------------------------------------------------------------------
|
| Consumed by a trusted external site to list, read, create and update blog
| posts. Authenticated with a static bearer token (`api.token` middleware,
| backed by BLOG_API_TOKEN). All routes are prefixed with `/api`.
|
| Examples:
|   GET    /api/posts?status=published&per_page=20
|   GET    /api/posts/{slug}
|   POST   /api/posts
|   PUT    /api/posts/{slug}
|   PATCH  /api/posts/{slug}
|
*/

Route::middleware(['api.token', 'throttle:120,1'])->group(function (): void {
    Route::get('posts', [PostController::class, 'index'])->name('api.posts.index');
    Route::post('posts', [PostController::class, 'store'])->name('api.posts.store');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('api.posts.show');
    Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update'])->name('api.posts.update');
});
