<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\AuditTrailService;
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpensePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Expense $expense): Response
    {
        $this->authorize('view', $expense);

        $expense->loadMissing(['recorder', 'approver']);

        app(AuditTrailService::class)->log('expense_pdf_accessed', $expense, [
            'title' => $expense->title,
            'amount' => $expense->amount,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();

        $viewData = [
            'expense' => $expense,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
        ];

        $filename = 'depense-'.($expense->reference ?: $expense->id).'.pdf';

        if ($request->boolean('download')) {
            return app(BusinessDocumentPdf::class)
                ->make('expenses.pdf', $viewData)
                ->download($filename);
        }

        return response()->view('expenses.pdf', $viewData);
    }
}
