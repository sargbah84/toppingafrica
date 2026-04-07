<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CreatorClaimController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\SitemapController;
use App\Livewire\TrendingPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public blog routes
Route::get('/', [BlogController::class, 'index'])->name('home');
Route::get('/search', [BlogController::class, 'search'])->name('blog.search');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/feed', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/trending', TrendingPage::class)->name('trending');
Route::get('/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');

// Creator Directory (public)
Route::get('/creators', [CreatorController::class, 'index'])->name('creators.index');
Route::get('/creators/claim/{token}', [CreatorClaimController::class, 'show'])->name('creators.claim');
Route::post('/creators/claim/{token}', [CreatorClaimController::class, 'update'])->name('creators.claim.update');
Route::get('/creators/{slug}', [CreatorController::class, 'show'])->name('creators.show');
Route::post('/creators/{slug}/request-claim', [CreatorController::class, 'requestClaim'])->name('creators.request-claim');
Route::post('/creators/{slug}/follow', [CreatorController::class, 'toggleFollow'])->middleware('auth')->name('creators.follow');

// Regular user pages (use blog layout)
Route::middleware(['auth'])->group(function () {
    Route::view('my-account', 'account.index')->name('dashboard');
    Route::view('my-account/profile', 'account.profile')->name('profile');
});

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
