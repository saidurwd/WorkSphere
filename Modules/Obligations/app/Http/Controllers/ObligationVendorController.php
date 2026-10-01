<?php

namespace Modules\Obligations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Obligations\Models\Vendor;

class ObligationVendorController extends Controller
{
    public function index(): View
    {
        $query = Vendor::query();

        $filters = [
            'search' => request()->string('search')->trim()->toString(),
            'status' => request()->string('status')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where('vendor_name', 'like', $search)
                ->orWhere('contact_person', 'like', $search)
                ->orWhere('email', 'like', $search);
        })->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        });

        $vendors = $query->orderBy('vendor_name')->paginate(15)->withQueryString();

        return view('obligations.vendors', [
            'vendors' => $vendors,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('obligation.manage_vendors');

        return view('obligations.vendors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('obligation.manage_vendors');

        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        Vendor::create($validated);

        return redirect()->route('obligations.vendors')->with('status', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('obligation.manage_vendors');

        return view('obligations.vendors.edit', [
            'vendor' => $vendor,
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('obligation.manage_vendors');

        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $vendor->update($validated);

        return redirect()->route('obligations.vendors')->with('status', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('obligation.manage_vendors');

        $vendor->delete();

        return redirect()->route('obligations.vendors')->with('status', 'Vendor deleted successfully.');
    }
}
