<?php

namespace App\Http\Controllers;

use App\Services\DlrSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class DlrController extends Controller
{
    public function __construct(private DlrSyncService $dlrSyncService) {}

    public function index(): View
    {
        $companies = [
            'DUN160' => 'DUNCAN BROTHERS LTD',
            'DUN162' => 'THE ALLYNUGGER TEA CO. LTD.',
            'DUN163' => 'AMO TEA CO. LTD.',
            'DUN164' => 'THE CHANDPORE TEA CO.LTD.',
            'DUN165' => 'THE MAZDEHEE TEA CO. LTD.',
            'DUN166' => 'SURMAH VALLEY TEA CO. LTD.',
            'DUN167' => 'THE LUNGLA (SYLHET) TEA CO. LTD.',
            'DUN168' => 'EASTLAND CAMELLIA LTD.',
        ];

        $divisions = [
            'DUN160' => [
                '0001' => 'HEAD OFFICE',
                '0004' => 'AMO TEA ESTATE',
                '0002' => 'SHUMSHERNUGGER TEA ESTATE',
            ],
            'DUN162' => [
                '0005' => 'ALLYNUGGER TEA ESTATE',
                '0006' => 'CHATLAPORE TEA ESTATE',
            ],
            'DUN163' => [
                '0007' => 'NALUA TEA ESTATE',
            ],
            'DUN164' => [
                '0008' => 'CHANDPORE TEA ESTATE',
            ],
            'DUN165' => [
                '0009' => 'MAZDEHEE TEA ESTATE',
            ],
            'DUN166' => [
                '0010' => 'LUSKERPORE TEA ESTATE',
                '0013' => 'SILLOAH TEA ESTATE',
                '0014' => 'RAJKIE TEA ESTATE',
            ],
            'DUN167' => [
                '0011' => 'KARIMPORE TEA ESTATE',
                '0015' => 'HINGAJEA TEA ESTATE',
                '0016' => 'LUNGLA TEA ESTATE',
                '0017' => 'ETAH TEA ESTATE',
            ],
            'DUN168' => [
                '0012' => 'CHAKLAPUNJI TEA ESTATE',
            ],
        ];

        $records = Session::get('dlr_records', []);
        $filters = Session::get('dlr_filters', [
            'date' => now()->format('d/m/Y'),
            'companycode' => '',
            'estatecode' => '',
        ]);

        if (request()->has('date') || request()->has('companycode') || request()->has('estatecode')) {
            $filters = [
                'date' => request()->get('date', $filters['date']),
                'companycode' => request()->get('companycode', $filters['companycode']),
                'estatecode' => request()->get('estatecode', $filters['estatecode']),
            ];
        }

        return view('dlrs.sync', [
            'companies' => $companies,
            'divisions' => $divisions,
            'records' => $records,
            'filters' => $filters,
        ]);
    }

    public function fetch(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'string', 'max:20'],
            'companycode' => ['required', 'string', 'max:20'],
            'estatecode' => ['required', 'string', 'max:20'],
        ]);

        try {
            $records = $this->dlrSyncService->fetchData(
                $validated['date'],
                $validated['companycode'],
                $validated['estatecode']
            );

            Session::put('dlr_records', $records);
            Session::put('dlr_filters', $validated);

            return redirect()->route('dashboard.dlr-sync.index');
        } catch (\Throwable $e) {
            return redirect()
                ->route('dashboard.dlr-sync.index')
                ->withInput()
                ->with('error', 'Failed to fetch data: '.$e->getMessage());
        }
    }

    public function manage(Request $request): View
    {
        $companies = [
            'DUN160' => 'DUNCAN BROTHERS LTD',
            'DUN162' => 'THE ALLYNUGGER TEA CO. LTD.',
            'DUN163' => 'AMO TEA CO. LTD.',
            'DUN164' => 'THE CHANDPORE TEA CO.LTD.',
            'DUN165' => 'THE MAZDEHEE TEA CO. LTD.',
            'DUN166' => 'SURMAH VALLEY TEA CO. LTD.',
            'DUN167' => 'THE LUNGLA (SYLHET) TEA CO. LTD.',
            'DUN168' => 'EASTLAND CAMELLIA LTD.',
        ];

        $divisions = [
            'DUN160' => [
                '0001' => 'HEAD OFFICE',
                '0004' => 'AMO TEA ESTATE',
                '0002' => 'SHUMSHERNUGGER TEA ESTATE',
            ],
            'DUN162' => [
                '0005' => 'ALLYNUGGER TEA ESTATE',
                '0006' => 'CHATLAPORE TEA ESTATE',
            ],
            'DUN163' => [
                '0007' => 'NALUA TEA ESTATE',
            ],
            'DUN164' => [
                '0008' => 'CHANDPORE TEA ESTATE',
            ],
            'DUN165' => [
                '0009' => 'MAZDEHEE TEA ESTATE',
            ],
            'DUN166' => [
                '0010' => 'LUSKERPORE TEA ESTATE',
                '0013' => 'SILLOAH TEA ESTATE',
                '0014' => 'RAJKIE TEA ESTATE',
            ],
            'DUN167' => [
                '0011' => 'KARIMPORE TEA ESTATE',
                '0015' => 'HINGAJEA TEA ESTATE',
                '0016' => 'LUNGLA TEA ESTATE',
                '0017' => 'ETAH TEA ESTATE',
            ],
            'DUN168' => [
                '0012' => 'CHAKLAPUNJI TEA ESTATE',
            ],
        ];

        $filters = $request->only(['companycode', 'estatecode', 'month']);
        $filters['companycode'] = $filters['companycode'] ?? '';
        $filters['estatecode'] = $filters['estatecode'] ?? '';
        $filters['month'] = $filters['month'] ?? now()->format('Y-m');

        $dates = [];

        if ($filters['companycode'] && $filters['estatecode'] && $filters['month']) {
            $monthParts = explode('-', $filters['month']);
            $year = $monthParts[0] ?? now()->year;
            $month = $monthParts[1] ?? now()->month;

            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            $dates = $this->dlrSyncService->getDistinctDates(
                $filters['companycode'],
                $filters['estatecode'],
                $startDate,
                $endDate
            );

            foreach ($dates as &$date) {
                $exists = DB::connection('sqlsrv')
                    ->table('tblAccountInfo')
                    ->where('COMPANYCODE', $filters['companycode'])
                    ->where('DIVISIONCODE', $filters['estatecode'])
                    ->where('FILTERDATE', $date)
                    ->exists();

                $date['status'] = $exists ? 'SYNCED' : 'NOT SYNCED';
            }
        }

        return view('dlrs.manage', [
            'companies' => $companies,
            'divisions' => $divisions,
            'dates' => $dates,
            'filters' => $filters,
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        $records = Session::get('dlr_records', []);
        $filters = Session::get('dlr_filters', []);

        if (empty($records)) {
            return redirect()
                ->route('dashboard.dlr-sync.index')
                ->with('error', 'No data available to sync. Please fetch data first.');
        }

        $inserted = $this->dlrSyncService->sync($records, $filters);

        Session::forget('dlr_records');

        return redirect()
            ->route('dashboard.dlr-sync.index')
            ->with('success', "Successfully synced {$inserted} record(s) to tblAccountInfo.");
    }
}
