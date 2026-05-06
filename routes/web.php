<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\CompanyPagesController;
use App\Http\Controllers\CreditNotePdfController;
use App\Http\Controllers\ExpensePdfController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\PaymentPdfController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\ReportExportDownloadController;
use Illuminate\Support\Facades\Route;

// ── Public company / marketing pages ─────────────────────────────────────────
Route::get('/', [CompanyPagesController::class, 'presentation'])->name('company.presentation');
Route::get('/presentation', fn() => redirect()->route('company.presentation'));

Route::get('/confidentialite', [CompanyPagesController::class, 'confidentialite'])->name('company.confidentialite');
Route::get('/conditions', [CompanyPagesController::class, 'conditions'])->name('company.conditions');
Route::get('/cookies', [CompanyPagesController::class, 'cookies'])->name('company.cookies');
Route::get('/bureaux', [CompanyPagesController::class, 'bureaux'])->name('company.bureaux');
Route::get('/dms-presentation', [CompanyPagesController::class, 'dmsPresentation'])->name('dms.presentation');

Route::post('/contact-request', [CompanyPagesController::class, 'contactRequest'])
    ->middleware('throttle:contact:3,3600')  // 3 per hour per IP + email
    ->name('company.presentation.contact');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
        ->middleware(['signed', 'throttle:30,1'])
        ->name('attachments.download');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)
        ->middleware('throttle:pdf:10,60')  // 10 per minute per user
        ->name('invoices.pdf');

    Route::get('/quotes/{quote}/pdf', QuotePdfController::class)
        ->middleware('throttle:pdf:10,60')
        ->name('quotes.pdf');

    Route::get('/payments/{payment}/pdf', PaymentPdfController::class)
        ->middleware('throttle:pdf:10,60')
        ->name('payments.pdf');

    Route::get('/expenses/{expense}/pdf', ExpensePdfController::class)
        ->middleware('throttle:pdf:10,60')
        ->name('expenses.pdf');

    Route::get('/credit-notes/{creditNote}/pdf', CreditNotePdfController::class)
        ->middleware('throttle:pdf:10,60')
        ->name('credit-notes.pdf');

    Route::get('/reports/download', ReportExportDownloadController::class)
        ->name('reports.download');

    Route::get('/backups/download', BackupDownloadController::class)
        ->middleware(['signed', 'throttle:10,1'])
        ->name('backups.download');
});

// ── Client Portal (public, token-secured) ────────────────────────────────────
Route::prefix('portal/{token}')->middleware('throttle:portal:60,60')->group(function (): void {
    Route::get('/', [ClientPortalController::class, 'index'])->name('portal.index');
    Route::get('/invoices/{invoice}', [ClientPortalController::class, 'showInvoice'])->whereNumber('invoice')->name('portal.invoice');
    Route::get('/invoices/{invoice}/pdf', [ClientPortalController::class, 'downloadPdf'])->whereNumber('invoice')->name('portal.invoice.pdf');
});
