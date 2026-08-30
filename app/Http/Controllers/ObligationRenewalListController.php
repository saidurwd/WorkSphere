<?php

namespace App\Http\Controllers;

use App\Models\ObligationRenewal;
use Illuminate\View\View;

class ObligationRenewalListController extends Controller
{
    public function index(): View
    {
        $query = ObligationRenewal::query()
            ->with(['obligation', 'obligation.type', 'obligation.department', 'renewedBy']);

        $filters = [
            'search' => request()->string('search')->trim()->toString(),
            'obligation_id' => request()->integer('obligation_id', 0),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->whereHas('obligation', function ($q2) use ($search) {
                $q2->where('obligation_no', 'like', $search)
                    ->orWhere('title', 'like', $search);
            });
        })->when($filters['obligation_id'] > 0, function ($q) use ($filters) {
            $q->where('obligation_id', $filters['obligation_id']);
        });

        $renewals = $query->orderByDesc('renewal_date')->paginate(15)->withQueryString();

        return view('obligations.renewals', [
            'renewals' => $renewals,
            'filters' => $filters,
        ]);
    }
}
