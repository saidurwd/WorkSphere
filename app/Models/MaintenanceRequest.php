<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    use HasCrud;

    protected $fillable = [
        'ticket_no',
        'asset_id',
        'reported_by',
        'issue_description',
        'priority',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (MaintenanceRequest $request) {
            if (filled($request->ticket_no)) {
                return;
            }

            $prefix = 'MR-';

            $lastTicket = static::where('ticket_no', 'like', $prefix.'%')
                ->orderByDesc('ticket_no')
                ->first()?->ticket_no;

            $seq = 1;
            if ($lastTicket !== null) {
                $numPart = substr($lastTicket, strlen($prefix));
                if (ctype_digit((string) $numPart)) {
                    $seq = ((int) $numPart) + 1;
                } else {
                    $seq = static::where('ticket_no', 'like', $prefix.'%')->count() + 1;
                }
            }

            $request->ticket_no = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }

    protected $resourceFieldOverrides = [
        'ticket_no' => [
            'label' => 'Ticket No',
            'rules' => 'nullable|string|max:255|unique:maintenance_requests,ticket_no',
            'placeholder' => 'e.g. MR-000001',
            'searchable' => true,
            'sortable' => true,
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'name_with_serial',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'reported_by' => [
            'label' => 'Reported By',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'issue_description' => [
            'type' => 'textarea',
            'label' => 'Issue Description',
            'rules' => 'required|string',
            'hide_in_index' => true,
        ],
        'priority' => [
            'type' => 'select',
            'label' => 'Priority',
            'options' => [
                'Low' => 'Low',
                'Medium' => 'Medium',
                'High' => 'High',
                'Critical' => 'Critical',
            ],
            'rules' => 'required|in:Low,Medium,High,Critical',
            'searchable' => true,
            'sortable' => true,
        ],
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
            ],
            'rules' => 'required|string|in:open,in_progress,resolved,closed',
            'searchable' => true,
            'sortable' => true,
        ],
        'history' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'string',
            'status' => 'string',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    public function getRequestLabelAttribute(): string
    {
        $asset = $this->asset?->name_with_serial;
        $ticket = $this->ticket_no ?? '';

        return $asset ? $asset.' - '.$ticket : $ticket;
    }
}
