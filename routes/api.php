<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GiftItemApiController;
use App\Http\Controllers\Api\V1\GroupApiController;
use App\Http\Controllers\Api\V1\ReservationApiController;
use App\Http\Controllers\Api\V1\SharedWishlistApiController;
use App\Http\Controllers\Api\V1\WishlistApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/google/exchange', [AuthController::class, 'googleExchange'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [WishlistApiController::class, 'dashboard']);
        Route::post('/wishlists', [WishlistApiController::class, 'store']);
        Route::get('/wishlists/{wishlist}', [WishlistApiController::class, 'show']);
        Route::put('/wishlists/{wishlist}', [WishlistApiController::class, 'update']);
        Route::delete('/wishlists/{wishlist}', [WishlistApiController::class, 'destroy']);
        Route::put('/wishlists/{wishlist}/groups', [WishlistApiController::class, 'syncGroups']);

        Route::post('/wishlists/{wishlist}/items', [GiftItemApiController::class, 'store']);
        Route::put('/items/{item}', [GiftItemApiController::class, 'update']);
        Route::delete('/items/{item}', [GiftItemApiController::class, 'destroy']);
        Route::post('/items/{item}/mark-reserved', [GiftItemApiController::class, 'markReserved']);
        Route::post('/items/{item}/clear-reservation', [GiftItemApiController::class, 'clearReservation']);

        Route::get('/shared/{share_code}', [SharedWishlistApiController::class, 'show']);
        Route::post('/shared/{share_code}/items/{item}/reserve', [SharedWishlistApiController::class, 'reserve']);
        Route::post('/shared/{share_code}/items/{item}/unreserve', [SharedWishlistApiController::class, 'unreserve']);
        Route::post('/shared/{share_code}/items/{item}/contributions', [SharedWishlistApiController::class, 'contribute']);
        Route::delete('/shared/{share_code}/items/{item}/contributions/{contribution}', [SharedWishlistApiController::class, 'removeContribution']);
        Route::post('/shared/{share_code}/items/{item}/comments', [SharedWishlistApiController::class, 'comment']);

        Route::get('/reservations', [ReservationApiController::class, 'index']);

        Route::get('/groups', [GroupApiController::class, 'index']);
        Route::post('/groups', [GroupApiController::class, 'store']);
        Route::get('/groups/{group}', [GroupApiController::class, 'show']);
        Route::delete('/groups/{group}', [GroupApiController::class, 'destroy']);
        Route::post('/groups/{group}/invite', [GroupApiController::class, 'invite']);
        Route::post('/groups/{group}/accept', [GroupApiController::class, 'accept']);
        Route::post('/groups/{group}/decline', [GroupApiController::class, 'decline']);
        Route::post('/groups/{group}/leave', [GroupApiController::class, 'leave']);
        Route::delete('/groups/{group}/members/{user}', [GroupApiController::class, 'removeMember']);
    });
});
