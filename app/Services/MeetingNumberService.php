<?php

namespace App\Services;

use App\Models\Meeting;
use Illuminate\Support\Facades\DB;

class MeetingNumberService
{
    public function generate(Meeting $meeting): string
    {
        $year = $meeting->meeting_date->format('Y');
        $lastNumber = DB::table('meetings')
            ->whereYear('meeting_date', $year)
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING_INDEX(meeting_no, '-', -1) AS UNSIGNED)"));

        $nextNumber = $lastNumber ? $lastNumber + 1 : 1;

        return sprintf('MTG-%s-%05d', $year, $nextNumber);
    }
}
