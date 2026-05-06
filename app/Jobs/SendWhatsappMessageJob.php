<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\WhatsappMessageLog;
use App\Services\Whatsapp\GowaClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly WhatsappMessageLog $log,
    ) {
    }

    public function handle(GowaClient $gowa): void
    {
        $deviceId = $this->resolveDeviceId();

        try {
            if ($this->log->type === 'file' && filled($this->log->file_path)) {
                $response = $gowa->sendFile(
                    phone: $this->log->phone,
                    filePath: Storage::path($this->log->file_path),
                    caption: $this->log->message,
                    deviceId: $deviceId,
                );
            } else {
                $response = $gowa->sendText(
                    phone: $this->log->phone,
                    message: (string) $this->log->message,
                    deviceId: $deviceId,
                );
            }

            $this->log->update([
                'status' => 'sent',
                'response' => $response,
                'gowa_message_id' => data_get($response, 'results.message_id'),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveDeviceId(): string
    {
        $deviceId = (string) (currentCompany()?->whatsapp_device_id ?? '');

        if (filled($deviceId)) {
            return $deviceId;
        }

        $companyId = $this->resolveCompanyIdFromLog();

        if (filled($companyId)) {
            $deviceId = (string) (Company::query()->find($companyId)?->whatsapp_device_id ?? '');
        }

        if (blank($deviceId)) {
            throw new RuntimeException('Aucun appareil WhatsApp configuré pour cette société (whatsapp_device_id manquant).');
        }

        return $deviceId;
    }

    private function resolveCompanyIdFromLog(): ?int
    {
        if (!$this->log->sendable_type || !$this->log->sendable_id) {
            return null;
        }

        return match ($this->log->sendable_type) {
            Invoice::class => Invoice::query()->whereKey($this->log->sendable_id)->value('company_id'),
            Quote::class => Quote::query()->whereKey($this->log->sendable_id)->value('company_id'),
            Client::class => Client::query()->whereKey($this->log->sendable_id)->value('company_id'),
            default => null,
        };
    }
}
