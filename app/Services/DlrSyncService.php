<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DlrSyncService
{
    public function fetchData(string $date, string $companyCode, string $divisionCode): array
    {
        $results = DB::connection('fraerp')
            ->select('EXEC FRA_SAGE_SYNC @P_DATE = :date, @P_COMPANYCODE = :companycode, @P_DIVISIONCODE = :divisioncode', [
                'date' => $date,
                'companycode' => $companyCode,
                'divisioncode' => $divisionCode,
            ]);

        $records = [];

        foreach ($results as $row) {
            $records[] = [
                'COMPANYCODE' => $row->COMPANYCODE ?? null,
                'COMPANYNAME' => $row->COMPANYNAME ?? null,
                'DIVISIONCODE' => $row->DIVISIONCODE ?? null,
                'DIVISIONNAME' => $row->DIVISIONNAME ?? null,
                'FILTERDATE' => $row->FILTERDATE ?? $date,
                'ACCGROUPCODE' => $row->ACCGROUPCODE ?? null,
                'ACCGROUPDESC' => $row->ACCGROUPDESC ?? null,
                'CLUSTERID' => $row->CLUSTERID ?? null,
                'CLUSTERCODE' => $row->CLUSTERCODE ?? null,
                'TAG' => $row->TAG ?? null,
                'ACCSUBGROUPCODE' => $row->ACCSUBGROUPCODE ?? null,
                'ACCSUBGROUPDESC' => $row->ACCSUBGROUPDESC ?? null,
                'AREACODE' => $row->AREACODE ?? null,
                'AREADESC' => $row->AREADESC ?? null,
                'TYPECAT' => $row->TYPECAT ?? null,
                'SEX' => $row->SEX ?? null,
                'HAZIRA' => $row->HAZIRA ?? null,
                'AMOUNT' => $row->AMOUNT ?? null,
            ];
        }

        if (empty($records)) {
            throw new InvalidArgumentException('No records returned from stored procedure.');
        }

        return $records;
    }

    public function getDistinctDates(string $companyCode, string $divisionCode, string $startDate, string $endDate): array
    {
        $dates = [];
        $current = Carbon::createFromFormat('Y-m-d', $startDate);
        $end = Carbon::createFromFormat('Y-m-d', $endDate);

        while ($current->lte($end)) {
            $dateString = $current->format('d/m/Y');

            if ($this->hasDataForDate($dateString, $companyCode, $divisionCode)) {
                $dates[$dateString] = [
                    'date' => $dateString,
                    'status' => 'NOT SYNCED',
                ];
            }

            $current->addDay();
        }

        return $dates;
    }

    public function hasDataForDate(string $date, string $companyCode, string $divisionCode): bool
    {
        try {
            $results = DB::connection('fraerp')
                ->select('EXEC FRA_SAGE_SYNC @P_DATE = :date, @P_COMPANYCODE = :companycode, @P_DIVISIONCODE = :divisioncode', [
                    'date' => $date,
                    'companycode' => $companyCode,
                    'divisioncode' => $divisionCode,
                ]);

            return ! empty($results);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function sync(array $records, array $filters = []): int
    {
        if (empty($records)) {
            return 0;
        }

        $companyCode = $filters['companycode'] ?? $records[0]['COMPANYCODE'] ?? null;
        $divisionCode = $filters['estatecode'] ?? $records[0]['DIVISIONCODE'] ?? null;
        $filterDate = $filters['date'] ?? $records[0]['FILTERDATE'] ?? null;

        if ($companyCode && $divisionCode && $filterDate) {
            DB::connection('sqlsrv')
                ->table('tblAccountInfo')
                ->where('COMPANYCODE', $companyCode)
                ->where('DIVISIONCODE', $divisionCode)
                ->where('FILTERDATE', $filterDate)
                ->delete();
        }

        $columns = 18;
        $maxParameters = 2000;
        $batchSize = (int) floor($maxParameters / $columns);

        $chunks = array_chunk($records, $batchSize);
        $inserted = 0;

        foreach ($chunks as $chunk) {
            DB::connection('sqlsrv')
                ->table('tblAccountInfo')
                ->insert($chunk);

            $inserted += count($chunk);
        }

        return $inserted;
    }
}
