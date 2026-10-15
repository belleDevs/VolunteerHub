<?php

namespace App\Support;

use Carbon\Carbon;

class ScheduleClock
{
    public const ZONE = 'Asia/Manila';

    public static function now(): Carbon
    {
        return Carbon::now(self::ZONE);
    }

    /**
     * Event and task times are stored as the clock time that was entered,
     * without a timezone conversion.
     */
    public static function at(?Carbon $time): ?Carbon
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($time->format('Y-m-d H:i:s'), self::ZONE);
    }

    public static function format(?Carbon $time): string
    {
        $local = self::at($time);

        return $local ? $local->format('M j, Y g:i A') : '';
    }

    public static function stamp(?Carbon $time): string
    {
        if (!$time) {
            return '';
        }

        return $time->copy()->timezone(self::ZONE)->format('M j, Y g:i A');
    }

    public static function range(?Carbon $start, ?Carbon $end): string
    {
        $startText = self::format($start);
        $endText = self::format($end);

        if ($startText && $endText && $startText !== $endText) {
            return $startText.' – '.$endText;
        }

        return $startText ?: $endText;
    }

    public static function phase(?Carbon $start, ?Carbon $end, ?string $status = null): string
    {
        if (in_array($status, ['completed', 'cancelled'], true)) {
            return 'past';
        }

        $now = self::now();
        $startAt = self::at($start);
        $endAt = self::at($end) ?? $startAt;

        if ($endAt && $endAt->lt($now)) {
            return 'past';
        }

        if ($startAt && $startAt->gt($now)) {
            return 'upcoming';
        }

        return 'now';
    }

    public static function label(string $phase): string
    {
        return match ($phase) {
            'past' => 'Past',
            'now' => 'Happening now',
            default => 'Upcoming',
        };
    }
}
