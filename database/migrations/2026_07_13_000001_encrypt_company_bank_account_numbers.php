<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt companies.bank_account_number at rest, matching the existing
 * treatment of bank_swift_code. Widens the column to TEXT (ciphertext is
 * longer than the original values) and encrypts every existing plaintext row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->text('bank_account_number')->nullable()->change();
        });

        DB::table('companies')
            ->whereNotNull('bank_account_number')
            ->orderBy('id')
            ->each(function (object $company): void {
                if ($this->isAlreadyEncrypted($company->bank_account_number)) {
                    return;
                }

                DB::table('companies')
                    ->where('id', $company->id)
                    ->update([
                        'bank_account_number' => Crypt::encryptString($company->bank_account_number),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('companies')
            ->whereNotNull('bank_account_number')
            ->orderBy('id')
            ->each(function (object $company): void {
                try {
                    $plain = Crypt::decryptString($company->bank_account_number);
                } catch (DecryptException) {
                    return; // Already plaintext.
                }

                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['bank_account_number' => $plain]);
            });
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
