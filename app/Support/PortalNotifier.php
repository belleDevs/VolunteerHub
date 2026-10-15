<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalNotifier
{
    /**
     * Store one portal notification for a user.
     * The bell shows the short summary. The Notifications page shows the full message.
     */
    public static function send(int $userId, string $title, string $summary, string $message, string $icon = 'fa-bell', ?string $url = null, array $extra = []): void
    {
        if ($userId <= 0) {
            return;
        }

        $now = Carbon::now();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => json_encode(array_merge([
                'title' => $title,
                'summary' => $summary,
                'message' => $message,
                'icon' => $icon,
                'url' => $url,
            ], $extra)),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Send the same notice to every administrator.
     */
    public static function admins(string $title, string $summary, string $message, string $icon = 'fa-bell', ?string $url = null): void
    {
        User::where('role', 'admin')->pluck('id')->each(function ($adminId) use ($title, $summary, $message, $icon, $url) {
            self::send((int) $adminId, $title, $summary, $message, $icon, $url);
        });
    }

    /**
     * Send one overdue notice after an administrator confirms it.
     */
    public static function notifyOrganizationAboutOverdue(Task $task): bool
    {
        $task->loadMissing([
            'event.organization:id,name',
            'assignments.user:id,name',
        ]);

        if (!$task->isOverdue()) {
            return false;
        }

        $organizationId = (int) optional($task->event)->organization_id;
        if ($organizationId <= 0 || !$task->due_date) {
            return false;
        }

        $eventTitle = $task->event->title ?? 'an event';
        $dueLabel = $task->due_date->format('M d, Y g:i A');
        $volunteer = $task->assignedVolunteerNames();
        $hasVolunteer = $volunteer !== 'Unassigned';
        $summary = $hasVolunteer
            ? '"' . $task->title . '" is overdue. Please reassign it.'
            : '"' . $task->title . '" is overdue and still has no volunteer.';
        $message = $hasVolunteer
            ? '"' . $task->title . '" for "' . $eventTitle . '" was due ' . $dueLabel . '. ' . $volunteer . ' still has this task. Please reassign it to a volunteer who can finish it.'
            : '"' . $task->title . '" for "' . $eventTitle . '" was due ' . $dueLabel . ' and no volunteer is assigned. Please assign a volunteer who can finish it.';

        self::send(
            $organizationId,
            'Task overdue',
            $summary,
            $message,
            'fa-clock',
            route('org.dashboard', ['task_id' => $task->id]),
            ['kind' => 'task_overdue', 'task_id' => $task->id]
        );

        return true;
    }
}
