<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CreatorClaimController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\DashboardController;
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
Route::get('/_trending-redirect', function (\Illuminate\Http\Request $request) {
    $target = template_url('trending', '/');
    $qs = $request->getQueryString();

    return redirect($qs ? $target . (str_contains($target, '?') ? '&' : '?') . $qs : $target);
})->name('trending');

Route::get('/_creators-redirect', function (\Illuminate\Http\Request $request) {
    $target = template_url('creators', '/');
    $qs = $request->getQueryString();

    return redirect($qs ? $target . (str_contains($target, '?') ? '&' : '?') . $qs : $target);
})->name('creators.index');
Route::get('/creators/claim/{token}', [CreatorClaimController::class, 'show'])->name('creators.claim');
Route::post('/creators/claim/{token}', [CreatorClaimController::class, 'update'])->name('creators.claim.update');

// "Complete your account" registration flow — opt-in upgrade from
// token-based claim to a full User account with the creator role.
Route::get('/creators/claim/{token}/register', [CreatorClaimController::class, 'showRegister'])->name('creators.claim.register.show');
Route::post('/creators/claim/{token}/register', [CreatorClaimController::class, 'register'])->name('creators.claim.register');

// Unified frontend dashboard — everyone who isn't a staff/admin lands
// here after login. The landing page branches by role: creators see
// their claimed profile cards inline, everyone else sees their
// Recently Viewed + Comments history inline.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');

    // Authenticated creator profile edit routes — intentionally kept at
    // /creator/profile/* because they're semantically creator-specific
    // actions, not dashboard-area navigation. Moving them would break
    // any existing inbound links without meaningful benefit.
    Route::get('/creator/profile/{creatorId}/edit', [CreatorClaimController::class, 'editAsOwner'])
        ->name('creators.claim.edit-as-owner');
    Route::post('/creator/profile/{creatorId}/update', [CreatorClaimController::class, 'updateAsOwner'])
        ->name('creators.claim.update-as-owner');
});

// Legacy redirect: any creator with a bookmarked /creator/dashboard URL
// (or an open tab from before the refactor) lands on the new /dashboard.
// 301 so browsers update their bookmarks.
Route::permanentRedirect('/creator/dashboard', '/dashboard');

Route::get('/creators/suggest', [CreatorController::class, 'suggest'])->name('creators.suggest');
Route::get('/creators/{slug}', [CreatorController::class, 'show'])->name('creators.show');
Route::post('/creators/{slug}/request-claim', [CreatorController::class, 'requestClaim'])
    ->middleware('throttle:5,1')
    ->name('creators.request-claim');
Route::post('/creators/{slug}/follow', [CreatorController::class, 'toggleFollow'])->middleware('auth')->name('creators.follow');

// Legacy account redirects — /my-account and its sub-pages were the
// pre-refactor home for logged-in user content (Recently Viewed,
// Comments, profile/password/delete forms). All of that now lives
// under /dashboard and /dashboard/settings. We keep these two URLs
// alive as 301 redirects so any bookmarks or externally-cached links
// still land on the correct destination.
Route::permanentRedirect('/my-account/profile', '/dashboard/settings');
Route::permanentRedirect('/my-account', '/dashboard');

// The route names 'account' and 'profile' used to point at /my-account
// and /my-account/profile respectively. They're kept alive here as
// aliases so any Blade template or controller that still calls
// route('account') or route('profile') continues to work during the
// transition. Both resolve to the new dashboard URLs.
Route::middleware(['auth'])->group(function () {
    Route::redirect('/account', '/dashboard')->name('account');
    Route::redirect('/profile', '/dashboard/settings')->name('profile');
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
