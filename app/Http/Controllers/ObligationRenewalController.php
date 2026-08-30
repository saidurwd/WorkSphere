<?php

namespace App\Http\Controllers;

use App\Models\Obligation;
use App\Models\ObligationActivityLog;
use App\Models\ObligationRenewal;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObligationRenewalController extends Controller
{
    public function create(Obligation $obligation): View
    {
        return view('obligations.renew', [
            'obligation' => $obligation,
            'vendors' => Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Obligation $obligation): RedirectResponse
    {
        $validated = $request->validate([
            'new_start_date' => ['required', 'date', 'after_or_equal:'.$obligation->start_date],
            'new_expiry_date' => ['required', 'date', 'after:new_start_date'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'purchase_reference' => ['nullable', 'string', 'max:255'],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'max:10240'],
        ]);

        $validated['renewal_date'] = now()->toDateString();
        $validated['previous_expiry_date'] = $obligation->expiry_date;
        $validated['renewed_by'] = Auth::id();
        $validated['obligation_id'] = $obligation->id;
        $validated['currency'] = $validated['currency'] ?? $obligation->currency;

        if ($request->hasFile('document')) {
            $validated['document_path'] = $request->file('document')->store('obligation-documents', 'public');
            $validated['document_name'] = $request->file('document')->getClientOriginalName();
        }

        \DB::transaction(function () use ($obligation, $validated, $request) {
            $renewal = ObligationRenewal::create($validated);

            if (isset($validated['document_path'])) {
                $obligation->documents()->create([
                    'document_type' => 'RENEWAL_CERTIFICATE',
                    'file_name' => $validated['document_name'],
                    'file_path' => $validated['document_path'],
                    'mime_type' => $request->file('document')->getClientMimeType(),
                    'file_size' => $request->file('document')->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }

            $obligation->update([
                'start_date' => $validated['new_start_date'],
                'expiry_date' => $validated['new_expiry_date'],
                'status' => 'active',
                'risk_level' => 'low',
                'vendor_id' => $validated['vendor_id'] ?? $obligation->vendor_id,
                'estimated_cost' => $validated['cost'] ?? $obligation->estimated_cost,
            ]);

            ObligationActivityLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => Auth::id(),
                'action' => 'RENEWED',
                'new_value' => json_encode($validated),
                'remarks' => 'Obligation renewed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('obligations.show', $obligation)->with('success', 'Obligation renewed successfully.');
    }
}
