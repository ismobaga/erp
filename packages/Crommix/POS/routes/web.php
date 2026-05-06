<?php

use Illuminate\Support\Facades\Route;

if (!config('pos.enabled', true)) {
    return;
}

Route::prefix('pos')->name('pos.')->middleware(['web', 'auth'])->group(function (): void {
    // POS routes are primarily managed through Filament resources.
});
