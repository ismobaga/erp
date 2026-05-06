<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GowaClient
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private int $timeout;
    private int $retryTimes;
    private int $retrySleepMs;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('gowa.base_url', 'https://gowa.crommixmali.com'), '/');
        $this->username = config('gowa.username');
        $this->password = config('gowa.password');
        $this->timeout = (int) config('gowa.timeout', 30);
        $this->retryTimes = max(0, (int) config('gowa.retry_times', 2));
        $this->retrySleepMs = max(0, (int) config('gowa.retry_sleep_ms', 300));
    }

    public function sendText(string $phone, string $message, ?string $deviceId = null): array
    {
        return $this->requestJson(
            method: 'post',
            path: '/send/message',
            deviceId: $deviceId,
            payload: [
                'phone' => $phone,
                'message' => $message,
            ],
        );
    }

    public function sendFile(string $phone, string $filePath, ?string $caption = null, ?string $deviceId = null): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('WhatsApp file attachment is missing or unreadable: ' . $filePath);
        }

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

        $this->throwWithContext($response);

        return $response->json() ?? [];
    }

    public function checkStatus(?string $deviceId = null): array
    {
        if (blank($deviceId)) {
            return ['status' => 'no_device_id'];
        }

        return $this->requestJson(
            method: 'get',
            path: '/devices/' . rawurlencode((string) $deviceId) . '/status',
            deviceId: $deviceId,
        );
    }

    public function login(?string $deviceId = null): array
    {
        if (blank($deviceId)) {
            return ['status' => 'no_device_id'];
        }

        return $this->requestJson(
            method: 'get',
            path: '/devices/' . rawurlencode((string) $deviceId) . '/login',
            deviceId: $deviceId,
        );
    }

    public function loginWithCode(string $deviceId, string $phone): array
    {
        return $this->requestJson(
            method: 'post',
            path: '/devices/' . rawurlencode($deviceId) . '/login/code',
            deviceId: $deviceId,
            query: ['phone' => $phone],
        );
    }

    public function logout(string $deviceId): array
    {
        return $this->requestJson(
            method: 'post',
            path: '/devices/' . rawurlencode($deviceId) . '/logout',
            deviceId: $deviceId,
        );
    }

    public function reconnect(string $deviceId): array
    {
        return $this->requestJson(
            method: 'post',
            path: '/devices/' . rawurlencode($deviceId) . '/reconnect',
            deviceId: $deviceId,
        );
    }

    public function listDevices(): array
    {
        return $this->requestJson(method: 'get', path: '/devices');
    }

    public function addDevice(?string $deviceId = null): array
    {
        $payload = [];

        if (filled($deviceId)) {
            $payload['device_id'] = $deviceId;
        }

        return $this->requestJson(
            method: 'post',
            path: '/devices',
            payload: $payload,
        );
    }

    public function getDevice(string $deviceId): array
    {
        return $this->requestJson(
            method: 'get',
            path: '/devices/' . rawurlencode($deviceId),
        );
    }

    public function removeDevice(string $deviceId): array
    {
        return $this->requestJson(
            method: 'delete',
            path: '/devices/' . rawurlencode($deviceId),
        );
    }

    public function appStatus(?string $deviceId = null): array
    {
        return $this->requestJson(
            method: 'get',
            path: '/app/status',
            deviceId: $deviceId,
        );
    }

    private function requestJson(
        string $method,
        string $path,
        ?string $deviceId = null,
        array $query = [],
        array $payload = [],
    ): array {
        $client = $this->client($deviceId);
        $url = $this->baseUrl . $path;

        if (filled($deviceId)) {
            // GOWA accepts either X-Device-Id header or device_id query param.
            $query['device_id'] = $query['device_id'] ?? $deviceId;
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $response = match (strtolower($method)) {
            'get' => $client->get($url),
            'delete' => $client->delete($url),
            'post' => $client->post($url, $payload),
            'put' => $client->put($url, $payload),
            'patch' => $client->patch($url, $payload),
            default => throw new \InvalidArgumentException('Unsupported HTTP method: ' . $method),
        };

        $this->throwWithContext($response);

        return $response->json() ?? [];
    }

    private function client(?string $deviceId = null): PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleepMs, function ($exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response?->status() >= 500);
            });

        if (filled($this->username) || filled($this->password)) {
            $client = $client->withBasicAuth((string) $this->username, (string) $this->password);
        }

        if (filled($deviceId)) {
            $client = $client->withHeaders(['X-Device-Id' => $deviceId]);
        }

        return $client;
    }

    private function throwWithContext(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $payload = $response->json();
        $code = Arr::get($payload, 'code');
        $message = Arr::get($payload, 'message');

        if (filled($code) || filled($message)) {
            throw new RuntimeException(sprintf(
                'GOWA request failed (%d%s%s)',
                $response->status(),
                filled($code) ? ', code: ' . $code : '',
                filled($message) ? ', message: ' . $message : ''
            ));
        }

        $response->throw();
    }
}
