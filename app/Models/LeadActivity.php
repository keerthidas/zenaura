<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public const TYPES = ['call', 'meeting', 'email', 'visit', 'note', 'other'];

    protected $fillable = [
        'lead_id',
        'created_by',
        'type',
        'subject',
        'notes',
        'occurred_at',
        'reminder_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'reminder_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->reminder_at !== null
            && $this->completed_at === null
            && $this->reminder_at->isPast();
    }
}
