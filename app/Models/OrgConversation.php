<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgConversation extends Model
{
    protected $fillable = [
        'organization_id',
        'volunteer_id',
        'mode',
        'needs_takeover',
        'taken_over_at',
        'last_message_at',
    ];

    protected $casts = [
        'needs_takeover' => 'boolean',
        'taken_over_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    public function withAgent(): bool
    {
        return $this->mode === 'agent';
    }
}
