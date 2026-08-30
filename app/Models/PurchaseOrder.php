<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasCrud;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'po_date',
        'expected_delivery_date',
        'total_amount',
        'approval_status',
        'created_by',
        'approved_by',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order) {
            if (filled($order->po_number)) {
                return;
            }

            $prefix = 'PO-';

            $lastPo = static::where('po_number', 'like', $prefix.'%')
                ->orderByDesc('po_number')
                ->first()?->po_number;

            $seq = 1;
            if ($lastPo !== null) {
                $numPart = substr($lastPo, strlen($prefix));
                if (ctype_digit((string) $numPart)) {
                    $seq = ((int) $numPart) + 1;
                } else {
                    $seq = static::where('po_number', 'like', $prefix.'%')->count() + 1;
                }
            }

            $order->po_number = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }

    protected $resourceFieldOverrides = [
        'po_number' => [
            'label' => 'PO Number',
            'rules' => 'nullable|string|max:255|unique:purchase_orders,po_number',
            'placeholder' => 'e.g. PO-000001',
            'searchable' => true,
            'sortable' => true,
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
        'vendor_id' => [
            'type' => 'select',
            'label' => 'Vendor',
            'relationship' => 'vendor',
            'option_label' => 'vendor_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'po_date' => [
            'label' => 'PO Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'expected_delivery_date' => [
            'label' => 'Expected Delivery Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'total_amount' => [
            'label' => 'Total Amount',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'approval_status' => [
            'type' => 'select',
            'label' => 'Approval Status',
            'options' => [
                'Pending' => 'Pending',
                'Approved' => 'Approved',
                'Rejected' => 'Rejected',
            ],
            'rules' => 'required|string|in:Pending,Approved,Rejected',
            'searchable' => true,
            'sortable' => true,
        ],
        'created_by' => [
            'type' => 'select',
            'label' => 'Created By',
            'relationship' => 'createdBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
        ],
        'approved_by' => [
            'type' => 'select',
            'label' => 'Approved By',
            'relationship' => 'approvedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
        ],
        'remarks' => [
            'type' => 'textarea',
            'label' => 'Remarks',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
        'details' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'expected_delivery_date' => 'date',
            'total_amount' => 'decimal:2',
            'approval_status' => 'string',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }
}
