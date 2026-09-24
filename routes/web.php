<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WishlistController as AdminWishlistController;
use App\Http\Controllers\GiftContributionController;
use App\Http\Controllers\GiftItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicWishlistController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Veřejné sdílené wishlisty (Přístupné i bez přihlášení)
Route::get('/s/{share_code}', [PublicWishlistController::class, 'show'])->name('public.wishlists.show');
Route::post('/s/{share_code}/items/{item}/reserve', [PublicWishlistController::class, 'reserve'])
    ->middleware('throttle:20,1')
    ->name('public.wishlists.reserve');
Route::post('/s/{share_code}/items/{item}/unreserve', [PublicWishlistController::class, 'unreserve'])
    ->middleware('throttle:20,1')
    ->name('public.wishlists.unreserve');
Route::post('/s/{share_code}/items/{item}/contributions', [GiftContributionController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('gift-contributions.store');
Route::delete('/s/{share_code}/items/{item}/contributions/{contribution}', [GiftContributionController::class, 'destroy'])
    ->middleware('throttle:20,1')
    ->name('gift-contributions.destroy');
Route::get('/s/{share_code}/manage/{token}', [PublicWishlistController::class, 'restoreSession'])
    ->middleware('throttle:20,1')
    ->name('public.wishlists.restore-session');

// Chráněné cesty pro přihlášeného uživatele
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [WishlistController::class, 'index'])->name('dashboard');

    Route::resource('wishlists', WishlistController::class);
    Route::get('/wishlists/{wishlist}/qr-code', [WishlistController::class, 'qrCode'])->name('wishlists.qr-code');

    Route::post('/wishlists/{wishlist}/items', [GiftItemController::class, 'store'])->name('gift-items.store');
    Route::put('/gift-items/{item}', [GiftItemController::class, 'update'])->name('gift-items.update');
    Route::delete('/gift-items/{item}', [GiftItemController::class, 'destroy'])->name('gift-items.destroy');
    Route::post('/gift-items/{item}/mark-reserved', [GiftItemController::class, 'markReserved'])->name('gift-items.mark-reserved');
    Route::post('/gift-items/{item}/clear-reservation', [GiftItemController::class, 'clearReservation'])->name('gift-items.clear-reservation');

    Route::get('/my-reservations', [ReservationController::class, 'index'])->name('reservations.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Ukončení impersonace musí jít i pod identitou vydávaného (ne-admin) uživatele.
    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'stop'])->name('admin.stop-impersonating');
});

// Administrace
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/verify-email', [AdminUserController::class, 'verifyEmail'])->name('users.verify-email');
    Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

    Route::post('/wishlists/{wishlist}/suspend', [AdminWishlistController::class, 'suspend'])->name('wishlists.suspend');
    Route::post('/wishlists/{wishlist}/unsuspend', [AdminWishlistController::class, 'unsuspend'])->name('wishlists.unsuspend');
});

require __DIR__.'/auth.php';
