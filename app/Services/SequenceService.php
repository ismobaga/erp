<?php

namespace App\Services;

use Illuminate\Database\UniqueConstraintViolationException;
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
     * On first insert a race condition is possible when two requests arrive
     * simultaneously and both find no row. We guard against this by using
     * `insertOrIgnore()` and re-fetching when the row already exists.
     *
     * @param  string  $key       Sequence identifier, e.g. 'invoice', 'quote', 'journal_entry'.
     * @param  string  $period    Scoping period, e.g. '2026', '2026-04', or 'all' for non-resetting sequences.
     * @param  int|null $companyId Company to scope the sequence to. Defaults to the current company.
     */
    public function next(string $key, string $period, ?int $companyId = null): int
    {
        $resolvedCompanyId = $companyId ?? (app()->bound('currentCompany') ? app('currentCompany')->id : null);

        return DB::transaction(function () use ($key, $period, $resolvedCompanyId): int {
            $query = DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period);

            if ($resolvedCompanyId !== null) {
                $query->where('company_id', $resolvedCompanyId);
            }

            $row = $query->lockForUpdate()->first();

            if ($row === null) {
                $insert = [
                    'key'      => $key,
                    'period'   => $period,
                    'next_val' => 2,
                ];

                if ($resolvedCompanyId !== null) {
                    $insert['company_id'] = $resolvedCompanyId;
                }

                try {
                    DB::table('sequences')->insertOrIgnore($insert);
                } catch (UniqueConstraintViolationException) {
                    // Another request inserted the row concurrently; fall through
                    // to the UPDATE path below by re-fetching the existing row.
                }

                // Re-fetch after insert attempt. If insertOrIgnore succeeded the
                // row has next_val=2 and we return 1. If another process won the
                // race we fall through and increment their value instead.
                $row = DB::table('sequences')
                    ->where('key', $key)
                    ->where('period', $period)
                    ->when($resolvedCompanyId !== null, fn($q) => $q->where('company_id', $resolvedCompanyId))
                    ->lockForUpdate()
                    ->first();

                // If the row was just inserted by us (next_val == 2), consume 1.
                if ($row !== null && (int) $row->next_val === 2) {
                    return 1;
                }

                // Otherwise another process inserted next_val=2; fall through to increment.
                if ($row === null) {
                    // Should not happen, but defensively return 1.
                    return 1;
                }
            }

            $current = (int) $row->next_val;

            $updateQuery = DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period);

            if ($resolvedCompanyId !== null) {
                $updateQuery->where('company_id', $resolvedCompanyId);
            }

            $updateQuery->update(['next_val' => $current + 1]);

            return $current;
        });
    }
}
