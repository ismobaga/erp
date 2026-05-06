<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappMessageLog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = $this->setUpCompany();
        $this->company->update([
            'whatsapp_device_id' => '628111222333@s.whatsapp.net',
            'whatsapp_enabled'   => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Basic message handling
    // -------------------------------------------------------------------------

    public function test_webhook_creates_conversation_and_message_for_inbound_text(): void
    {
        $payload = $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
            overrides: [
                'chat_id'     => '628987654321@s.whatsapp.net',
                'from'        => '628987654321@s.whatsapp.net',
                'from_name'   => 'Test Client',
                'is_from_me'  => false,
                'text'        => 'Bonjour, je voudrais un devis.',
                'timestamp'   => now()->toIso8601String(),
            ],
        );

        $response = $this->postJson('/webhooks/gowa', $payload);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'company_id'   => $this->company->id,
            'chat_id'      => '628987654321@s.whatsapp.net',
            'contact_name' => 'Test Client',
        ]);

        $conversation = WhatsappConversation::first();
        $this->assertNotNull($conversation);

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conversation->id,
            'direction'       => 'inbound',
            'type'            => 'text',
            'body'            => 'Bonjour, je voudrais un devis.',
        ]);
    }

    public function test_webhook_reuses_existing_conversation(): void
    {
        $chatId = '628111000111@s.whatsapp.net';

        // First message creates conversation.
        $this->postJson('/webhooks/gowa', $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
            overrides: ['chat_id' => $chatId, 'from' => $chatId, 'text' => 'Message 1'],
        ))->assertOk();

        // Second message reuses same conversation.
        $this->postJson('/webhooks/gowa', $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
            overrides: ['chat_id' => $chatId, 'from' => $chatId, 'text' => 'Message 2'],
        ))->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 1);
        $this->assertDatabaseCount('whatsapp_messages', 2);
    }

    public function test_webhook_ignores_unknown_device(): void
    {
        $payload = $this->messagePayload(
            event: 'message',
            deviceId: '999999999999@s.whatsapp.net',
        );

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 0);
    }

    public function test_webhook_ignores_disabled_whatsapp_company(): void
    {
        $this->company->update(['whatsapp_enabled' => false]);

        $payload = $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
        );

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 0);
    }

    // -------------------------------------------------------------------------
    // ACK handling
    // -------------------------------------------------------------------------

    public function test_webhook_ack_updates_message_status(): void
    {
        $msgId = 'ABCD1234';
        $chatId = '628111333222@s.whatsapp.net';

        // Create an existing message to update.
        $conversation = WhatsappConversation::withoutCompanyScope()->create([
            'company_id' => $this->company->id,
            'chat_id'    => $chatId,
            'status'     => 'open',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => $msgId,
            'direction'       => 'outbound',
            'event_type'      => 'message',
            'type'            => 'text',
            'body'            => 'Test outbound',
            'ack_status'      => 'server',
            'sent_at'         => now(),
        ]);

        $ackPayload = [
            'event'     => 'message.ack',
            'device_id' => $this->company->whatsapp_device_id,
            'payload'   => [
                'id'  => $msgId,
                'ack' => 3, // read
            ],
        ];

        $this->postJson('/webhooks/gowa', $ackPayload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'message_id' => $msgId,
            'ack_status' => 'read',
        ]);
    }

    public function test_webhook_ack_updates_message_log(): void
    {
        $msgId = 'LOG_MSG_001';

        // Create a WhatsappMessageLog that would be created when sending an invoice.
        WhatsappMessageLog::withoutCompanyScope()->create([
            'company_id'      => $this->company->id,
            'phone'           => '628111555@s.whatsapp.net',
            'type'            => 'file',
            'message'         => 'Facture',
            'status'          => 'sent',
            'gowa_message_id' => $msgId,
            'ack_status'      => 'server',
            'sent_at'         => now(),
        ]);

        $ackPayload = [
            'event'     => 'message.ack',
            'device_id' => $this->company->whatsapp_device_id,
            'payload'   => [
                'id'  => $msgId,
                'ack' => 2, // delivered
            ],
        ];

        $this->postJson('/webhooks/gowa', $ackPayload)->assertOk();

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'gowa_message_id' => $msgId,
            'ack_status'      => 'delivered',
        ]);
    }

    // -------------------------------------------------------------------------
    // Message reaction
    // -------------------------------------------------------------------------

    public function test_webhook_reaction_creates_message_record(): void
    {
        $chatId = '628111444555@s.whatsapp.net';

        $payload = [
            'event'     => 'message.reaction',
            'device_id' => $this->company->whatsapp_device_id,
            'payload'   => [
                'id'          => 'REACT001',
                'chat_id'     => $chatId,
                'from'        => $chatId,
                'is_from_me'  => false,
                'timestamp'   => now()->toIso8601String(),
                'reaction'    => ['emoji' => '👍'],
            ],
        ];

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'event_type' => 'message.reaction',
            'type'       => 'reaction',
            'body'       => '👍',
        ]);
    }

    // -------------------------------------------------------------------------
    // Message revoked / deleted
    // -------------------------------------------------------------------------

    public function test_webhook_revoked_marks_message_as_revoked(): void
    {
        $msgId = 'REVOKE_01';

        $conversation = WhatsappConversation::withoutCompanyScope()->create([
            'company_id' => $this->company->id,
            'chat_id'    => '628199@s.whatsapp.net',
            'status'     => 'open',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => $msgId,
            'direction'       => 'inbound',
            'event_type'      => 'message',
            'type'            => 'text',
            'body'            => 'Original',
            'ack_status'      => 'pending',
            'sent_at'         => now(),
        ]);

        $payload = [
            'event'     => 'message.revoked',
            'device_id' => $this->company->whatsapp_device_id,
            'payload'   => ['id' => $msgId],
        ];

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'message_id' => $msgId,
            'event_type' => 'message.revoked',
            'type'       => 'revoked',
        ]);
    }

    public function test_webhook_deleted_removes_message(): void
    {
        $msgId = 'DELETE_01';

        $conversation = WhatsappConversation::withoutCompanyScope()->create([
            'company_id' => $this->company->id,
            'chat_id'    => '628299@s.whatsapp.net',
            'status'     => 'open',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id'      => $msgId,
            'direction'       => 'inbound',
            'event_type'      => 'message',
            'type'            => 'text',
            'body'            => 'To be deleted',
            'ack_status'      => 'pending',
            'sent_at'         => now(),
        ]);

        $payload = [
            'event'     => 'message.deleted',
            'device_id' => $this->company->whatsapp_device_id,
            'payload'   => ['id' => $msgId],
        ];

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseMissing('whatsapp_messages', ['message_id' => $msgId]);
    }

    // -------------------------------------------------------------------------
    // Client auto-linking
    // -------------------------------------------------------------------------

    public function test_webhook_links_conversation_to_existing_client(): void
    {
        $phone  = '628777888999';
        $chatId = $phone . '@s.whatsapp.net';

        $client = Client::withoutCompanyScope()->create([
            'company_id'   => $this->company->id,
            'type'         => 'company',
            'company_name' => 'Auto-Link Corp',
            'phone'        => $phone,
            'status'       => 'active',
        ]);

        $payload = $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
            overrides: ['chat_id' => $chatId, 'from' => $chatId, 'text' => 'Hello'],
        );

        $this->postJson('/webhooks/gowa', $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'chat_id'   => $chatId,
            'client_id' => $client->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------

    public function test_webhook_requires_secret_when_configured(): void
    {
        config(['gowa.webhook_secret' => 'super-secret']);

        $payload = $this->messagePayload(
            event: 'message',
            deviceId: $this->company->whatsapp_device_id,
        );

        // Without secret → 401.
        $this->postJson('/webhooks/gowa', $payload)->assertStatus(401);

        // With wrong secret → 401.
        $this->withHeader('X-Gowa-Secret', 'wrong-secret')
            ->postJson('/webhooks/gowa', $payload)
            ->assertStatus(401);

        // With correct secret → 200.
        $this->withHeader('X-Gowa-Secret', 'super-secret')
            ->postJson('/webhooks/gowa', $payload)
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function messagePayload(
        string $event,
        string $deviceId,
        array $overrides = [],
    ): array {
        return [
            'event'     => $event,
            'device_id' => $deviceId,
            'payload'   => array_merge([
                'id'         => 'MSG_' . uniqid(),
                'chat_id'    => '628111222333@s.whatsapp.net',
                'from'       => '628111222333@s.whatsapp.net',
                'from_name'  => 'Sender',
                'is_from_me' => false,
                'text'       => 'Hello',
                'timestamp'  => now()->toIso8601String(),
            ], $overrides),
        ];
    }
}
