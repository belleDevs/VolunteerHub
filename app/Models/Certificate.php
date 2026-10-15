<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'certificate_code',
        'issued_at',
        'hours_rendered',
        'file_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'hours_rendered' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Task titles this volunteer finished on the certified event.
     */
    public function completedTaskTitles(): array
    {
        return Assignment::where('user_id', $this->user_id)
            ->where('event_id', $this->event_id)
            ->where('status', 'completed')
            ->with('task')
            ->get()
            ->map(fn (Assignment $assignment) => $assignment->task->title ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
