<?php

use App\Http\Middleware\EnsureOrganizationContext;
use Illuminate\Support\Facades\Route;

// Organization-scoped routes that require an active organization context
Route::middleware(['auth', 'verified', EnsureOrganizationContext::class])->prefix('{organization}')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return inertia('Org/Dashboard');
    })->name('org.dashboard');

    // Placeholder routes for later phases
    Route::get('/events', function () {
        return inertia('Org/Events/Index');
    })->name('org.events.index');

    Route::get('/venues', function () {
        return inertia('Org/Venues/Index');
    })->name('org.venues.index');
});
