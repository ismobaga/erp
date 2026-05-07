<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\ApiWebhookEvent;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookIngestionController extends Controller
{
    public function __invoke(Request $request, string $source): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:150'],
            'payload' => ['nullable', 'array'],
        ]);

        $token = $request->attributes->get('apiToken');

        $event = ApiWebhookEvent::create([
            'company_id' => app('currentCompany')->id,
            'api_token_id' => $token->id,
            'source' => strtolower($source),
            'event' => $validated['event'],
            'payload_json' => $validated['payload'] ?? [],
            'received_at' => now(),
        ]);

        app(AuditTrailService::class)->log('api_webhook_received', $event, [
            'source' => $event->source,
            'event' => $event->event,
            'api_token_id' => $token->id,
        ]);

        return response()->json([
            'status' => 'accepted',
            'data' => [
                'id' => $event->id,
                'source' => $event->source,
                'event' => $event->event,
                'received_at' => $event->received_at?->toIso8601String(),
            ],
        ], 202);
    }
}
