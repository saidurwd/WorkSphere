<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    use HasCrud;

    protected $fillable = [
        'asset_id',
        'file_name',
        'document_type',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => 'string',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssetDocument $document) {
            if (! $document->uploaded_at) {
                $document->uploaded_at = now();
            }

            if (! $document->uploaded_by && auth()->check()) {
                $document->uploaded_by = auth()->id();
            }

            if (! $document->document_type) {
                $document->document_type = 'Invoice';
            }
        });
    }

    public $resourceFieldOverrides = [
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'name_with_serial',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'document_type' => [
            'type' => 'select',
            'label' => 'Document Type',
            'options' => [
                'Invoice' => 'Invoice',
                'Warranty' => 'Warranty',
                'Manual' => 'Manual',
                'AMC' => 'AMC',
                'Insurance' => 'Insurance',
                'Photo' => 'Photo',
            ],
            'rules' => 'nullable',
            'searchable' => true,
            'sortable' => true,
            'help_text' => 'Automatically detected on upload based on document content or source.',
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
        'file_name' => [
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
            'hide_in_index' => false,
        ],
        'file_path' => [
            'type' => 'file',
            'label' => 'Document File',
            'rules' => 'nullable|file',
            'hide_in_index' => true,
        ],
        'uploaded_by' => [
            'type' => 'select',
            'label' => 'Uploaded By',
            'relationship' => 'uploader',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => true,
            'sortable' => true,
            'help_text' => 'Defaults to the current logged-in user.',
        ],
        'uploaded_at' => [
            'type' => 'datetime-local',
            'label' => 'Uploaded At',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('tyro-dashboard.user_model', 'App\\Models\\User'), 'uploaded_by');
    }
}
