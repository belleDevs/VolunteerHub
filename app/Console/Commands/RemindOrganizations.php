<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Task;
use App\Models\TaskApplication;
use App\Models\User;
use App\Support\OrgNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindOrganizations extends Command
{
    protected $signature = 'org:remind';

    protected $description = 'Send each organization one reminder for an upcoming event, a nearing deadline, or an overdue task';

    public function handle(): int
    {
        $now = Carbon::now();
        $soon = $now->copy()->addDay();

        User::where('role', 'organization')->where('status', 'approved')->orderBy('id')->each(function (User $organization) use ($now, $soon) {
            Event::where('organization_id', $organization->id)
                ->where('status', 'published')
                ->whereBetween('start_time', [$now, $soon])
                ->orderBy('start_time')
                ->each(function (Event $event) use ($organization) {
                    OrgNotifier::send(
                        (int) $organization->id,
                        'event',
                        'Upcoming event reminder',
                        '"'.$event->title.'" starts '.$event->start_time->format('M d, Y g:i A').($event->location ? ' at '.$event->location : '').'.',
                        $event->title,
                        route('org.dashboard', ['section' => 'events']),
                        'View event',
                        true,
                        'event-soon-'.$event->id
                    );
                });

            Task::whereHas('event', fn ($query) => $query->where('organization_id', $organization->id))
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')
                ->with('event')
                ->each(function (Task $task) use ($organization, $now, $soon) {
                    $eventTitle = $task->event->title ?? 'Event';
                    if ($task->due_date->between($now, $soon)) {
                        OrgNotifier::send(
                            (int) $organization->id,
                            'task',
                            'Task deadline approaching',
                            'The task "'.$task->title.'" is due '.$task->due_date->format('M d, Y g:i A').'. Please check its assignment and progress.',
                            $eventTitle,
                            route('org.dashboard', ['section' => 'tasks']),
                            'View task',
                            true,
                            'task-due-'.$task->id
                        );
                    }

                    if ($task->due_date->lt($now)) {
                        OrgNotifier::send(
                            (int) $organization->id,
                            'task',
                            'Task overdue',
                            '"'.$task->title.'" for "'.$eventTitle.'" was due '.$task->due_date->format('M d, Y g:i A').' and is still incomplete.',
                            $eventTitle,
                            route('org.dashboard', ['section' => 'tasks']),
                            'View task',
                            true,
                            'task-overdue-'.$task->id
                        );
                    }
                });

            $pending = TaskApplication::whereHas('event', fn ($query) => $query->where('organization_id', $organization->id))
                ->where('status', 'pending')
                ->count();
            if ($pending > 0) {
                OrgNotifier::send(
                    (int) $organization->id,
                    'system',
                    'Pending volunteer applications',
                    $pending.' volunteer application'.($pending === 1 ? ' is' : 's are').' still waiting for review.',
                    $organization->name,
                    route('org.dashboard', ['section' => 'tasks']),
                    'Review applications',
                    false,
                    'pending-applications-'.$organization->id.'-'.$now->toDateString()
                );
            }
        });

        return self::SUCCESS;
    }
}
