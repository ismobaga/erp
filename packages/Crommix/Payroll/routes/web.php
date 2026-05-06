<?php

use Illuminate\Support\Facades\Route;

if (!config('payroll.enabled', true)) {
    return;
}

Route::prefix('payroll')->name('payroll.')->middleware(['web', 'auth'])->group(function (): void {
    // Payroll routes are primarily managed through Filament resources.
});
