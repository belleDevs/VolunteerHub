<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationNotice extends Model
{
    protected $fillable = [
        'organization_id',
        'notice_type',
        'title',
        'message',
        'recipients',
        'emails_sent',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function typeLabel(): string
    {
        return match ($this->notice_type) {
            'event' => 'Event',
            'task' => 'Task',
            'member' => 'Member',
            'seminar' => 'Seminar',
            default => 'Reminder',
        };
    }
}
