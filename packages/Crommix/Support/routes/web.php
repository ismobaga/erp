<?php

use Illuminate\Support\Facades\Route;

if (!config('support.enabled', true)) {
    return;
}

Route::prefix('support')->name('support.')->middleware(['web', 'auth'])->group(function (): void {
    // Support routes are primarily managed through Filament resources.
});
