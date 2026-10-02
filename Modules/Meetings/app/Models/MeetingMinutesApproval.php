<?php

namespace Modules\Meetings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinutesApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'step_no',
        'approver_id',
        'status',
        'comments',
        'action_at',
    ];

    protected function casts(): array
    {
        return [
            'step_no' => 'integer',
            'action_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
