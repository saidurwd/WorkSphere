<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasCrud;

    protected $fillable = [
        'grn_number',
        'vendor_id',
        'purchase_order_id',
        'received_date',
        'received_by',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(function (GoodsReceipt $receipt) {
            if (filled($receipt->grn_number)) {
                return;
            }

            $prefix = 'GRN-';

            $lastGrn = static::where('grn_number', 'like', $prefix.'%')
                ->orderByDesc('grn_number')
                ->first()?->grn_number;

            $seq = 1;
            if ($lastGrn !== null) {
                $numPart = substr($lastGrn, strlen($prefix));
                if (ctype_digit((string) $numPart)) {
                    $seq = ((int) $numPart) + 1;
                } else {
                    $seq = static::where('grn_number', 'like', $prefix.'%')->count() + 1;
                }
            }

            $receipt->grn_number = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }

    protected $resourceFieldOverrides = [
        'grn_number' => [
            'label' => 'GRN Number',
            'rules' => 'nullable|string|max:255|unique:goods_receipts,grn_number',
            'placeholder' => 'e.g. GRN-000001',
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
        'purchase_order_id' => [
            'type' => 'select',
            'label' => 'Purchase Order',
            'relationship' => 'purchaseOrder',
            'option_label' => 'po_number',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'received_date' => [
            'label' => 'Received Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'received_by' => [
            'type' => 'select',
            'label' => 'Received By',
            'relationship' => 'receivedBy',
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
            'received_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }
}
