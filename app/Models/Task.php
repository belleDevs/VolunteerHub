<?php

namespace App\Models;

use App\Support\RoleCatalog;
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

    /**
     * Skills this volunteer already has that fit this task.
     */
    public function matchedSkillsFor(User $volunteer)
    {
        $volunteerSkills = $volunteer->relationLoaded('skills')
            ? $volunteer->skills
            : $volunteer->skills()->get();

        $relatedNames = collect(RoleCatalog::relatedSkillNames((string) $this->title))
            ->map(fn (string $name) => mb_strtolower($name));

        $required = $this->relationLoaded('skills')
            ? $this->skills
            : $this->skills()->get();
        $requiredIds = $required->pluck('id');

        return $volunteerSkills
            ->filter(function (Skill $skill) use ($relatedNames, $requiredIds) {
                return $requiredIds->contains($skill->id)
                    || $relatedNames->contains(mb_strtolower($skill->name));
            })
            ->unique('id')
            ->values();
    }

    public function applications()
    {
        return $this->hasMany(TaskApplication::class);
    }

    /**
     * Admin oversight status from the task status recorded in VolunteerHub.
     */
    public function oversightStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->status === 'completed') {
            return 'completed';
        }

        if ($this->status === 'in_progress') {
            return 'in_progress';
        }

        if ($this->status === 'assigned') {
            return 'assigned';
        }

        return 'pending';
    }

    public function oversightLabel(): string
    {
        return match ($this->oversightStatus()) {
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Pending',
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->oversightStatus(), ['completed', 'cancelled'], true);
    }

    public function assignedVolunteerNames(): string
    {
        if (!$this->relationLoaded('assignments') || $this->assignments->isEmpty()) {
            return 'Unassigned';
        }

        return $this->assignments
            ->map(fn ($assignment) => $assignment->user->name ?? 'Former volunteer')
            ->unique()
            ->join(', ');
    }
}
