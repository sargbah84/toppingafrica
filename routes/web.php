<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public blog routes
Route::get('/', [BlogController::class, 'index'])->name('home');
Route::get('/search', [BlogController::class, 'search'])->name('blog.search');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/feed', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Logout route for admin panel
Route::post('logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';

// Blog post catch-all route (MUST be last to avoid catching /login, /register, etc.)
Route::get('/{slug}', [BlogController::class, 'show'])->name('blog.show')->where('slug', '[a-z0-9\-]+');
