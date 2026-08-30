<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\View\View;

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
}
