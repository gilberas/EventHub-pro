<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

Route::name('api.v1.')->prefix('v1')->group(function () {

    // Public auth endpoints (rate limited)
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:api-auth')
        ->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    // Public event endpoints
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/featured', [EventController::class, 'featured'])->name('events.featured');
    Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');

    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::get('auth/tokens', [AuthController::class, 'tokens'])->name('auth.tokens');
        Route::delete('auth/tokens/{tokenId}', [AuthController::class, 'revokeToken'])->name('auth.tokens.revoke');

        // Booking endpoints (rate limited)
        Route::middleware('throttle:api-booking')->group(function () {
            Route::post('sessions/{session}/hold', [BookingController::class, 'holdSeats'])->name('sessions.hold');
            Route::post('checkout', [BookingController::class, 'checkout'])->name('checkout');
        });

        // My bookings
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{reference}', [BookingController::class, 'show'])->name('bookings.show');

        // My tickets
        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets/validate', [TicketController::class, 'validateScan'])->name('tickets.validate');
    });
});
