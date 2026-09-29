<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'task_skills');
    }

    public function applications()
    {
        return $this->hasMany(TaskApplication::class);
    }
}
