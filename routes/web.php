<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\InvoicePdfController;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    return view('company.presentation', [
        'company' => Schema::hasTable('company_settings')
            ? CompanySetting::query()->first()
            : null,
    ]);
})->name('company.presentation');

Route::get('/presentation', function () {
    return redirect()->route('company.presentation');
});

Route::post('/contact-request', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'intent' => ['required', 'string', 'max:255'],
        'message' => ['nullable', 'string', 'max:2000'],
    ]);

    return redirect()->to(url('/') . '/#contact')->with(
        'status',
        'Merci ' . $validated['name'] . ' — votre demande a bien été reçue. Nous vous recontacterons rapidement.'
    );
})->name('company.presentation.contact');

Route::middleware('auth')->group(function (): void {
    Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
        ->name('attachments.download');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)
        ->name('invoices.pdf');
});
