<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The portal_token column was accidentally cast as 'encrypted' in the Client
 * model, but an encrypted column cannot be efficiently queried. Since portal
 * tokens are random UUIDs (already unforgeable), at-rest encryption adds no
 * meaningful security while breaking the lookup entirely.
 *
 * This migration normalises any existing encrypted portal_token values back to
 * plain-text UUIDs so that the `resolveClientByToken` controller helper can
 * compare them with a simple WHERE clause.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('clients')->lazyById()->each(function ($client) {
            if (blank($client->portal_token)) {
                return;
            }

            // Detect Laravel-encrypted payloads: they are base64-encoded JSON
            // objects containing exactly the keys "iv", "value", and "mac" (and
            // optionally "tag" for GCM mode).  A plain UUID would not decode to
            // valid JSON with those keys, making this a reliable heuristic.
            $isEncrypted = false;
            if (str_starts_with($client->portal_token, 'eyJ')) {
                $decoded = base64_decode($client->portal_token, strict: true);
                if ($decoded !== false) {
                    $payload = json_decode($decoded, associative: true);
                    $isEncrypted = is_array($payload)
                        && isset($payload['iv'], $payload['value'], $payload['mac']);
                }
            }

            if (!$isEncrypted) {
                return; // Already plain-text; nothing to do.
            }

            try {
                $plain = Crypt::decryptString($client->portal_token);
            } catch (\Throwable) {
                try {
                    $plain = Crypt::decrypt($client->portal_token);
                } catch (\Throwable) {
                    // Cannot decrypt — leave as-is and regenerate via model.
                    Log::warning("portal_token for client {$client->id} could not be decrypted; skipping.");
                    return;
                }
            }

            DB::table('clients')->where('id', $client->id)->update(['portal_token' => $plain]);
        });
    }

    public function down(): void
    {
        // Intentionally irreversible — re-encrypting individual tokens at
        // migration-rollback time is not practical.
    }
};
