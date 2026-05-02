<?php

namespace App\Jobs;

use App\Models\WhatsappMessageLog;
use App\Services\Whatsapp\GowaClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly WhatsappMessageLog $log,
    ) {}

    public function handle(GowaClient $gowa): void
    {
        $deviceId = currentCompany()?->whatsapp_device_id;

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
}
