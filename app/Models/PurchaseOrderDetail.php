<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderDetail extends Model
{
    use HasCrud;

    protected $fillable = [
        'purchase_order_id',
        'category_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $resourceFieldOverrides = [
        'purchase_order_id' => [
            'type' => 'select',
            'label' => 'Purchase Order',
            'relationship' => 'purchaseOrder',
            'option_label' => 'po_number',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'category_id' => [
            'type' => 'select',
            'label' => 'Category',
            'relationship' => 'category',
            'option_label' => 'category_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'description' => [
            'type' => 'textarea',
            'label' => 'Description',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
        'quantity' => [
            'label' => 'Quantity',
            'rules' => 'required|integer|min:1',
            'searchable' => false,
            'sortable' => true,
        ],
        'unit_price' => [
            'label' => 'Unit Price',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'total_price' => [
            'label' => 'Total Price',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }
}
