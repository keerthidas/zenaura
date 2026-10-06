<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'new',
        'contacted',
        'qualified',
        'quotation',
        'negotiation',
        'won',
        'lost',
    ];

    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'address',
        'emirate',
        'source',
        'interested_in',
        'estimated_value',
        'expected_close_date',
        'status',
        'lost_reason',
        'notes',
        'client_id',
        'converted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'expected_close_date' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('occurred_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(LeadActivity::class)
            ->whereNotNull('reminder_at')
            ->whereNull('completed_at');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isConverted(): bool
    {
        return $this->client_id !== null;
    }
}
