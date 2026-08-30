<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptDetail extends Model
{
    use HasCrud;

    protected $fillable = [
        'goods_receipt_id',
        'asset_id',
        'serial_number',
    ];

    protected $resourceFieldOverrides = [
        'goods_receipt_id' => [
            'type' => 'select',
            'label' => 'Goods Receipt',
            'relationship' => 'goodsReceipt',
            'option_label' => 'grn_number',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'asset_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'serial_number' => [
            'label' => 'Serial Number',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
