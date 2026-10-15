<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\ChatbotRule;
use App\Models\Event;
use App\Models\TaskApplication;
use App\Models\User;
use Carbon\Carbon;

class VolunteerAssistant
{
    public const HANDOFF = 'I don\'t have that information. Your organization will reply to this question.';

    /**
     * Answer only from saved rules and this volunteer's records.
     * A null answer means the organization has to take over.
     *
     * @return array{response: string, intent: string, confidence: float}|null
     */
    public function answer(User $volunteer, string $message, ?User $organization): ?array
    {
        $text = mb_strtolower(trim($message));
        if ($text === '') {
            return null;
        }

        if ($this->isGreeting($text)) {
            $orgName = $organization->name ?? 'your organization';

            return [
                'response' => 'I can answer from your skills, assignments, certificates, and the details '.$orgName.' has saved. If I don\'t have an answer, '.$orgName.' will reply.',
                'intent' => 'greeting',
                'confidence' => 0.7,
            ];
        }

        $personal = $this->fromRecords($volunteer, $text, $organization);
        if ($personal !== null) {
            return $personal;
        }

        foreach (ChatbotRule::query()->orderBy('keyword')->get() as $rule) {
            $keyword = mb_strtolower(trim($rule->keyword));
            if ($keyword !== '' && str_contains($text, $keyword)) {
                return [
                    'response' => $rule->response,
                    'intent' => $keyword,
                    'confidence' => 0.9,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{response: string, intent: string, confidence: float}|null
     */
    private function fromRecords(User $volunteer, string $text, ?User $organization): ?array
    {
        $eventIds = $organization
            ? Event::where('organization_id', $organization->id)->pluck('id')
            : null;

        if (str_contains($text, 'accept') || str_contains($text, 'decline')) {
            return [
                'response' => 'Open Assigned Tasks. Accept or Decline is available only while the event has not ended and the assignment is still waiting for you. A past task you never answered is marked Uncompleted. After you accept, submit your work for review. The organization confirms completion.',
                'intent' => 'respond',
                'confidence' => 0.9,
            ];
        }

        if (str_contains($text, 'availab')) {
            $status = match ($volunteer->availability) {
                'inactive' => 'Unavailable',
                'limited' => 'Busy / Limited Availability',
                default => 'Available',
            };

            return [
                'response' => 'Your saved availability is '.$status.'. Update it on the Availability page. That change does not cancel tasks you already accepted.',
                'intent' => 'availability',
                'confidence' => 0.88,
            ];
        }

        if (str_contains($text, 'profile') || str_contains($text, 'password') || str_contains($text, 'setting')) {
            return [
                'response' => 'Update your name, photo, contact number, biography, interests, and experience on Profile. Change your password, email, and notification choices on Settings. Your role cannot be changed from these pages.',
                'intent' => 'account',
                'confidence' => 0.84,
            ];
        }

        if (str_contains($text, 'application') || str_contains($text, 'applied')) {
            $applications = TaskApplication::where('user_id', $volunteer->id)
                ->when($eventIds !== null, fn ($query) => $query->whereIn('event_id', $eventIds->isEmpty() ? [-1] : $eventIds))
                ->with(['task', 'event'])
                ->latest()
                ->get();

            if ($applications->isEmpty()) {
                return [
                    'response' => 'You have no task applications on record.',
                    'intent' => 'applications',
                    'confidence' => 0.86,
                ];
            }

            $lines = $applications->map(function (TaskApplication $application) {
                $task = $application->task->title ?? 'Task';
                $event = $application->event->title ?? 'Event';

                return $task.' for '.$event.' ('.str_replace('_', ' ', $application->status).')';
            })->join('; ');

            return [
                'response' => 'Your applications: '.$lines.'.',
                'intent' => 'applications',
                'confidence' => 0.86,
            ];
        }

        if (str_contains($text, 'skill')) {
            $skills = $volunteer->skills()->orderBy('name')->pluck('name');
            $list = $skills->isEmpty() ? 'none yet' : $skills->join(', ');

            return [
                'response' => 'Your saved skills are: '.$list.'.',
                'intent' => 'skills',
                'confidence' => 0.86,
            ];
        }

        if (str_contains($text, 'task') || str_contains($text, 'assignment') || str_contains($text, 'duty') || str_contains($text, 'role')) {
            $assignments = $this->assignments($volunteer, $eventIds);
            if ($assignments->isEmpty()) {
                return [
                    'response' => 'You have no assignments on record'.($organization ? ' with '.$organization->name : '').'.',
                    'intent' => 'tasks',
                    'confidence' => 0.86,
                ];
            }

            $lines = $assignments->map(function (Assignment $assignment) {
                $task = $assignment->task->title ?? 'Role';
                $event = $assignment->event->title ?? 'Event';
                $due = $assignment->task?->due_date?->format('M j, Y');

                return $task.' for '.$event.' ('.str_replace('_', ' ', $assignment->status).($due ? ', due '.$due : '').')';
            })->join('; ');

            return [
                'response' => 'Your assignments: '.$lines.'.',
                'intent' => 'tasks',
                'confidence' => 0.86,
            ];
        }

        if (str_contains($text, 'certific') || str_contains($text, 'award')) {
            $certificates = Certificate::where('user_id', $volunteer->id)
                ->when($organization, fn ($query) => $query->whereIn('event_id', $eventIds))
                ->with('event')
                ->orderByDesc('issued_at')
                ->get();

            if ($certificates->isEmpty()) {
                return [
                    'response' => 'You have no certificates on record yet. A certificate is issued after the organization approves completed work.',
                    'intent' => 'certificates',
                    'confidence' => 0.86,
                ];
            }

            $lines = $certificates->map(function (Certificate $certificate) {
                $when = $certificate->issued_at?->format('M j, Y');

                return ($certificate->event->title ?? 'Event').($when ? ' on '.$when : '').($certificate->certificate_code ? ' ('.$certificate->certificate_code.')' : '');
            })->join('; ');

            return [
                'response' => 'Your certificates: '.$lines.'.',
                'intent' => 'certificates',
                'confidence' => 0.86,
            ];
        }

        if (str_contains($text, 'hour')) {
            $hours = $this->assignments($volunteer, $eventIds)->sum('hours_logged');

            return [
                'response' => 'Your logged hours'.($organization ? ' with '.$organization->name : '').' are '.$hours.'.',
                'intent' => 'hours',
                'confidence' => 0.86,
            ];
        }

        $asksAboutEvents = str_contains($text, 'schedule')
            || str_contains($text, 'upcoming')
            || preg_match('/\b(what|which|when|where|list).{0,40}\bevents?\b/u', $text)
            || preg_match('/\bmy events?\b/u', $text);

        if ($asksAboutEvents) {
            $events = $this->assignments($volunteer, $eventIds)
                ->map(fn (Assignment $assignment) => $assignment->event)
                ->filter()
                ->unique('id')
                ->sortBy(fn (Event $event) => $event->start_time?->timestamp ?? 0)
                ->values();

            if ($events->isEmpty()) {
                return [
                    'response' => 'You are not assigned to an event'.($organization ? ' with '.$organization->name : '').' yet.',
                    'intent' => 'events',
                    'confidence' => 0.86,
                ];
            }

            $lines = $events->map(function (Event $event) {
                $when = $event->start_time?->format('M j, Y g:i A');

                return $event->title.($when ? ' on '.$when : '').($event->location ? ' at '.$event->location : '');
            })->join('; ');

            return [
                'response' => 'Your events: '.$lines.'.',
                'intent' => 'events',
                'confidence' => 0.86,
            ];
        }

        $orgName = mb_strtolower($organization->name ?? '');
        if ($organization && (str_contains($text, 'organization') || ($orgName !== '' && str_contains($text, $orgName)))) {
            $bio = trim((string) $organization->bio);

            return [
                'response' => $bio !== ''
                    ? $organization->name.': '.$bio
                    : 'Your organization is '.$organization->name.'. No further description is saved.',
                'intent' => 'organization',
                'confidence' => 0.8,
            ];
        }

        return null;
    }

    private function assignments(User $volunteer, $eventIds)
    {
        return Assignment::where('user_id', $volunteer->id)
            ->when($eventIds !== null, function ($query) use ($eventIds) {
                $query->whereIn('event_id', $eventIds->isEmpty() ? [-1] : $eventIds);
            })
            ->with(['task', 'event'])
            ->get();
    }

    private function isGreeting(string $text): bool
    {
        return in_array($text, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'thanks', 'thank you'], true);
    }
}
