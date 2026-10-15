<?php

namespace App\Support;

use App\Mail\VolunteerNoticeMail;
use App\Models\Assignment;
use App\Models\Event;
use App\Models\MembershipSeminar;
use App\Models\OrganizationNotice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VolunteerNotice
{
    /**
     * Send one notice to the volunteers it applies to, on the portal and by email.
     *
     * @return array{recipients: int, emails: int}
     */
    public static function broadcast(User $organization, string $type, string $title, string $message): array
    {
        $recipients = 0;
        $emails = 0;
        $addresses = [];
        $failures = [];

        foreach (self::recipients($organization, $type) as $volunteer) {
            $events = self::upcomingEvents($organization);
            $assignments = self::openAssignments($organization, $volunteer);
            if ($type === 'task' && $assignments->isEmpty()) {
                continue;
            }

            $recipients++;
            $address = trim((string) $volunteer->email);
            $error = null;
            $delivered = self::deliver($volunteer, $organization, $type, $title, $message, $events, $assignments, $error);
            if ($delivered) {
                $emails++;
                $addresses[] = $address;
            } elseif ($address !== '') {
                $failures[$address] = $error;
            }
        }

        if ($recipients > 0) {
            self::record($organization, $type, $title, $message, $recipients, $emails);
        }

        return ['recipients' => $recipients, 'emails' => $emails, 'addresses' => $addresses, 'failures' => $failures];
    }

    /**
     * Tell the assigned volunteer about the task, and include their open events.
     */
    public static function notifyAssignment(User $organization, User $volunteer, Task $task, ?User $previous = null): void
    {
        $task->loadMissing('event');
        $reassigned = $previous && (int) $previous->id !== (int) $volunteer->id;
        $title = $reassigned ? 'Task reassigned' : 'New task assigned';
        $intro = $reassigned
            ? 'This task was reassigned to you. Accept it to take the duty, or decline it.'
            : 'You have been assigned to this task. Accept it to take the duty, or decline it.';
        $message = $intro."\n\n".self::assignmentFacts($task, $reassigned ? $previous : null, $volunteer);
        $events = self::upcomingEvents($organization);
        $assignments = self::openAssignments($organization, $volunteer);
        $emailed = self::deliver($volunteer, $organization, 'task', $title, $message, $events, $assignments) ? 1 : 0;
        $recipients = 1;

        if ($reassigned) {
            $previousMessage = 'This task was reassigned to '.$volunteer->name.".\n\n".self::assignmentFacts($task, $previous, $volunteer);
            $recipients++;
            if (self::deliver($previous, $organization, 'task', 'Task reassigned', $previousMessage, $events, collect())) {
                $emailed++;
            }
        }

        self::record($organization, 'task', $title, $message, $recipients, $emailed);
    }

    /**
     * Tell someone who just registered what the membership seminar requires.
     */
    public static function notifyApplicant(User $organization, User $volunteer): void
    {
        $seminar = self::nextSeminar($organization);
        $title = 'Membership seminar';
        $message = $volunteer->name.' registered with '.$organization->name.'. '
            .'A person becomes a member only after the membership fee is paid and they attend a membership seminar.';
        if ($seminar) {
            $message .= "\n\n".self::seminarFacts($seminar);
        } else {
            $message .= "\n\nThe organization has not scheduled the next membership seminar yet.";
        }

        $emailed = self::deliver($volunteer, $organization, 'seminar', $title, $message, collect(), collect());
        self::record($organization, 'seminar', $title, $message, 1, $emailed ? 1 : 0);
    }

    /**
     * Tell applicants the date, place, and fee of a newly scheduled seminar.
     */
    public static function notifyScheduledSeminar(User $organization, MembershipSeminar $seminar): void
    {
        $title = 'Membership seminar scheduled';
        $message = $organization->name.' scheduled a membership seminar. Attend this seminar and pay the membership fee to become a member.'
            ."\n\n".self::seminarFacts($seminar);
        $recipients = 0;
        $emails = 0;

        foreach (self::applicants($organization) as $volunteer) {
            $recipients++;
            if (self::deliver($volunteer, $organization, 'seminar', $title, $message, collect(), collect())) {
                $emails++;
            }
        }

        if ($recipients > 0) {
            self::record($organization, 'seminar', $title, $message, $recipients, $emails);
        }
    }

    /**
     * Tell current members that someone finished the fee and the seminar.
     */
    public static function notifyNewMember(User $organization, User $member): void
    {
        $member->loadMissing('membershipSeminar');
        $seminar = $member->membershipSeminar;
        $title = 'New member';
        $message = $member->name.' is now a member of '.$organization->name.'. '
            .'The membership fee was recorded'
            .($member->membership_fee_paid_at ? ' on '.$member->membership_fee_paid_at->format('M d, Y') : '')
            .' and seminar attendance was recorded'
            .($member->membership_attended_at ? ' on '.$member->membership_attended_at->format('M d, Y') : '')
            .'.';
        if ($seminar) {
            $message .= "\n\n".self::seminarFacts($seminar);
        }

        $recipients = 0;
        $emails = 0;
        foreach (self::members($organization) as $volunteer) {
            if ((int) $volunteer->id === (int) $member->id) {
                continue;
            }
            $recipients++;
            if (self::deliver($volunteer, $organization, 'member', $title, $message, collect(), collect())) {
                $emails++;
            }
        }

        $ownMessage = 'You are now a member of '.$organization->name.' because the membership fee and seminar attendance are both recorded.';
        if ($seminar) {
            $ownMessage .= "\n\n".self::seminarFacts($seminar);
        }
        $recipients++;
        if (self::deliver($member, $organization, 'member', 'You are now a member', $ownMessage, collect(), collect())) {
            $emails++;
        }

        self::record($organization, 'member', $title, $message, $recipients, $emails);
    }

    /**
     * Tell the organization's volunteers that an event is now visible.
     */
    public static function notifyPublishedEvent(Event $event): void
    {
        $event->loadMissing('organization');
        $organization = $event->organization;
        if (!$organization) {
            return;
        }

        $when = $event->start_time ? $event->start_time->format('M d, Y g:i A') : 'a scheduled date';
        self::broadcast(
            $organization,
            'event',
            'New event: '.$event->title,
            $event->title.' is now open'.($event->location ? ' at '.$event->location : '').' on '.$when.'.'
        );
    }

    public static function linkedVolunteers(User $organization): Collection
    {
        return self::recipients($organization, 'reminder');
    }

    public static function mailIsReady(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && config('mail.mailers.smtp.host') !== '127.0.0.1';
    }

    /**
     * Save a Gmail mailbox and use it for the next send in this request.
     */
    public static function connectGmail(string $address, string $password): void
    {
        $address = trim($address);
        $password = preg_replace('/\s+/', '', trim($password)) ?? '';

        self::writeEnv([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => $address,
            'MAIL_PASSWORD' => $password,
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => $address,
            'MAIL_FROM_NAME' => 'VolunteerHub',
        ]);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => $address,
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $address,
            'mail.from.name' => 'VolunteerHub',
        ]);

        Mail::purge('smtp');
    }

    private static function deliver(
        User $volunteer,
        User $organization,
        string $type,
        string $title,
        string $message,
        Collection $events,
        Collection $assignments,
        ?string &$error = null
    ): bool {
        $error = null;
        if (!$volunteer->wantsVolunteerNotice($type)) {
            return false;
        }

        PortalNotifier::send(
            (int) $volunteer->id,
            $title,
            Str::limit(preg_replace('/\s+/', ' ', $message) ?? $message, 160),
            $message,
            match ($type) {
                'event' => 'fa-calendar',
                'task' => 'fa-list-check',
                default => 'fa-bell',
            },
            route('volunteer.dashboard', ['section' => match ($type) {
                'task' => 'tasks',
                'event' => 'events',
                'certificate' => 'certificates',
                default => 'notifications',
            }])
        );

        $address = trim((string) $volunteer->email);
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $error = 'No email address is saved on this account.';

            return false;
        }

        $mail = new VolunteerNoticeMail($volunteer, $organization, $title, $message, $events, $assignments);

        $from = filter_var($organization->email, FILTER_VALIDATE_EMAIL)
            ? $organization->email
            : 'org@volunteerhub.ph';

        $smtpError = null;
        if (self::mailIsReady()) {
            try {
                Mail::to($address)->send($mail);

                return true;
            } catch (\Throwable $exception) {
                report($exception);
                $smtpError = $exception->getMessage();
            }
        }

        try {
            RegisteredMailbox::send($address, $title, $mail->render(), $from, $organization->name);

            return true;
        } catch (\Throwable $exception) {
            report($exception);
            $error = self::deliveryError($smtpError, $exception->getMessage());

            return false;
        }
    }

    private static function recipients(User $organization, string $type): Collection
    {
        $eventIds = Event::where('organization_id', $organization->id)->pluck('id');
        $assignedIds = $eventIds->isEmpty()
            ? collect()
            : Assignment::whereIn('event_id', $eventIds)->pluck('user_id')->filter()->unique()->values();

        if ($assignedIds->isNotEmpty()) {
            User::where('role', 'volunteer')
                ->where('status', 'approved')
                ->whereNull('organization_id')
                ->whereIn('id', $assignedIds)
                ->update(['organization_id' => $organization->id]);
        }

        $volunteers = User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get();

        if ($type !== 'task') {
            return $volunteers;
        }

        return $volunteers->filter(function (User $volunteer) use ($organization) {
            return self::openAssignments($organization, $volunteer)->isNotEmpty();
        })->values();
    }

    private static function members(User $organization): Collection
    {
        return User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('membership_fee_paid_at')
            ->whereNotNull('membership_attended_at')
            ->orderBy('name')
            ->get();
    }

    private static function applicants(User $organization): Collection
    {
        return User::where('role', 'volunteer')
            ->where('status', 'approved')
            ->where('organization_id', $organization->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($query) {
                $query->whereNull('membership_fee_paid_at')
                    ->orWhereNull('membership_attended_at');
            })
            ->orderBy('name')
            ->get();
    }

    private static function nextSeminar(User $organization): ?MembershipSeminar
    {
        return MembershipSeminar::where('organization_id', $organization->id)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->first();
    }

    private static function seminarFacts(MembershipSeminar $seminar): string
    {
        return implode("\n", array_filter([
            'Seminar: '.$seminar->title,
            'Schedule: '.$seminar->starts_at->format('M d, Y g:i A').' to '.$seminar->ends_at->format('g:i A'),
            'Location: '.$seminar->location,
            'Membership fee: PHP '.number_format((float) $seminar->fee_amount, 2),
            $seminar->notes ? 'Note: '.$seminar->notes : null,
        ]));
    }

    private static function assignmentFacts(Task $task, ?User $previous, User $current): string
    {
        $event = $task->event;
        $lines = [
            'Task: '.$task->title,
            'Event: '.($event->title ?? 'Event'),
            'Location: '.($event->location ?: 'Not set'),
            'Schedule: '.($event && $event->start_time ? $event->start_time->format('M d, Y g:i A') : 'Not set'),
            'Deadline: '.($task->due_date ? $task->due_date->format('M d, Y g:i A') : 'Not set'),
        ];
        if ($previous) {
            $lines[] = 'Previous volunteer: '.$previous->name;
        }
        $lines[] = 'Assigned volunteer: '.$current->name;

        return implode("\n", array_filter($lines, fn ($line) => $line !== null));
    }

    private static function upcomingEvents(User $organization): Collection
    {
        return Event::where('organization_id', $organization->id)
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();
    }

    private static function openAssignments(User $organization, User $volunteer): Collection
    {
        $eventIds = Event::where('organization_id', $organization->id)->pluck('id');
        if ($eventIds->isEmpty()) {
            return collect();
        }

        return Assignment::where('user_id', $volunteer->id)
            ->whereIn('event_id', $eventIds)
            ->whereIn('status', ['approved', 'assigned', 'submitted', 'in_progress'])
            ->with(['task', 'event'])
            ->get();
    }

    private static function digest(string $message, Collection $events, Collection $assignments): string
    {
        $parts = [trim($message)];
        if ($events->isNotEmpty()) {
            $parts[] = 'Events: '.$events->pluck('title')->join(', ').'.';
        }
        if ($assignments->isNotEmpty()) {
            $parts[] = 'Tasks: '.$assignments->map(fn (Assignment $assignment) => $assignment->task->title ?? 'Assigned task')->join(', ').'.';
        }

        return implode(' ', array_filter($parts));
    }

    private static function deliveryError(?string $smtpError, string $directError): string
    {
        $smtp = $smtpError ?? '';
        if (str_contains($smtp, '535') || str_contains($smtp, 'BadCredentials')) {
            return 'Google rejected the sign-in for the sending Gmail account. The notice is addressed to the volunteer\'s registered email, and it will be delivered after that app password is accepted.';
        }

        if (str_contains($directError, 'NotAuthorized') || str_contains($directError, 'IPv6Auth')) {
            return 'The volunteer address is correct, but Gmail will not accept mail from this computer until a sending account signs in with an app password.';
        }

        return trim($directError);
    }

    private static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $contents = file_get_contents($path);
        if ($contents === false) {
            return;
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.self::quoteEnv((string) $value);
            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents)) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents, 1);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($path, $contents);
    }

    private static function quoteEnv(string $value): string
    {
        if ($value === '' || preg_match('/[\s#="]/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    private static function record(User $organization, string $type, string $title, string $message, int $recipients, int $emails): void
    {
        OrganizationNotice::create([
            'organization_id' => $organization->id,
            'notice_type' => $type,
            'title' => $title,
            'message' => $message,
            'recipients' => $recipients,
            'emails_sent' => $emails,
        ]);
    }
}
