<?php

namespace App\Services\Whatsapp;

use App\Models\Client;
use App\Models\Company;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappMessageLog;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookProcessor
{
    /**
     * Process an incoming GoWA webhook payload.
     *
     * @param  array<string, mixed>  $payload  Full top-level webhook body.
     */
    public function process(array $payload): void
    {
        $event    = (string) ($payload['event'] ?? '');
        $deviceId = (string) ($payload['device_id'] ?? '');

        if (blank($event) || blank($deviceId)) {
            Log::warning('WhatsApp webhook received without event or device_id.', ['payload' => $payload]);

            return;
        }

        // Identify the company by device_id.
        $company = Company::query()
            ->where('whatsapp_device_id', $deviceId)
            ->where('whatsapp_enabled', true)
            ->first();

        if ($company === null) {
            Log::info('WhatsApp webhook received for unknown/disabled device.', ['device_id' => $deviceId, 'event' => $event]);

            return;
        }

        // Bind the company so HasCompanyScope resolves correctly during this call.
        app()->instance('currentCompany', $company);

        $data = (array) ($payload['payload'] ?? []);

        try {
            match ($event) {
                'message'           => $this->handleMessage($company, $data, $payload),
                'message.ack'       => $this->handleAck($company, $data),
                'message.reaction'  => $this->handleReaction($company, $data, $payload),
                'message.edited'    => $this->handleEdited($company, $data, $payload),
                'message.revoked'   => $this->handleRevoked($company, $data),
                'message.deleted'   => $this->handleDeleted($company, $data),
                'call.offer'        => $this->handleCallOffer($company, $data, $payload),
                default             => $this->handleUnknown($event, $data),
            };
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook processing error.', [
                'event'     => $event,
                'device_id' => $deviceId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Event handlers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data     payload object
     * @param  array<string, mixed>  $envelope full webhook body
     */
    private function handleMessage(Company $company, array $data, array $envelope): void
    {
        $chatId   = (string) ($data['chat_id'] ?? '');
        $fromJid  = (string) ($data['from'] ?? '');
        $fromName = (string) ($data['from_name'] ?? '');
        $msgId    = (string) ($data['id'] ?? '');
        $isFromMe = (bool) ($data['is_from_me'] ?? false);

        if (blank($chatId)) {
            return;
        }

        $conversation = $this->findOrCreateConversation($company, $chatId, $fromName);

        // Determine message type and body.
        [$type, $body, $mediaUrl] = $this->extractTypeAndBody($data);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => $msgId,
            'direction'       => $isFromMe ? 'outbound' : 'inbound',
            'event_type'      => 'message',
            'type'            => $type,
            'body'            => $body,
            'media_url'       => $mediaUrl,
            'from_jid'        => $fromJid,
            'ack_status'      => $isFromMe ? 'server' : 'pending',
            'sent_at'         => $this->parseTimestamp($data['timestamp'] ?? null),
            'raw_payload'     => $envelope,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Attempt to match conversation to a client if not yet linked.
        if ($conversation->client_id === null && !$isFromMe) {
            $this->tryLinkClient($conversation, $chatId);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function handleAck(Company $company, array $data): void
    {
        $msgId     = (string) ($data['id'] ?? '');
        $ackLevel  = (int) ($data['ack'] ?? 0);

        if (blank($msgId)) {
            return;
        }

        $ackStatus = $this->ackLevelToStatus($ackLevel);
        $now       = now();

        // Update WhatsappMessage if tracked in conversation.
        $message = WhatsappMessage::query()
            ->where('message_id', $msgId)
            ->first();

        if ($message) {
            $updates = ['ack_status' => $ackStatus];

            if ($ackLevel >= 2 && !$message->delivered_at) {
                $updates['delivered_at'] = $now;
            }

            if ($ackLevel >= 3 && !$message->read_at) {
                $updates['read_at'] = $now;
            }

            $message->update($updates);

            // Refresh last_message_at on the conversation.
            $message->conversation?->update(['last_message_at' => $now]);
        }

        // Also update the outbound message log if it references this GoWA message_id.
        $log = WhatsappMessageLog::forCompany($company->id)
            ->where('gowa_message_id', $msgId)
            ->first();

        if ($log) {
            $logUpdates = ['ack_status' => $ackStatus];

            if ($ackLevel >= 2 && !$log->delivered_at) {
                $logUpdates['delivered_at'] = $now;
            }

            if ($ackLevel >= 3 && !$log->read_at) {
                $logUpdates['read_at'] = $now;
            }

            $log->update($logUpdates);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $envelope
     */
    private function handleReaction(Company $company, array $data, array $envelope): void
    {
        $chatId  = (string) ($data['chat_id'] ?? '');
        $fromJid = (string) ($data['from'] ?? '');
        $msgId   = (string) ($data['id'] ?? '');

        if (blank($chatId)) {
            return;
        }

        $conversation = $this->findOrCreateConversation($company, $chatId);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => $msgId,
            'direction'       => (bool) ($data['is_from_me'] ?? false) ? 'outbound' : 'inbound',
            'event_type'      => 'message.reaction',
            'type'            => 'reaction',
            'body'            => (string) ($data['reaction']['emoji'] ?? ''),
            'from_jid'        => $fromJid,
            'ack_status'      => 'pending',
            'sent_at'         => $this->parseTimestamp($data['timestamp'] ?? null),
            'raw_payload'     => $envelope,
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $envelope
     */
    private function handleEdited(Company $company, array $data, array $envelope): void
    {
        $chatId = (string) ($data['chat_id'] ?? '');
        $msgId  = (string) ($data['id'] ?? '');

        if (blank($chatId) || blank($msgId)) {
            return;
        }

        // Update the existing message body if stored.
        $existing = WhatsappMessage::query()->where('message_id', $msgId)->first();

        if ($existing) {
            $existing->update([
                'body'        => (string) ($data['text'] ?? $data['body'] ?? $existing->body),
                'raw_payload' => $envelope,
            ]);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function handleRevoked(Company $company, array $data): void
    {
        $msgId = (string) ($data['id'] ?? '');

        if (blank($msgId)) {
            return;
        }

        WhatsappMessage::query()
            ->where('message_id', $msgId)
            ->update(['event_type' => 'message.revoked', 'type' => 'revoked']);
    }

    /** @param  array<string, mixed>  $data */
    private function handleDeleted(Company $company, array $data): void
    {
        $msgId = (string) ($data['id'] ?? '');

        if (blank($msgId)) {
            return;
        }

        WhatsappMessage::query()
            ->where('message_id', $msgId)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $envelope
     */
    private function handleCallOffer(Company $company, array $data, array $envelope): void
    {
        $chatId  = (string) ($data['chat_id'] ?? $data['from'] ?? '');
        $fromJid = (string) ($data['from'] ?? '');

        if (blank($chatId)) {
            return;
        }

        $conversation = $this->findOrCreateConversation($company, $chatId);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => (string) ($data['id'] ?? ''),
            'direction'       => 'inbound',
            'event_type'      => 'call.offer',
            'type'            => 'call',
            'body'            => 'Appel entrant',
            'from_jid'        => $fromJid,
            'ack_status'      => 'pending',
            'sent_at'         => $this->parseTimestamp($data['timestamp'] ?? null),
            'raw_payload'     => $envelope,
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    private function handleUnknown(string $event, mixed $data): void
    {
        Log::debug('WhatsApp webhook: unhandled event type.', ['event' => $event]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function findOrCreateConversation(Company $company, string $chatId, string $contactName = ''): WhatsappConversation
    {
        /** @var WhatsappConversation $conversation */
        $conversation = WhatsappConversation::withoutCompanyScope()
            ->firstOrCreate(
                ['company_id' => $company->id, 'chat_id' => $chatId],
                ['contact_name' => $contactName ?: null, 'status' => 'open'],
            );

        if (filled($contactName) && blank($conversation->contact_name)) {
            $conversation->update(['contact_name' => $contactName]);
        }

        return $conversation;
    }

    /**
     * Attempt to link the conversation to a client based on the chat JID.
     * The JID format is `<phone>@s.whatsapp.net`; we extract the phone
     * and match it (or its stripped variant) against clients.
     */
    private function tryLinkClient(WhatsappConversation $conversation, string $chatId): void
    {
        // Only link 1-to-1 chats (groups end with @g.us).
        if (!str_ends_with($chatId, '@s.whatsapp.net')) {
            return;
        }

        $phone = str_replace('@s.whatsapp.net', '', $chatId);

        $client = Client::withoutCompanyScope()
            ->where('company_id', $conversation->company_id)
            ->where(function ($q) use ($phone): void {
                $q->where('phone', $phone)
                  ->orWhere('phone', '+' . $phone);
            })
            ->first();

        if ($client) {
            $conversation->update(['client_id' => $client->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string|null, 2: string|null}
     */
    private function extractTypeAndBody(array $data): array
    {
        // Text message
        if (isset($data['text'])) {
            return ['text', (string) $data['text'], null];
        }

        if (isset($data['body']) && is_string($data['body'])) {
            return ['text', $data['body'], null];
        }

        // Image
        if (isset($data['image'])) {
            $img = (array) $data['image'];

            return ['image', $img['caption'] ?? null, $img['url'] ?? null];
        }

        // Document
        if (isset($data['document'])) {
            $doc = (array) $data['document'];

            return ['document', $doc['filename'] ?? $doc['caption'] ?? null, $doc['url'] ?? null];
        }

        // Audio / Voice
        if (isset($data['audio'])) {
            $audio = (array) $data['audio'];

            return ['audio', null, $audio['url'] ?? null];
        }

        // Video
        if (isset($data['video'])) {
            $vid = (array) $data['video'];

            return ['video', $vid['caption'] ?? null, $vid['url'] ?? null];
        }

        // Sticker
        if (isset($data['sticker'])) {
            return ['sticker', null, null];
        }

        // Location
        if (isset($data['location'])) {
            $loc = (array) $data['location'];

            return ['location', sprintf('%s, %s', $loc['latitude'] ?? '', $loc['longitude'] ?? ''), null];
        }

        // Contact
        if (isset($data['contact'])) {
            return ['contact', null, null];
        }

        return ['text', null, null];
    }

    private function ackLevelToStatus(int $level): string
    {
        return match ($level) {
            1       => 'server',
            2       => 'delivered',
            3       => 'read',
            4       => 'played',
            default => 'pending',
        };
    }

    private function parseTimestamp(mixed $ts): ?\Carbon\Carbon
    {
        if (blank($ts)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse((string) $ts);
        } catch (\Throwable) {
            return null;
        }
    }
}
