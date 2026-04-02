<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Blog\CategoryController;
use App\Http\Controllers\Admin\Blog\PostController;
use App\Http\Controllers\Admin\Blog\TagController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\IsStaff;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'verified', IsStaff::class])
    ->name('admin.')
    ->group(function (): void {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Blog
        Route::prefix('blog')->name('blog.')->group(function (): void {

            // Posts
            Route::get('posts/trashed', [PostController::class, 'trashed'])->name('posts.trashed');
            Route::post('posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');
            Route::delete('posts/{id}/force-delete', [PostController::class, 'forceDelete'])->name('posts.force-delete');
            Route::resource('posts', PostController::class)->except(['show'])->parameters(['posts' => 'post:id']);

            // Categories
            Route::resource('categories', CategoryController::class)->except(['show']);

            // Tags
            Route::resource('tags', TagController::class)->except(['show']);
        });

        // Pages
        Route::resource('pages', PageController::class)->except(['show']);

        // Users
        Route::resource('users', UserController::class)->except(['show']);

        // Roles (super admin only)
        Route::resource('roles', RoleController::class)->except(['show'])
            ->middleware('can:manage users');

        // Image upload (for TipTap editor)
        Route::post('upload-image', [ImageUploadController::class, 'store'])->name('upload-image');

        // Comments
        Route::get('comments', fn () => view('admin.comments.index'))->name('comments.index');

        // Newsletter
        Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
        Route::get('newsletters/export', [NewsletterController::class, 'export'])->name('newsletters.export');
        Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');

        // Ads
        Route::resource('ads', AdController::class)->except(['show']);

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
