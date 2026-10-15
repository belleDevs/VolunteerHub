<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_role',
        'action',
        'action_type',
        'module',
        'description',
        'status',
        'subject_type',
        'subject_id',
        'subject_label',
        'previous_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'previous_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
