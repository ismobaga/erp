<?php

use App\Models\ContactRequest;
use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ReportExportDownloadController;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $company = Schema::hasTable('company_settings')
        ? CompanySetting::query()->first()
        : null;

    $companyName = $company?->company_name ?: 'CROMMIX MALI - SA';
    $companyEmail = $company?->email ?: 'contact@crommixmali.com';
    $companyPhone = $company?->phone ?: '83 45 08 83 / +226 25 50 20 00';
    $companyAddress = trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343';
    $companyWebsite = $company?->website ?: '';

    return view('company.presentation', compact(
        'company',
        'companyName',
        'companyEmail',
        'companyPhone',
        'companyAddress',
        'companyWebsite'
    ));
})->name('company.presentation');

Route::get('/presentation', function () {
    return redirect()->route('company.presentation');
});

// Static legal / info pages
Route::get('/confidentialite', function () {
    $company = Schema::hasTable('company_settings') ? CompanySetting::query()->first() : null;
    return view('company.confidentialite', [
        'company' => $company,
        'companyName' => $company?->company_name ?: 'CROMMIX MALI - SA',
        'companyEmail' => $company?->email ?: 'contact@crommixmali.com',
        'companyPhone' => $company?->phone ?: '83 45 08 83 / +226 25 50 20 00',
        'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343',
        'companyWebsite' => $company?->website ?: '',
    ]);
})->name('company.confidentialite');

Route::get('/conditions', function () {
    $company = Schema::hasTable('company_settings') ? CompanySetting::query()->first() : null;
    return view('company.conditions', [
        'company' => $company,
        'companyName' => $company?->company_name ?: 'CROMMIX MALI - SA',
        'companyEmail' => $company?->email ?: 'contact@crommixmali.com',
        'companyPhone' => $company?->phone ?: '83 45 08 83 / +226 25 50 20 00',
        'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343',
        'companyWebsite' => $company?->website ?: '',
    ]);
})->name('company.conditions');

Route::get('/cookies', function () {
    $company = Schema::hasTable('company_settings') ? CompanySetting::query()->first() : null;
    return view('company.cookies', [
        'company' => $company,
        'companyName' => $company?->company_name ?: 'CROMMIX MALI - SA',
        'companyEmail' => $company?->email ?: 'contact@crommixmali.com',
        'companyPhone' => $company?->phone ?: '83 45 08 83 / +226 25 50 20 00',
        'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343',
        'companyWebsite' => $company?->website ?: '',
    ]);
})->name('company.cookies');

Route::get('/bureaux', function () {
    $company = Schema::hasTable('company_settings') ? CompanySetting::query()->first() : null;
    return view('company.bureaux', [
        'company' => $company,
        'companyName' => $company?->company_name ?: 'CROMMIX MALI - SA',
        'companyEmail' => $company?->email ?: 'contact@crommixmali.com',
        'companyPhone' => $company?->phone ?: '83 45 08 83 / +226 25 50 20 00',
        'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343',
        'companyWebsite' => $company?->website ?: '',
    ]);
})->name('company.bureaux');

Route::get('/dms-presentation', function () {
    $company = Schema::hasTable('company_settings')
        ? CompanySetting::query()->first()
        : null;

    $companyName = $company?->company_name ?: 'CROMMIX MALI - SA';
    $companyEmail = $company?->email ?: 'contact@crommixmali.com';
    $companyPhone = $company?->phone ?: '83 45 08 83 / +226 25 50 20 00';
    $companyAddress = trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')) ?: 'Bamako (République du Mali), Bacodjicoroni Golf Rue 661 Porte 343';
    $companyWebsite = $company?->website ?: '';

    return view('company.dms-presentation', compact(
        'company',
        'companyName',
        'companyEmail',
        'companyPhone',
        'companyAddress',
        'companyWebsite'
    ));
})->name('dms.presentation');

Route::post('/contact-request', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'company_name' => ['nullable', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'intent' => ['required', 'string', 'max:255'],
        'message' => ['nullable', 'string', 'max:2000'],
        'source' => ['nullable', 'in:website,dms'],
    ]);

    $source = $validated['source'] ?? 'website';

    ContactRequest::create([
        'name' => $validated['name'],
        'company_name' => $validated['company_name'] ?? null,
        'email' => $validated['email'],
        'intent' => $validated['intent'],
        'message' => $validated['message'] ?? null,
        'status' => 'new',
        'source' => $source,
    ]);

    $redirectTarget = $source === 'dms'
        ? url('/dms-presentation') . '/#contact'
        : url('/') . '/#contact';

    return redirect()->to($redirectTarget)->with(
        'status',
        'Merci ' . e($validated['name']) . ' — votre demande a bien été reçue. Nous vous recontacterons rapidement.'
    );
})->middleware('throttle:5,1')->name('company.presentation.contact');

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
