<?php

namespace App\Http\Controllers;

use App\Models\Obligation;
use App\Models\ObligationActivityLog;
use App\Models\ObligationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObligationDocumentController extends Controller
{
    public function store(Request $request, Obligation $obligation): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
            'document_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $file = $request->file('file');

        $document = $obligation->documents()->create([
            'document_type' => $validated['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('obligation-documents', 'public'),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'document_date' => $validated['document_date'],
            'expiry_date' => $validated['expiry_date'],
            'uploaded_by' => Auth::id(),
        ]);

        ObligationActivityLog::create([
            'obligation_id' => $obligation->id,
            'user_id' => Auth::id(),
            'action' => 'DOCUMENT_UPLOADED',
            'new_value' => json_encode($document->toArray()),
            'remarks' => 'Document uploaded: '.$validated['document_type'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroy(Obligation $obligation, ObligationDocument $document): RedirectResponse
    {
        if ($document->obligation_id !== $obligation->id) {
            abort(404);
        }

        $document->delete();

        ObligationActivityLog::create([
            'obligation_id' => $obligation->id,
            'user_id' => Auth::id(),
            'action' => 'DOCUMENT_DELETED',
            'old_value' => json_encode($document->toArray()),
            'remarks' => 'Document deleted: '.$document->file_name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Document deleted successfully.');
    }
}
