<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerReward extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'event_id',
        'task_id',
        'assignment_id',
        'reward_scope',
        'reward_type',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function label(): ?string
    {
        $options = $this->reward_scope === 'event'
            ? User::EVENT_REWARDS
            : User::TASK_REWARDS;

        return $options[$this->reward_type] ?? null;
    }
}
