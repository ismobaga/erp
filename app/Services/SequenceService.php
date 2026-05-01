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

                DB::table('sequences')->insert($insert);

                return 1;
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
