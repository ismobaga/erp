<?php

use Illuminate\Support\Facades\Route;

if (!config('procurement.enabled', true)) {
    return;
}

Route::prefix('procurement')->name('procurement.')->middleware(['web', 'auth'])->group(function (): void {
    // Procurement routes are primarily managed through Filament resources.
});
