<?php

namespace App\Http\Controllers;

use App\Models\ObligationDocument;
use Illuminate\View\View;

class ObligationDocumentListController extends Controller
{
    public function index(): View
    {
        $query = ObligationDocument::query()
            ->with(['obligation', 'obligation.type', 'obligation.department', 'uploader']);

        $filters = [
            'search' => request()->string('search')->trim()->toString(),
            'document_type' => request()->string('document_type')->toString(),
            'obligation_id' => request()->integer('obligation_id', 0),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where('file_name', 'like', $search)
                ->orWhereHas('obligation', function ($q2) use ($search) {
                    $q2->where('obligation_no', 'like', $search)
                        ->orWhere('title', 'like', $search);
                });
        })->when($filters['document_type'] !== '', function ($q) use ($filters) {
            $q->where('document_type', $filters['document_type']);
        })->when($filters['obligation_id'] > 0, function ($q) use ($filters) {
            $q->where('obligation_id', $filters['obligation_id']);
        });

        $documents = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $documentTypes = ObligationDocument::select('document_type')->distinct()->pluck('document_type');

        return view('obligations.documents', [
            'documents' => $documents,
            'filters' => $filters,
            'documentTypes' => $documentTypes,
        ]);
    }
}
