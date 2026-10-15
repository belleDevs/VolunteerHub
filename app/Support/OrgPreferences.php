<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class OrgPreferences
{
    public const DATE_FORMATS = [
        'M j, Y' => 'Oct 11, 2026',
        'F j, Y' => 'October 11, 2026',
        'Y-m-d' => '2026-10-11',
        'm/d/Y' => '10/11/2026',
    ];

    public const TIMEZONES = [
        'Asia/Manila' => 'Philippine Time (Asia/Manila)',
        'UTC' => 'UTC',
    ];

    public const NOTIFICATION_KEYS = [
        'event_decisions' => 'Event approval or rejection',
        'applications' => 'New volunteer applications',
        'task_updates' => 'Task assignment and progress',
        'deadlines' => 'Task deadline reminders',
        'completions' => 'Volunteer task completion submissions',
        'email' => 'Email notifications',
    ];

    public static function defaults(): array
    {
        return array_fill_keys(array_keys(self::NOTIFICATION_KEYS), true);
    }

    public static function forUser(User $user): array
    {
        $saved = is_array($user->notification_preferences) ? $user->notification_preferences : [];

        return array_merge(self::defaults(), array_intersect_key($saved, self::defaults()));
    }

    public static function timezone(User $user): string
    {
        return array_key_exists((string) $user->timezone, self::TIMEZONES) ? $user->timezone : 'Asia/Manila';
    }

    public static function dateFormat(User $user): string
    {
        return array_key_exists((string) $user->date_format, self::DATE_FORMATS) ? $user->date_format : 'M j, Y';
    }

    public static function format(?Carbon $time, ?User $user = null, bool $withTime = false): string
    {
        if (!$time) {
            return '';
        }

        $user = $user ?? auth()->user();
        if (!$user) {
            return $time->timezone('Asia/Manila')->format($withTime ? 'M j, Y g:i A' : 'M j, Y');
        }

        $format = self::dateFormat($user).($withTime ? ' g:i A' : '');

        return $time->copy()->timezone(self::timezone($user))->format($format);
    }

    public static function allowsMail(User $user, string $category, string $title): bool
    {
        $preferences = self::forUser($user);
        if (empty($preferences['email'])) {
            return false;
        }

        $key = self::preferenceFor($category, $title);

        return $key === null || !empty($preferences[$key]);
    }

    private static function preferenceFor(string $category, string $title): ?string
    {
        $title = strtolower($title);

        if ($category === 'event' && (str_contains($title, 'approved') || str_contains($title, 'rejected') || str_contains($title, 'requires changes'))) {
            return 'event_decisions';
        }
        if (str_contains($title, 'application')) {
            return 'applications';
        }
        if (str_contains($title, 'deadline') || str_contains($title, 'overdue') || str_contains($title, 'upcoming event')) {
            return 'deadlines';
        }
        if (str_contains($title, 'completion') || str_contains($title, 'submitted')) {
            return 'completions';
        }
        if (str_contains($title, 'assign') || str_contains($title, 'reassign') || str_contains($title, 'completed') || str_contains($title, 'task created')) {
            return 'task_updates';
        }

        return null;
    }
}
