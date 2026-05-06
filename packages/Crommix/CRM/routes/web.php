<?php

use Illuminate\Support\Facades\Route;

if (!config('crm.enabled', true)) {
    return;
}

Route::prefix('crm')->name('crm.')->middleware(['web', 'auth'])->group(function (): void {
    // CRM routes are primarily managed through Filament resources.
});
