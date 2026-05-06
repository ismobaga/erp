<?php

use Illuminate\Support\Facades\Route;

if (!config('hr.enabled', true)) {
    return;
}

Route::prefix('hr')->name('hr.')->middleware(['web', 'auth'])->group(function (): void {
    // HR module routes are primarily managed through the admin panel Filament resources.
    // Add any custom public-facing HR endpoints here if needed.
});
