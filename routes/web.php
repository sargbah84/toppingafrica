<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CreatorClaimController;
use App\Http\Controllers\CreatorController;
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

// Built-in section pages (Trending, Creators) are now CMS pages backed by
// templates. The catch-all `/{slug}` route at the bottom of this file
// dispatches them to BlogController::renderTrendingPage / renderCreatorsPage.
//
// We keep the legacy route names alive as redirects so every internal call
// to route('trending') / route('creators.index') (sitemap, schema markup,
// homepage carousel, footer, etc.) automatically follows the canonical slug
// the admin has chosen — even after a slug rename.
Route::get('/_trending-redirect', function () {
    return redirect(template_url('trending', '/'));
})->name('trending');

Route::get('/_creators-redirect', function () {
    return redirect(template_url('creators', '/'));
})->name('creators.index');
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
