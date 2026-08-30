<?php

namespace App\Http\Controllers;

use App\Models\GatePass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GatePassController extends Controller
{
    public function index(Request $request): View
    {
        $query = GatePass::query()->latest('issue_date');

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'issue_date' => $request->string('issue_date')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q) use ($search) {
                $q->where('gate_pass_number', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('purpose', 'like', $search)
                    ->orWhere('address', 'like', $search)
                    ->orWhere('prepared_by', 'like', $search)
                    ->orWhere('checked_by', 'like', $search);
            });
        });

        if ($filters['issue_date'] !== '') {
            $today = now()->startOfDay();
            $weekStart = $today->copy()->startOfWeek();
            $weekEnd = $today->copy()->endOfWeek();

            $query->when($filters['issue_date'] === 'today', function ($q) use ($today) {
                $q->whereDate('issue_date', $today);
            })->when($filters['issue_date'] === 'this_week', function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('issue_date', [$weekStart, $weekEnd]);
            })->when($filters['issue_date'] === 'this_month', function ($q) use ($today) {
                $q->whereMonth('issue_date', $today->month)
                    ->whereYear('issue_date', $today->year);
            })->when($filters['issue_date'] === 'future', function ($q) use ($today) {
                $q->whereDate('issue_date', '>', $today);
            });
        }

        $gatePasses = $query->paginate(15)->withQueryString();

        return view('gate-passes.index', [
            'gatePasses' => $gatePasses,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('gate-passes.create', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'checked_by' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['gate_pass_number'] = $this->generatePassNo();
        $validated['prepared_by'] = $validated['prepared_by'] ?: Auth::user()->name;

        GatePass::create($validated);

        return redirect()->route('gate-passes.index')->with('success', 'Gate pass created successfully.');
    }

    public function edit(GatePass $gatePass): View
    {
        return view('gate-passes.edit', [
            'gatePass' => $gatePass,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, GatePass $gatePass): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'checked_by' => ['nullable', 'string', 'max:255'],
        ]);

        $gatePass->update($validated);

        return redirect()->route('gate-passes.index')->with('success', 'Gate pass updated successfully.');
    }

    public function destroy(GatePass $gatePass): RedirectResponse
    {
        $gatePass->delete();

        return redirect()->route('gate-passes.index')->with('success', 'Gate pass deleted successfully.');
    }

    public function print(GatePass $gatePass): View
    {
        return view('gate-passes.print', [
            'gatePass' => $gatePass,
        ]);
    }

    public function dashboard(): View
    {
        $total = GatePass::count();
        $thisMonth = GatePass::whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->count();
        $checked = GatePass::whereNotNull('checked_by')
            ->where('checked_by', '<>', '')
            ->count();
        $pendingCheck = (int) $total - (int) $checked;
        $totalQuantity = (int) GatePass::sum('quantity');

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $weekEnd = $today->copy()->endOfWeek();

        $todayPasses = GatePass::whereDate('issue_date', $today)
            ->orderBy('issue_date', 'asc')
            ->take(5)
            ->get();

        $thisWeekPasses = GatePass::whereBetween('issue_date', [$tomorrow, $weekEnd])
            ->orderBy('issue_date', 'asc')
            ->take(5)
            ->get();

        $thisMonthPasses = GatePass::whereMonth('issue_date', $today->month)
            ->whereYear('issue_date', $today->year)
            ->latest('issue_date')
            ->take(5)
            ->get();

        $recentlyPrepared = GatePass::whereNotNull('prepared_by')
            ->where('prepared_by', '<>', '')
            ->latest('issue_date')
            ->take(5)
            ->get();

        $pendingCheckPasses = GatePass::where(function ($q) {
            $q->whereNull('checked_by')->orWhere('checked_by', '');
        })->latest('issue_date')
            ->take(5)
            ->get();

        $mapPass = static function (GatePass $pass): array {
            return [
                'title' => $pass->name,
                'subtitle' => 'Pass '.$pass->gate_pass_number.' · '.Str::limit($pass->purpose, 40),
                'url' => route('gate-passes.edit', $pass),
                'badge' => $pass->isChecked()
                    ? ['text' => 'Checked', 'class' => 'badge-success']
                    : ['text' => 'Pending', 'class' => 'badge-warning'],
            ];
        };

        return view('gate-passes.dashboard', [
            'total' => $total,
            'thisMonth' => $thisMonth,
            'checked' => $checked,
            'pendingCheck' => $pendingCheck,
            'totalQuantity' => $totalQuantity,
            'todayPasses' => $todayPasses->map($mapPass)->all(),
            'thisWeekPasses' => $thisWeekPasses->map($mapPass)->all(),
            'thisMonthPasses' => $thisMonthPasses->map($mapPass)->all(),
            'recentlyPrepared' => $recentlyPrepared->map($mapPass)->all(),
            'pendingCheckPasses' => $pendingCheckPasses->map($mapPass)->all(),
            'viewAllTodayRoute' => route('gate-passes.index', ['issue_date' => 'today']),
            'viewAllWeekRoute' => route('gate-passes.index', ['issue_date' => 'this_week']),
            'viewAllMonthRoute' => route('gate-passes.index', ['issue_date' => 'this_month']),
            'viewAllPreparedRoute' => route('gate-passes.index'),
            'viewAllPendingRoute' => route('gate-passes.index'),
        ]);
    }

    private function generatePassNo(): string
    {
        $year = now()->format('Y');
        $max = GatePass::where('gate_pass_number', 'like', "GP-{$year}-%")
            ->orderByDesc('gate_pass_number')
            ->value('gate_pass_number');
        $sequence = $max ? ((int) substr($max, -4) + 1) : 1;

        do {
            $passNo = 'GP-'.$year.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (GatePass::where('gate_pass_number', $passNo)->exists());

        return $passNo;
    }
}
