<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrgNotifier
{
    public const CATEGORIES = [
        'event' => 'Event',
        'task' => 'Task',
        'volunteer' => 'Volunteer',
        'account' => 'Account',
        'certificate' => 'Certificate',
        'system' => 'System',
    ];

    public static function send(
        int $organizationId,
        string $category,
        string $title,
        string $message,
        string $related,
        ?string $url = null,
        string $action = 'Open',
        bool $mail = false,
        ?string $dedupe = null
    ): void {
        if ($organizationId <= 0 || !isset(self::CATEGORIES[$category])) {
            return;
        }

        if ($dedupe && self::exists($organizationId, $dedupe)) {
            return;
        }

        $now = Carbon::now();
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $organizationId,
            'data' => json_encode([
                'title' => $title,
                'message' => $message,
                'summary' => Str::limit($message, 160),
                'category' => $category,
                'related' => $related,
                'url' => $url,
                'action' => $action,
                'dedupe' => $dedupe,
                'icon' => 'fa-bell',
            ]),
            'read_at' => null,
            'dismissed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($mail) {
            self::mailOrganization($organizationId, $category, $title, $message, $dedupe);
        }
    }

    public static function unreadCount(int $organizationId): int
    {
        return self::base($organizationId)->whereNull('read_at')->count();
    }

    public static function inbox(int $organizationId, ?string $box, ?string $category, ?string $search): Collection
    {
        $query = self::base($organizationId)->orderByDesc('created_at');

        if ($box === 'unread') {
            $query->whereNull('read_at');
        }

        if ($category && isset(self::CATEGORIES[$category])) {
            $query->where('data->category', $category);
        }

        $term = trim((string) $search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('data->title', 'like', $like)
                    ->orWhere('data->message', 'like', $like)
                    ->orWhere('data->related', 'like', $like);
            });
        }

        return $query->limit(100)->get()->map(function ($row) {
            $data = json_decode($row->data, true) ?: [];
            $category = $data['category'] ?? self::inferCategory((string) ($data['title'] ?? ''));
            $data['category'] = $category;
            if (empty($data['url'])) {
                $data['url'] = self::fallbackUrl($category);
                $data['action'] = $data['action'] ?? 'Open';
            }
            $row->payload = $data;
            $row->category_label = self::CATEGORIES[$category] ?? 'System';

            return $row;
        });
    }

    public static function owned(int $organizationId, string $id): ?object
    {
        return self::base($organizationId)->where('id', $id)->first();
    }

    private static function inferCategory(string $title): string
    {
        $title = strtolower($title);
        if (str_contains($title, 'certificate')) {
            return 'certificate';
        }
        if (str_contains($title, 'application') || str_contains($title, 'volunteer')) {
            return 'volunteer';
        }
        if (str_contains($title, 'task') || str_contains($title, 'deadline') || str_contains($title, 'assignment')) {
            return 'task';
        }
        if (str_contains($title, 'account') || str_contains($title, 'verification') || str_contains($title, 'document')) {
            return 'account';
        }
        if (str_contains($title, 'event')) {
            return 'event';
        }

        return 'system';
    }

    private static function fallbackUrl(string $category): string
    {
        $section = match ($category) {
            'event' => 'events',
            'task' => 'tasks',
            'volunteer' => 'volunteers',
            'certificate' => 'certificates',
            'account' => 'settings',
            default => 'notifications',
        };

        return route('org.dashboard', ['section' => $section]);
    }

    private static function base(int $organizationId)
    {
        return DB::table('notifications')
            ->where('notifiable_id', $organizationId)
            ->where('notifiable_type', User::class)
            ->whereNull('dismissed_at');
    }

    private static function exists(int $organizationId, string $dedupe): bool
    {
        return DB::table('notifications')
            ->where('notifiable_id', $organizationId)
            ->where('notifiable_type', User::class)
            ->where('data->dedupe', $dedupe)
            ->exists();
    }

    private static function mailOrganization(int $organizationId, string $category, string $title, string $message, ?string $dedupe): void
    {
        if (!VolunteerNotice::mailIsReady()) {
            return;
        }

        $organization = User::find($organizationId);
        if (!$organization || !OrgPreferences::allowsMail($organization, $category, $title)) {
            return;
        }

        $address = trim((string) ($organization->email ?? ''));
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($address, $title, $organization) {
                $mail->to($address)
                    ->from((string) config('mail.from.address'), $organization->name ?: (string) config('mail.from.name'))
                    ->subject($title);
            });
        } catch (\Throwable $exception) {
            report($exception);
            self::send(
                $organizationId,
                'system',
                'Email delivery failed',
                'VolunteerHub could not email "'.$title.'" to '.$address.'. The notice is still in Notifications.',
                $organization->name,
                route('org.dashboard', ['section' => 'notifications']),
                'View notifications',
                false,
                'mail-fail:'.($dedupe ?: md5($title.$message))
            );
        }
    }
}
