<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportFiles
{
    public static function excel(ActivityReport $report): StreamedResponse
    {
        $filename = self::filename($report, 'xls');

        return response()->streamDownload(function () use ($report) {
            echo self::spreadsheet($report);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public static function pdf(ActivityReport $report): StreamedResponse
    {
        $filename = self::filename($report, 'pdf');

        return response()->streamDownload(function () use ($report) {
            echo ReportPdf::make($report);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private static function filename(ActivityReport $report, string $extension): string
    {
        $subject = $report->subjectTitle !== ''
            ? trim((string) preg_replace('/[^a-z0-9]+/i', '-', $report->subjectTitle), '-')
            : $report->category;

        return 'volunteerhub-'.strtolower($subject).'-'.$report->startDate.'-to-'.$report->endDate.'.'.$extension;
    }

    private static function spreadsheet(ActivityReport $report): string
    {
        $rows = [
            ['VolunteerHub report'],
            [$report->subjectTitle !== '' ? $report->purposeLabel.' | '.$report->subjectTitle : ($report->categories[$report->category] ?? 'Report')],
            ['Period', $report->rangeLabel],
            ['Completed events and tasks use the status saved in VolunteerHub.'],
            [],
            ['Figure', 'Value', 'How it is counted'],
        ];

        foreach ($report->cards as $card) {
            $rows[] = [$card['label'], (string) $card['value'], $card['hint']];
        }

        foreach ($report->sections as $section) {
            $rows[] = [];
            $rows[] = [$section['title']];
            if ($section['rows'] === []) {
                $rows[] = [$section['empty']];
                continue;
            }
            $rows[] = $section['headers'];
            foreach ($section['rows'] as $row) {
                $rows[] = $row;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Worksheet ss:Name="Report"><Table>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                $xml .= '<Cell><Data ss:Type="String">'.self::xml((string) $cell).'</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        return $xml.'</Table></Worksheet></Workbook>';
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
