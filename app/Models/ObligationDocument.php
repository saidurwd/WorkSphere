<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationDocument extends Model
{
    use HasCrud;

    protected $fillable = [
        'obligation_id',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'document_date',
        'expiry_date',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expiry_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
