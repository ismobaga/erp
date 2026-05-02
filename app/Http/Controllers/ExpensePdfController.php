<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\AuditTrailService;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpensePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Expense $expense): Response
    {
        abort_unless(auth()->user()?->canAny(['expenses.view', 'reports.view']), 403);

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

        $filename = 'depense-' . ($expense->reference ?: $expense->id) . '.pdf';

        if ($request->boolean('download')) {
            return Pdf::loadView('expenses.pdf', $viewData)
                ->setOption([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 120,
                    'defaultFont' => 'DejaVu Sans',
                ])
                ->setPaper('a4')
                ->download($filename);
        }

        return response()->view('expenses.pdf', $viewData);
    }
}
