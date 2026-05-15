<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('portal_token_hash', 64)->nullable()->after('portal_token')->index();
            $table->timestamp('portal_token_expires_at')->nullable()->after('portal_token_hash');
            $table->timestamp('portal_token_last_used_at')->nullable()->after('portal_token_expires_at');
            $table->timestamp('portal_token_revoked_at')->nullable()->after('portal_token_last_used_at');
        });

        DB::table('clients')->select(['id', 'portal_token'])->lazyById()->each(function ($client): void {
            $plain = (string) ($client->portal_token ?? '');

            if ($plain === '') {
                $plain = Str::random(64);
            } elseif (str_starts_with($plain, 'eyJ')) {
                try {
                    $plain = Crypt::decryptString($plain);
                } catch (Throwable) {
                    // Keep existing value when decryption fails; hash+encryption below will normalize it.
                }
            }

            DB::table('clients')
                ->where('id', $client->id)
                ->update([
                    'portal_token' => Crypt::encryptString($plain),
                    'portal_token_hash' => hash('sha256', $plain),
                    'portal_token_expires_at' => Carbon::now()->addDays((int) config('erp.portal.token_ttl_days', 180)),
                    'portal_token_revoked_at' => null,
                ]);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->unique('portal_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropUnique(['portal_token_hash']);
            $table->dropColumn([
                'portal_token_hash',
                'portal_token_expires_at',
                'portal_token_last_used_at',
                'portal_token_revoked_at',
            ]);
        });
    }
};
