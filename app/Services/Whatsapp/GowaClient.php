<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;

class GowaClient
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('gowa.base_url', 'https://gowa.crommixmali.com'), '/');
        $this->username = config('gowa.username');
        $this->password = config('gowa.password');
        $this->timeout = (int) config('gowa.timeout', 30);
    }

    public function sendText(string $phone, string $message, ?string $deviceId = null): array
    {
        $response = $this->client($deviceId)
            ->post($this->baseUrl . '/send/message', [
                'phone' => $phone,
                'message' => $message,
            ]);

        $response->throw();

        return $response->json() ?? [];
    }

    public function sendFile(string $phone, string $filePath, ?string $caption = null, ?string $deviceId = null): array
    {
        $stream = fopen($filePath, 'r');

        try {
            $response = $this->client($deviceId)
                ->attach('file', $stream, basename($filePath))
                ->post($this->baseUrl . '/send/file', array_filter([
                    'phone' => $phone,
                    'caption' => $caption,
                ]));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $response->throw();

        return $response->json() ?? [];
    }

    public function checkStatus(?string $deviceId = null): array
    {
        if (blank($deviceId)) {
            return ['status' => 'no_device_id'];
        }

        $response = $this->client($deviceId)
            ->get($this->baseUrl . '/devices/' . urlencode((string) $deviceId) . '/status');

        return $response->json() ?? [];
    }

    public function login(?string $deviceId = null): array
    {
        if (blank($deviceId)) {
            return ['status' => 'no_device_id'];
        }

        $response = $this->client($deviceId)
            ->get($this->baseUrl . '/devices/' . urlencode((string) $deviceId) . '/login');

        return $response->json() ?? [];
    }

    private function client(?string $deviceId = null): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->withBasicAuth((string) $this->username, (string) $this->password);

        if (filled($deviceId)) {
            $client = $client->withHeaders(['X-Device-Id' => $deviceId]);
        }

        return $client;
    }
}
