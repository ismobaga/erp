<?php

namespace App\Http\Controllers;

use App\Services\Whatsapp\WhatsappWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Handle an incoming GoWA webhook notification.
     *
     * The endpoint is public (no authentication) but optionally validated via
     * a shared secret sent in the X-Gowa-Secret header.
     */
    public function __invoke(Request $request, WhatsappWebhookProcessor $processor): JsonResponse
    {
        // Optional HMAC / shared-secret verification.
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook rejected: invalid secret.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload'], 400);
        }

        $processor->process($payload);

        return response()->json(['status' => 'ok']);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('gowa.webhook_secret');

        if (blank($secret)) {
            // No secret configured — accept all requests.
            return true;
        }

        $header = $request->header('X-Gowa-Secret', '');

        return hash_equals((string) $secret, (string) $header);
    }
}
