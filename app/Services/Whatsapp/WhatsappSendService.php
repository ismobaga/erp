<?php

namespace App\Services\Whatsapp;

use App\Models\Client;
use App\Models\Company;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\WhatsappMessageLog;
use App\Support\PhoneFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WhatsappSendService
{
    public function sendInvoice(Invoice $invoice): WhatsappMessageLog
    {
        $company = currentCompany();
        $client = $invoice->client;

        if ($client === null) {
            throw new \RuntimeException('Invoice #' . $invoice->invoice_number . ' has no associated client.');
        }

        $phone = PhoneFormatter::toWhatsappJid((string) $client->phone);

        $clientName = $client->company_name ?: $client->contact_name ?: 'Client';
        $template = (string) config('whatsapp_templates.invoice', 'Bonjour {client}, veuillez trouver ci-joint votre facture {number}.');
        $caption = str_replace(
            ['{client}', '{number}', '{amount}'],
            [$clientName, (string) $invoice->invoice_number, number_format((float) $invoice->total, 2, ',', ' ') . ' FCFA'],
            $template
        );

        $invoice->loadMissing(['client', 'items.service', 'quote']);
        $pdfPath = $this->generateInvoicePdf($invoice, $company);

        $log = WhatsappMessageLog::create([
            'sendable_type' => Invoice::class,
            'sendable_id' => $invoice->id,
            'client_id' => $client->id,
            'phone' => $phone,
            'type' => 'file',
            'message' => $caption,
            'file_path' => $pdfPath,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        try {
            $response = app(GowaClient::class)->sendFile(
                phone: $phone,
                filePath: Storage::path($pdfPath),
                caption: $caption,
                deviceId: $company?->whatsapp_device_id,
            );

            $log->update([
                'status' => 'sent',
                'response' => $response,
                'gowa_message_id' => data_get($response, 'results.message_id'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    public function sendQuote(Quote $quote): WhatsappMessageLog
    {
        $company = currentCompany();
        $client = $quote->client;

        if ($client === null) {
            throw new \RuntimeException('Quote #' . $quote->quote_number . ' has no associated client.');
        }

        $phone = PhoneFormatter::toWhatsappJid((string) $client->phone);

        $clientName = $client->company_name ?: $client->contact_name ?: 'Client';
        $template = (string) config('whatsapp_templates.quote', 'Bonjour {client}, veuillez trouver ci-joint votre devis {number}.');
        $caption = str_replace(
            ['{client}', '{number}'],
            [$clientName, (string) $quote->quote_number],
            $template
        );

        $quote->loadMissing(['client', 'items.service']);
        $pdfPath = $this->generateQuotePdf($quote, $company);

        $log = WhatsappMessageLog::create([
            'sendable_type' => Quote::class,
            'sendable_id' => $quote->id,
            'client_id' => $client->id,
            'phone' => $phone,
            'type' => 'file',
            'message' => $caption,
            'file_path' => $pdfPath,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        try {
            $response = app(GowaClient::class)->sendFile(
                phone: $phone,
                filePath: Storage::path($pdfPath),
                caption: $caption,
                deviceId: $company?->whatsapp_device_id,
            );

            $log->update([
                'status' => 'sent',
                'response' => $response,
                'gowa_message_id' => data_get($response, 'results.message_id'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    public function sendPaymentReminder(Invoice $invoice): WhatsappMessageLog
    {
        $company = currentCompany();
        $client = $invoice->client;

        if ($client === null) {
            throw new \RuntimeException('Invoice #' . $invoice->invoice_number . ' has no associated client.');
        }

        $phone = PhoneFormatter::toWhatsappJid((string) $client->phone);

        $clientName = $client->company_name ?: $client->contact_name ?: 'Client';
        $template = (string) config('whatsapp_templates.payment_reminder', 'Bonjour {client}, ceci est un rappel pour la facture {number} arrivée à échéance.');
        $message = str_replace(
            ['{client}', '{number}'],
            [$clientName, (string) $invoice->invoice_number],
            $template
        );

        $log = WhatsappMessageLog::create([
            'sendable_type' => Invoice::class,
            'sendable_id' => $invoice->id,
            'client_id' => $client->id,
            'phone' => $phone,
            'type' => 'text',
            'message' => $message,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        try {
            $response = app(GowaClient::class)->sendText(
                phone: $phone,
                message: $message,
                deviceId: $company?->whatsapp_device_id,
            );

            $log->update([
                'status' => 'sent',
                'response' => $response,
                'gowa_message_id' => data_get($response, 'results.message_id'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    public function sendTextToClient(Client $client, string $message): WhatsappMessageLog
    {
        $company = currentCompany();
        $company = $client->company ?? $company; // Use client's company if available, otherwise fallback to current company    

        $phone = PhoneFormatter::toWhatsappJid((string) $client->phone);

        $log = WhatsappMessageLog::create([
            'client_id' => $client->id,
            'phone' => $phone,
            'type' => 'text',
            'message' => $message,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        try {
            $response = app(GowaClient::class)->sendText(
                phone: $phone,
                message: $message,
                deviceId: $company?->whatsapp_device_id,
            );

            $log->update([
                'status' => 'sent',
                'response' => $response,
                'gowa_message_id' => data_get($response, 'results.message_id'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    private function generateInvoicePdf(Invoice $invoice, ?Company $company): string
    {
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'invoice' => $invoice,
            'company' => $company,
            'bankDetails' => [
                'bank_name' => $company?->bank_name,
                'account_name' => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number,
                'swift_code' => $company?->bank_swift_code,
            ],
            'logoDataUri' => null,
            'isDownload' => true,
        ];

        $pdf = Pdf::loadView('invoices.pdf', $viewData)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ])
            ->setPaper('a4');

        $safeNumber = preg_replace('/[^A-Za-z0-9\-]/', '_', (string) $invoice->invoice_number);
        $filename = 'whatsapp/' . $safeNumber . '_' . now()->timestamp . '.pdf';
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    private function generateQuotePdf(Quote $quote, ?Company $company): string
    {
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'quote' => $quote,
            'company' => $company,
            'bankDetails' => [
                'bank_name' => $company?->bank_name,
                'account_name' => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number,
                'swift_code' => $company?->bank_swift_code,
            ],
            'logoDataUri' => null,
            'isDownload' => true,
        ];

        $viewName = view()->exists('quotes.pdf') ? 'quotes.pdf' : 'invoices.pdf';

        if ($viewName === 'invoices.pdf') {
            // Adapt quote data to invoice view structure
            $viewData['invoice'] = $quote;
        }

        $pdf = Pdf::loadView($viewName, $viewData)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ])
            ->setPaper('a4');

        $safeNumber = preg_replace('/[^A-Za-z0-9\-]/', '_', (string) $quote->quote_number);
        $filename = 'whatsapp/' . $safeNumber . '_' . now()->timestamp . '.pdf';
        Storage::put($filename, $pdf->output());

        return $filename;
    }
}
