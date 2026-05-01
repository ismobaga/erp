<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client for the WhatsApp API MultiDevice (v8.x).
 *
 * Configuration keys (config/services.php → 'whatsapp'):
 *   url       – Base URL of the WhatsApp API server (e.g. http://localhost:3000)
 *   device_id – Default device ID sent as X-Device-Id header
 *   username  – HTTP Basic-Auth username
 *   password  – HTTP Basic-Auth password
 */
class WhatsAppService
{
    private PendingRequest $http;

    public function __construct()
    {
        $base     = rtrim((string) config('services.whatsapp.url', 'http://localhost:3000'), '/');
        $username = (string) config('services.whatsapp.username', '');
        $password = (string) config('services.whatsapp.password', '');
        $deviceId = (string) config('services.whatsapp.device_id', '');

        $this->http = Http::baseUrl($base)
            ->withHeaders(array_filter(['X-Device-Id' => $deviceId]))
            ->when(
                $username !== '' && $password !== '',
                fn(PendingRequest $req) => $req->withBasicAuth($username, $password),
            )
            ->timeout(30)
            ->acceptJson();
    }

    /**
     * Send a plain-text WhatsApp message.
     *
     * @param  string  $phone     Recipient JID, e.g. "6289685028129@s.whatsapp.net"
     * @param  string  $message   Message body
     * @param  array<string,mixed>  $extra   Optional extra payload fields (reply_message_id, mentions, …)
     */
    public function sendMessage(string $phone, string $message, array $extra = []): Response
    {
        $payload = array_merge(['phone' => $phone, 'message' => $message], $extra);

        $response = $this->http->post('/send/message', $payload);

        $this->assertSuccess($response, 'sendMessage');

        return $response;
    }

    /**
     * Send an image by URL.
     *
     * @param  string  $phone      Recipient JID
     * @param  string  $imageUrl   Publicly accessible image URL
     * @param  string  $caption    Optional caption
     * @param  array<string,mixed>  $extra
     */
    public function sendImageUrl(string $phone, string $imageUrl, string $caption = '', array $extra = []): Response
    {
        $payload = array_merge([
            'phone'     => $phone,
            'image_url' => $imageUrl,
            'caption'   => $caption,
        ], $extra);

        $response = $this->http->post('/send/image', $payload);

        $this->assertSuccess($response, 'sendImageUrl');

        return $response;
    }

    /**
     * Return the connection / login status of the configured device.
     */
    public function status(): Response
    {
        return $this->http->get('/app/status');
    }

    /**
     * Return whether the configured device is currently connected and logged in.
     */
    public function isConnected(): bool
    {
        try {
            $response = $this->status();

            if (! $response->successful()) {
                return false;
            }

            $results = $response->json('results', []);

            return (bool) ($results['is_connected'] ?? false)
                && (bool) ($results['is_logged_in'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Ensure the phone number is formatted as a WhatsApp JID.
     * If the value already contains "@", it is returned as-is.
     * Otherwise "@s.whatsapp.net" is appended.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9@.]/', '', $phone) ?? $phone;

        return str_contains($phone, '@') ? $phone : $phone . '@s.whatsapp.net';
    }

    /**
     * Throw a RuntimeException when the API response indicates failure.
     */
    private function assertSuccess(Response $response, string $operation): void
    {
        if (! $response->successful()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? 'Unknown error';

            throw new RuntimeException(
                sprintf('[WhatsApp] %s failed (%d): %s', $operation, $response->status(), $message),
            );
        }
    }
}
