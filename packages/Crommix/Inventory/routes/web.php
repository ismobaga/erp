<?php

use Illuminate\Support\Facades\Route;

if (!config('inventory.enabled', true)) {
    return;
}

Route::prefix('inventory')->name('inventory.')->middleware(['web', 'auth'])->group(function (): void {
    // Inventory routes are primarily managed through Filament resources.
});
