<?php

namespace Modules\Meetings\Services;

use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;

/**
 * Generates sequential meeting numbers: `MTG-{year}-{00001}`.
 *
 * The sequence is derived from the highest existing number in the same year, so
 * the value is derived rather than stored — there is no counter row to lose.
 *
 * PORTABILITY: the original implementation used MySQL's `SUBSTRING_INDEX`, which
 * does not exist on SQLite. That meant this service — and therefore every code
 * path that creates a meeting — threw on the driver the whole test suite runs on,
 * and nothing caught it because no test created a meeting through the number
 * service. The driver branch below is the same GAP-004 class Phase 2 closed
 * everywhere else.
 */
class MeetingNumberService
{
    private const PREFIX = 'MTG-';

    public function generate(Meeting $meeting): string
    {
        $year = $meeting->meeting_date->format('Y');
        $prefix = self::PREFIX.$year.'-';

        // selectRaw rather than ->max(): the aggregate helper wraps its argument
        // in double quotes, which turns the string literals inside the expression
        // into identifiers and produces a syntax error.
        $lastNumber = DB::table('meetings')
            ->whereYear('meeting_date', $year)
            // Serialises the read-then-write so two concurrent creates do not both
            // read the same maximum. A no-op on SQLite, which is single-writer.
            ->lockForUpdate()
            ->selectRaw('MAX('.$this->trailingNumberExpression($prefix).') AS aggregate')
            ->value('aggregate');

        $nextNumber = $lastNumber ? ((int) $lastNumber) + 1 : 1;

        return sprintf('%s%05d', $prefix, $nextNumber);
    }

    /**
     * The SQL expression yielding the numeric suffix of `meeting_no`.
     *
     * MySQL/MariaDB: take everything after the final `-`.
     * SQLite: `replace()` the known prefix, which is equivalent here because the
     * prefix is deterministic (`MTG-` plus the year already being filtered on).
     */
    protected function trailingNumberExpression(string $prefix): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(REPLACE(meeting_no, '{$prefix}', '') AS INTEGER)";
        }

        return "CAST(SUBSTRING_INDEX(meeting_no, '-', -1) AS UNSIGNED)";
    }
}
