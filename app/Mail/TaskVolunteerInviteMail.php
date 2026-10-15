<?php

namespace App\Mail;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskVolunteerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $volunteer
    ) {
        $this->task->loadMissing(['event.organization', 'skills']);
    }

    public function envelope(): Envelope
    {
        $organization = $this->task->event?->organization;

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $organization?->name ?: (string) config('mail.from.name')
            ),
            subject: 'Volunteer Needed: ' . $this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task-volunteer-invite',
        );
    }
}
