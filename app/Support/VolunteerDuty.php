<?php

namespace App\Support;

class VolunteerDuty
{
    public static function label(string $status, string $phase, ?string $feedback = null): string
    {
        if ($status === 'pending' && $phase === 'past') {
            return 'Uncompleted';
        }

        return match ($status) {
            'pending' => 'Pending Response',
            'approved' => $phase === 'now' ? 'In Progress' : 'Accepted',
            'submitted' => 'Submitted for Review',
            'completed' => 'Completed',
            'rejected' => 'Revision requested',
            'cancelled' => self::declined($feedback) ? 'Declined' : 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function declined(?string $feedback): bool
    {
        return is_string($feedback) && str_starts_with($feedback, 'Declined by the volunteer');
    }

    public static function canRespond(string $status, string $phase): bool
    {
        return $status === 'pending' && $phase !== 'past';
    }
}
