<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VolunteerNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $volunteer,
        public User $organization,
        public string $noticeTitle,
        public string $noticeMessage,
        public Collection $events,
        public Collection $assignments
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $this->organization->name ?: (string) config('mail.from.name')
            ),
            subject: $this->noticeTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.volunteer-notice',
        );
    }
}
