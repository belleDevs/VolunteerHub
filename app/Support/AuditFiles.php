<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditFiles
{
    public static function csv(Builder $query): StreamedResponse
    {
        $filename = 'volunteerhub-audit-logs-'.now('UTC')->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Log ID', 'Date and time', 'User name', 'User role', 'Action', 'Module', 'Description', 'Status', 'Affected record', 'Previous values', 'New values', 'IP address']);

            foreach ($query->clone()->cursor() as $log) {
                fputcsv($handle, self::row($log));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function pdf(Builder $query): StreamedResponse
    {
        $filename = 'volunteerhub-audit-logs-'.now('UTC')->format('Y-m-d').'.pdf';
        $total = (clone $query)->count();
        $logs = $query->clone()->orderByDesc('created_at')->orderByDesc('id')->limit(500)->get();

        return response()->streamDownload(function () use ($logs, $total) {
            echo AuditPdf::make($logs, $total);
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    private static function row(AuditLog $log): array
    {
        return [
            $log->id,
            AuditTrail::localTime($log->created_at),
            $log->actor_name,
            AuditTrail::roleLabel($log->actor_role),
            $log->action,
            $log->module,
            $log->description,
            $log->status === 'failed' ? 'Failed' : 'Success',
            $log->subject_label ?? '',
            $log->previous_values ? json_encode($log->previous_values, JSON_UNESCAPED_UNICODE) : '',
            $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
            $log->ip_address ?? '',
        ];
    }
}
