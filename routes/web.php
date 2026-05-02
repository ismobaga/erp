<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\CompanyPagesController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ReportExportDownloadController;
use Illuminate\Support\Facades\Route;

// ── Public company / marketing pages ─────────────────────────────────────────
Route::get('/', [CompanyPagesController::class, 'presentation'])->name('company.presentation');
Route::get('/presentation', fn () => redirect()->route('company.presentation'));

Route::get('/confidentialite', [CompanyPagesController::class, 'confidentialite'])->name('company.confidentialite');
Route::get('/conditions',      [CompanyPagesController::class, 'conditions'])->name('company.conditions');
Route::get('/cookies',         [CompanyPagesController::class, 'cookies'])->name('company.cookies');
Route::get('/bureaux',         [CompanyPagesController::class, 'bureaux'])->name('company.bureaux');
Route::get('/dms-presentation',[CompanyPagesController::class, 'dmsPresentation'])->name('dms.presentation');

Route::post('/contact-request', [CompanyPagesController::class, 'contactRequest'])
    ->middleware('throttle:5,1')
    ->name('company.presentation.contact');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
        ->middleware(['signed', 'throttle:30,1'])
        ->name('attachments.download');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)
        ->name('invoices.pdf');

    Route::get('/reports/download', ReportExportDownloadController::class)
        ->name('reports.download');

    Route::get('/backups/download', BackupDownloadController::class)
        ->middleware(['signed', 'throttle:10,1'])
        ->name('backups.download');
});

// ── Client Portal (public, token-secured) ────────────────────────────────────
Route::prefix('portal/{token}')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/',                      [ClientPortalController::class, 'index'])->name('portal.index');
    Route::get('/invoices/{invoice}',    [ClientPortalController::class, 'showInvoice'])->name('portal.invoice');
    Route::get('/invoices/{invoice}/pdf',[ClientPortalController::class, 'downloadPdf'])->name('portal.invoice.pdf');
});
