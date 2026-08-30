<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\Obligation;
use App\Models\ObligationActivityLog;
use App\Models\ObligationCategory;
use App\Models\ObligationType;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ObligationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Obligation::query()
            ->with(['type', 'category', 'company', 'department', 'location', 'vendor', 'owner', 'backupUser', 'approver']);

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('active', true);
                    });
            });
        }

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'priority' => $request->string('priority')->toString(),
            'risk_level' => $request->string('risk_level')->toString(),
            'obligation_type_id' => $request->integer('obligation_type_id', 0),
            'department_id' => $request->integer('department_id', 0),
            'company_id' => $request->integer('company_id', 0),
            'owner_user_id' => $request->integer('owner_user_id', 0),
            'vendor_id' => $request->integer('vendor_id', 0),
            'expiry_period' => $request->string('expiry_period')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q2) use ($search) {
                $q2->where('obligation_no', 'like', $search)
                    ->orWhere('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        })->when(in_array($filters['status'], ['active', 'upcoming', 'action_required', 'renewal_in_progress', 'pending_approval', 'purchase_in_progress', 'renewed', 'expired', 'cancelled', 'not_required', 'archived'], true), function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when(in_array($filters['priority'], ['low', 'medium', 'high', 'critical'], true), function ($q) use ($filters) {
            $q->where('priority', $filters['priority']);
        })->when(in_array($filters['risk_level'], ['low', 'medium', 'high', 'critical'], true), function ($q) use ($filters) {
            $q->where('risk_level', $filters['risk_level']);
        })->when($filters['obligation_type_id'] > 0, function ($q) use ($filters) {
            $q->where('obligation_type_id', $filters['obligation_type_id']);
        })->when($filters['department_id'] > 0, function ($q) use ($filters) {
            $q->where('department_id', $filters['department_id']);
        })->when($filters['company_id'] > 0, function ($q) use ($filters) {
            $q->where('company_id', $filters['company_id']);
        })->when($filters['owner_user_id'] > 0, function ($q) use ($filters) {
            $q->where('owner_user_id', $filters['owner_user_id']);
        })->when($filters['vendor_id'] > 0, function ($q) use ($filters) {
            $q->where('vendor_id', $filters['vendor_id']);
        });

        if ($filters['expiry_period'] !== '') {
            $today = now()->startOfDay();
            $query->when($filters['expiry_period'] === '7_days', function ($q) use ($today) {
                $q->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)]);
            })->when($filters['expiry_period'] === '30_days', function ($q) use ($today) {
                $q->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)]);
            })->when($filters['expiry_period'] === '90_days', function ($q) use ($today) {
                $q->whereBetween('expiry_date', [$today, $today->copy()->addDays(90)]);
            })->when($filters['expiry_period'] === 'expired', function ($q) use ($today) {
                $q->where('expiry_date', '<', $today)->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived']);
            });
        }

        $obligations = $query->orderByDesc('expiry_date')->paginate(15)->withQueryString();

        return view('obligations.index', [
            'obligations' => $obligations,
            'filters' => $filters,
            'types' => ObligationType::orderBy('type_name')->get(['id', 'type_name']),
            'categories' => ObligationCategory::orderBy('category_name')->get(['id', 'category_name']),
            'companies' => Company::orderBy('company_name')->get(['id', 'company_name']),
            'departments' => Department::orderBy('department_name')->get(['id', 'department_name']),
            'locations' => Location::orderBy('location_name')->get(['id', 'location_name']),
            'vendors' => Vendor::orderBy('vendor_name')->get(['id', 'vendor_name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('obligations.create', [
            'types' => ObligationType::where('active', true)->orderBy('type_name')->get(['id', 'type_name']),
            'categories' => ObligationCategory::where('active', true)->orderBy('category_name')->get(['id', 'category_name']),
            'companies' => Company::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']),
            'departments' => Department::where('status', 'active')->orderBy('department_name')->get(['id', 'department_name']),
            'locations' => Location::where('status', 'active')->orderBy('location_name')->get(['id', 'location_name']),
            'vendors' => Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'obligation_type_id' => ['required', 'exists:obligation_types,id'],
            'category_id' => ['required', 'exists:obligation_categories,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'owner_user_id' => ['required', 'exists:users,id'],
            'backup_user_id' => ['nullable', 'exists:users,id'],
            'reviewer_user_id' => ['nullable', 'exists:users,id'],
            'approver_user_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:start_date'],
            'renewal_required' => ['required', 'boolean'],
            'auto_renew' => ['required', 'boolean'],
            'recurrence_type' => ['nullable', 'string', 'max:50'],
            'recurrence_interval' => ['nullable', 'integer', 'min:1'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'risk_level' => ['required', 'in:low,medium,high,critical'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'status' => ['required', 'in:active,upcoming,action_required,renewal_in_progress,pending_approval,purchase_in_progress,renewed,expired,cancelled,not_required,archived'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['obligation_no'] = 'OBS-'.strtoupper(Str::random(8));
        $validated['created_by'] = Auth::id();

        $obligation = Obligation::create($validated);

        $this->logActivity($obligation, 'CREATED', null, $obligation->toArray(), 'Obligation created');

        return redirect()->route('obligations.show', $obligation)->with('success', 'Obligation created successfully.');
    }

    public function show(Obligation $obligation): View
    {
        $obligation->load([
            'type',
            'category',
            'company',
            'department',
            'location',
            'vendor',
            'owner',
            'backupUser',
            'reviewer',
            'approver',
            'creator',
            'responsibilities.user',
            'renewals',
            'documents.uploader',
            'activityLogs.user',
            'tasks',
            'notificationLogs.rule',
        ]);

        return view('obligations.show', [
            'obligation' => $obligation,
        ]);
    }

    public function edit(Obligation $obligation): View
    {
        return view('obligations.edit', [
            'obligation' => $obligation,
            'types' => ObligationType::where('active', true)->orderBy('type_name')->get(['id', 'type_name']),
            'categories' => ObligationCategory::where('active', true)->orderBy('category_name')->get(['id', 'category_name']),
            'companies' => Company::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']),
            'departments' => Department::where('status', 'active')->orderBy('department_name')->get(['id', 'department_name']),
            'locations' => Location::where('status', 'active')->orderBy('location_name')->get(['id', 'location_name']),
            'vendors' => Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Obligation $obligation): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'obligation_type_id' => ['required', 'exists:obligation_types,id'],
            'category_id' => ['required', 'exists:obligation_categories,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'owner_user_id' => ['required', 'exists:users,id'],
            'backup_user_id' => ['nullable', 'exists:users,id'],
            'reviewer_user_id' => ['nullable', 'exists:users,id'],
            'approver_user_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:start_date'],
            'renewal_required' => ['required', 'boolean'],
            'auto_renew' => ['required', 'boolean'],
            'recurrence_type' => ['nullable', 'string', 'max:50'],
            'recurrence_interval' => ['nullable', 'integer', 'min:1'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'risk_level' => ['required', 'in:low,medium,high,critical'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'status' => ['required', 'in:active,upcoming,action_required,renewal_in_progress,pending_approval,purchase_in_progress,renewed,expired,cancelled,not_required,archived'],
            'notes' => ['nullable', 'string'],
        ]);

        $old = $obligation->toArray();
        $obligation->update($validated);

        $this->logActivity($obligation, 'UPDATED', $old, $obligation->toArray(), 'Obligation updated');

        return redirect()->route('obligations.show', $obligation)->with('success', 'Obligation updated successfully.');
    }

    public function destroy(Obligation $obligation): RedirectResponse
    {
        $obligation->delete();

        return redirect()->route('obligations.index')->with('success', 'Obligation deleted successfully.');
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }

    private function logActivity(Obligation $obligation, string $action, ?array $oldValue, ?array $newValue, ?string $remarks = null): void
    {
        ObligationActivityLog::create([
            'obligation_id' => $obligation->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'remarks' => $remarks,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
