<?php

use App\Http\Controllers\Api\ApiDocumentationController;
use App\Http\Controllers\Api\V1\Private\AuditLogController;
use App\Http\Controllers\Api\V1\Private\ClientController;
use App\Http\Controllers\Api\V1\Private\InvoiceController;
use App\Http\Controllers\Api\V1\Public\CompanyProfileController;
use App\Http\Controllers\Api\V1\Public\WebhookIngestionController;
use Illuminate\Support\Facades\Route;

Route::get('/docs/openapi.json', ApiDocumentationController::class)
    ->name('api.docs.openapi');

Route::prefix('v1')->group(function (): void {
    Route::prefix('public')
        ->middleware(['auth.api', 'api.scope:public', 'api.audit', 'throttle:api-public'])
        ->group(function (): void {
            Route::get('/company', CompanyProfileController::class)->name('api.v1.public.company');
            Route::post('/webhooks/{source}', WebhookIngestionController::class)
                ->whereAlphaNumeric('source')
                ->middleware('throttle:api-webhooks')
                ->name('api.v1.public.webhooks.ingest');
        });

    Route::prefix('private')
        ->middleware(['auth.api', 'api.scope:private', 'api.audit', 'throttle:api-private'])
        ->group(function (): void {
            Route::get('/clients', [ClientController::class, 'index'])->name('api.v1.private.clients.index');
            Route::get('/clients/{client}', [ClientController::class, 'show'])->whereNumber('client')->name('api.v1.private.clients.show');

            Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.v1.private.invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('api.v1.private.invoices.show');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.v1.private.audit-logs.index');
        });
});
