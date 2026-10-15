<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditTrail
{
    public const MODULES = [
        'Authentication',
        'Accounts',
        'Organizations',
        'Events',
        'Tasks',
        'Volunteers',
        'Certificates',
        'Reports',
        'System',
    ];

    public const ACTION_TYPES = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'other' => 'Other',
    ];

    public const ROLES = [
        'admin' => 'Admin',
        'organization' => 'Organization',
        'volunteer' => 'Volunteer',
    ];

    public const STATUSES = [
        'success' => 'Success',
        'failed' => 'Failed',
    ];

    public static function record(array $entry): void
    {
        try {
            $actor = $entry['actor'] ?? Auth::user();
            $actor = $actor instanceof User ? $actor : null;
            $role = (string) ($entry['actor_role'] ?? $actor->role ?? 'unknown');
            $name = trim((string) ($entry['actor_name'] ?? $actor->name ?? 'Unknown'));
            if ($name === '') {
                $name = 'Unknown';
            }

            $subject = $entry['subject'] ?? null;
            $previous = self::clean($entry['previous'] ?? null);
            $new = self::clean($entry['new'] ?? null);
            $request = request();

            AuditLog::create([
                'user_id' => array_key_exists('user_id', $entry) ? $entry['user_id'] : $actor?->id,
                'actor_name' => mb_substr($name, 0, 255),
                'actor_role' => mb_substr($role, 0, 32),
                'action' => mb_substr((string) $entry['action'], 0, 255),
                'action_type' => array_key_exists($entry['action_type'] ?? '', self::ACTION_TYPES) ? $entry['action_type'] : 'other',
                'module' => in_array($entry['module'] ?? '', self::MODULES, true) ? $entry['module'] : 'System',
                'description' => mb_substr(trim((string) ($entry['description'] ?? '')), 0, 500),
                'status' => ($entry['status'] ?? 'success') === 'failed' ? 'failed' : 'success',
                'subject_type' => $subject instanceof Model ? $subject::class : null,
                'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
                'subject_label' => isset($entry['subject_label']) ? mb_substr((string) $entry['subject_label'], 0, 255) : null,
                'previous_values' => $previous,
                'new_values' => $new,
                'ip_address' => $request instanceof Request ? mb_substr((string) $request->ip(), 0, 45) : null,
                'created_at' => Carbon::now('UTC'),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public static function report(Request $request, ?string $format = null): void
    {
        if ($format === null && $request->query() === []) {
            return;
        }

        $purpose = (string) $request->query('purpose', 'platform');
        $subject = match ($purpose) {
            'organization' => 'organization '.$request->query('organization', ''),
            'event' => 'event '.$request->query('event', ''),
            'task' => 'task '.$request->query('task', ''),
            'volunteer' => 'volunteer '.$request->query('volunteer', ''),
            default => 'platform '.$request->query('category', 'overview'),
        };

        self::record([
            'action' => $format ? 'Report exported' : 'Report generated',
            'action_type' => 'other',
            'module' => 'Reports',
            'description' => $format
                ? 'Exported the '.$subject.' report as '.strtoupper($format).'.'
                : 'Generated the '.$subject.' report.',
            'status' => 'success',
            'new' => array_filter([
                'format' => $format,
                'purpose' => $purpose,
                'category' => $request->query('category'),
                'range' => $request->query('range'),
                'organization' => $request->query('organization'),
                'event' => $request->query('event'),
                'task' => $request->query('task'),
                'volunteer' => $request->query('volunteer'),
            ], fn ($value) => $value !== null && $value !== ''),
        ]);
    }

    public static function summary(): array
    {
        $counts = AuditLog::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when actor_role = 'admin' then 1 else 0 end) as admin_count")
            ->selectRaw("sum(case when actor_role = 'organization' then 1 else 0 end) as organization_count")
            ->selectRaw("sum(case when actor_role = 'volunteer' then 1 else 0 end) as volunteer_count")
            ->selectRaw("sum(case when status = 'failed' then 1 else 0 end) as failed_count")
            ->first();

        return [
            'total' => (int) ($counts->total ?? 0),
            'admin' => (int) ($counts->admin_count ?? 0),
            'organization' => (int) ($counts->organization_count ?? 0),
            'volunteer' => (int) ($counts->volunteer_count ?? 0),
            'failed' => (int) ($counts->failed_count ?? 0),
        ];
    }

    public static function filtered(Request $request): Builder
    {
        $query = AuditLog::query()->orderByDesc('created_at')->orderByDesc('id');
        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('actor_name', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('subject_label', 'like', '%'.$search.'%');
            });
        }

        $timezone = config('audit.display_timezone', 'Asia/Manila');

        if ($request->filled('from')) {
            try {
                $query->where('created_at', '>=', Carbon::parse($request->query('from'), $timezone)->startOfDay()->utc());
            } catch (\Throwable) {
            }
        }

        if ($request->filled('to')) {
            try {
                $query->where('created_at', '<=', Carbon::parse($request->query('to'), $timezone)->endOfDay()->utc());
            } catch (\Throwable) {
            }
        }

        $role = (string) $request->query('role', '');
        if (array_key_exists($role, self::ROLES)) {
            $query->where('actor_role', $role);
        }

        $module = (string) $request->query('module', '');
        if (in_array($module, self::MODULES, true)) {
            $query->where('module', $module);
        }

        $actionType = (string) $request->query('action_type', '');
        if (array_key_exists($actionType, self::ACTION_TYPES)) {
            $query->where('action_type', $actionType);
        }

        $status = (string) $request->query('status', '');
        if (array_key_exists($status, self::STATUSES)) {
            $query->where('status', $status);
        }

        return $query;
    }

    public static function localTime(Carbon $time): string
    {
        return $time->copy()
            ->timezone(config('audit.display_timezone', 'Asia/Manila'))
            ->format('M j, Y g:i A');
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLES[$role] ?? 'Unknown';
    }

    public static function trailWhen(Carbon $time): string
    {
        $local = $time->copy()->timezone(config('audit.display_timezone', 'Asia/Manila'));
        $now = Carbon::now(config('audit.display_timezone', 'Asia/Manila'));

        if ($local->isSameDay($now)) {
            return $local->format('g:i A');
        }

        if ($local->isSameDay($now->copy()->subDay())) {
            return 'Yesterday';
        }

        return $local->year === $now->year ? $local->format('M d') : $local->format('M d, Y');
    }

    public static function icon(AuditLog $log): array
    {
        $action = strtolower($log->action);

        if ($log->status === 'failed' || str_contains($action, 'denied') || str_contains($action, 'blocked')) {
            return ['fa-circle-xmark', 'bg-rose-50 text-rose-500'];
        }
        if (str_contains($action, 'logged in') || str_contains($action, 'password')) {
            return ['fa-key', 'bg-amber-50 text-amber-500'];
        }
        if (str_contains($action, 'logged out')) {
            return ['fa-arrow-right-from-bracket', 'bg-slate-100 text-slate-500'];
        }
        if (str_contains($action, 'approved') || str_contains($action, 'issued')) {
            return ['fa-circle-check', 'bg-emerald-50 text-emerald-500'];
        }
        if (str_contains($action, 'rejected')) {
            return ['fa-ban', 'bg-rose-50 text-rose-500'];
        }
        if (str_contains($action, 'deleted') || str_contains($action, 'removed') || str_contains($action, 'pruned')) {
            return ['fa-trash', 'bg-rose-50 text-rose-500'];
        }
        if (str_contains($action, 'created') || str_contains($action, 'registered')) {
            return ['fa-plus', 'bg-sky-50 text-sky-600'];
        }
        if (str_contains($action, 'updated') || str_contains($action, 'changed') || str_contains($action, 'reassigned')) {
            return ['fa-pen', 'bg-sky-50 text-sky-600'];
        }
        if (str_contains($action, 'notice') || str_contains($action, 'invite') || str_contains($action, 'reply')) {
            return ['fa-bullhorn', 'bg-sky-50 text-sky-600'];
        }
        if (str_contains($action, 'export') || str_contains($action, 'generated') || str_contains($action, 'downloaded') || str_contains($action, 'opened')) {
            return ['fa-file-export', 'bg-emerald-50 text-emerald-600'];
        }

        return ['fa-clock-rotate-left', 'bg-slate-100 text-slate-500'];
    }

    public static function prune(): int
    {
        $months = max(1, (int) config('audit.retention_months', 24));
        $cutoff = Carbon::now('UTC')->subMonths($months);
        $removed = AuditLog::where('created_at', '<', $cutoff)->delete();

        if ($removed > 0) {
            self::record([
                'action' => 'Audit logs pruned',
                'action_type' => 'deleted',
                'module' => 'System',
                'description' => $removed.' audit log(s) older than '.$months.' months were removed by the retention task.',
                'status' => 'success',
                'actor_name' => 'VolunteerHub',
                'actor_role' => 'system',
                'user_id' => null,
                'new' => ['removed' => $removed, 'retention_months' => $months],
            ]);
        }

        return $removed;
    }

    private static function clean(mixed $values): ?array
    {
        if (!is_array($values) || $values === []) {
            return null;
        }

        $blocked = [
            'password',
            'password_confirmation',
            'remember_token',
            'token',
            'completion_proof',
            'completion_proof_path',
            'file_path',
            'checksum',
        ];

        $clean = [];
        foreach ($values as $key => $value) {
            if (in_array((string) $key, $blocked, true)) {
                continue;
            }
            if (is_array($value)) {
                $nested = self::clean($value);
                if ($nested !== null) {
                    $clean[$key] = $nested;
                }
                continue;
            }
            if ($value instanceof Carbon) {
                $clean[$key] = $value->toDateTimeString();
                continue;
            }
            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$key] = $value;
                continue;
            }
            $clean[$key] = mb_substr(trim((string) $value), 0, 300);
        }

        return $clean === [] ? null : $clean;
    }
}
