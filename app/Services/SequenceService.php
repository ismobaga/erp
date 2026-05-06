<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates monotonically increasing, gap-free sequence numbers in a
 * concurrency-safe manner using a dedicated `sequences` table with
 * row-level locking.
 *
 * In a multi-tenant setup sequences are scoped to the current company so that
 * each company has its own independent numbering series.
 */
class SequenceService
{
    /**
     * Acquire and return the next integer value for a given (company_id, key, period)
     * combination.  A `SELECT … FOR UPDATE` inside a transaction ensures
     * that two concurrent requests cannot receive the same value.
     *
     * @param  string  $key       Sequence identifier, e.g. 'invoice', 'quote', 'journal_entry'.
     * @param  string  $period    Scoping period, e.g. '2026', '2026-04', or 'all' for non-resetting sequences.
     * @param  int|null $companyId Company to scope the sequence to. Defaults to the current company.
     */
    public function next(string $key, string $period, ?int $companyId = null): int
    {
        $resolvedCompanyId = $companyId ?? (app()->bound('currentCompany') ? app('currentCompany')->id : null);
        if ($resolvedCompanyId === null) {
            throw new \RuntimeException(
                "SequenceService::next() requires a company ID. Bind 'currentCompany' before calling this method, " .
                "or pass \$companyId explicitly (required in queue workers and Artisan commands)."
            );
        }

        return DB::transaction(function () use ($key, $period, $resolvedCompanyId): int {
            $row = DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period)
                ->where('company_id', $resolvedCompanyId)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                $inserted = DB::table('sequences')->insertOrIgnore([
                    'key' => $key,
                    'period' => $period,
                    'company_id' => $resolvedCompanyId,
                    'next_val' => 2,
                ]);

                if ($inserted) {
                    return 1;
                }

                // Another request inserted first; fall through to the update path.
                $row = DB::table('sequences')
                    ->where('key', $key)
                    ->where('period', $period)
                    ->where('company_id', $resolvedCompanyId)
                    ->lockForUpdate()
                    ->first();

                if ($row === null) {
                    return 1;
                }
            }

            $current = (int) $row->next_val;

            DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period)
                ->where('company_id', $resolvedCompanyId)
                ->update(['next_val' => $current + 1]);

            return $current;
        });
    }
}
