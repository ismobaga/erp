<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates monotonically increasing, gap-free sequence numbers in a
 * concurrency-safe manner using a dedicated `sequences` table with
 * row-level locking.
 *
 * This replaces the previous pattern of pluck()-ing all existing numbers
 * into PHP memory and finding the max, which was subject to race conditions
 * and O(n) memory growth.
 */
class SequenceService
{
    /**
     * Acquire and return the next integer value for a given (key, period)
     * combination.  A `SELECT … FOR UPDATE` inside a transaction ensures
     * that two concurrent requests cannot receive the same value.
     *
     * @param  string  $key     Sequence identifier, e.g. 'invoice', 'quote', 'journal_entry'.
     * @param  string  $period  Scoping period, e.g. '2026', '2026-04', or 'all' for non-resetting sequences.
     */
    public function next(string $key, string $period): int
    {
        return DB::transaction(function () use ($key, $period): int {
            $row = DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('sequences')->insert([
                    'key'      => $key,
                    'period'   => $period,
                    'next_val' => 2,
                ]);

                return 1;
            }

            $current = (int) $row->next_val;

            DB::table('sequences')
                ->where('key', $key)
                ->where('period', $period)
                ->update(['next_val' => $current + 1]);

            return $current;
        });
    }
}
