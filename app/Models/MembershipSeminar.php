<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipSeminar extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'location',
        'starts_at',
        'ends_at',
        'fee_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'fee_amount' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(User::class, 'membership_seminar_id');
    }
}
