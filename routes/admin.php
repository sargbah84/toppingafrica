<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\Blog\CategoryController;
use App\Http\Controllers\Admin\Blog\PostController;
use App\Http\Controllers\Admin\Blog\TagController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\IsStaff;
use App\Livewire\Admin\Blog\ContentCalendar;
use App\Livewire\Admin\Blog\ContentLab;
use App\Livewire\Admin\ManageCreators;
use App\Livewire\Admin\ManageTrends;
use App\Livewire\Admin\Monitoring\ActivityLogs;
use App\Livewire\Admin\Monitoring\JobMonitor;
use App\Livewire\Admin\Monitoring\RequestLogs;
use Illuminate\Support\Facades\Route;

// Impersonation leave — outside the admin middleware group so it works
// even when the impersonated user is not staff or not verified.
Route::post('admin/impersonate/leave', [ImpersonationController::class, 'leave'])
    ->middleware('auth')
    ->name('admin.impersonate.leave');

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

            // Content Lab
            Route::get('lib', ContentLab::class)->name('content-lab');

            // Content Calendar
            Route::get('calendar', ContentCalendar::class)->name('calendar');
        });

        // Pages
        Route::resource('pages', PageController::class)->except(['show']);

        // Users (requires 'manage users' permission)
        Route::resource('users', UserController::class)->except(['show'])
            ->middleware('can:manage users');
        Route::post('users/{user}/toggle-verification', [UserController::class, 'toggleVerification'])->name('users.toggle-verification')
            ->middleware('can:manage users');

        // Impersonation (start — requires staff access)
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'impersonate'])->name('users.impersonate')
            ->middleware('can:manage users');

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
        Route::get('newsletters/{newsletter}', [NewsletterController::class, 'show'])->name('newsletters.show');
        Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');

        // Ads
        Route::resource('ads', AdController::class)->only(['index', 'create', 'edit', 'destroy']);
        Route::post('ads/{ad}/duplicate', [AdController::class, 'duplicate'])->name('ads.duplicate');

        // Popups
        Route::get('popups', fn () => view('admin.popups.index'))->name('popups.index');

        // Creators
        Route::get('creators', ManageCreators::class)->name('creators.index');

        // Trends
        Route::get('trends', ManageTrends::class)->name('trends.index');

        // Monitoring
        Route::get('monitoring/activity-logs', ActivityLogs::class)->name('monitoring.activity-logs');
        Route::get('monitoring/request-logs', RequestLogs::class)->name('monitoring.request-logs');
        Route::get('monitoring/jobs', JobMonitor::class)->name('monitoring.jobs');

        // Settings (requires 'manage settings' permission)
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index')
            ->middleware('can:manage settings');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update')
            ->middleware('can:manage settings');
        Route::post('settings/cache', [SettingController::class, 'runCacheAction'])->name('settings.cache')
            ->middleware('can:manage settings');
        Route::get('settings/gsc-callback', [SettingController::class, 'gscCallback'])->name('settings.gsc-callback')
            ->middleware('can:manage settings');
    });
