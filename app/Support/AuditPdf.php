<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

class AuditPdf
{
    private int $pageW = 792;

    private int $pageH = 612;

    private float $margin = 28;

    private float $cursor = 0;

    private string $ops = '';

    private array $pages = [];

    private int $pageNumber = 0;

    public static function make(Collection $logs, int $total): string
    {
        return (new self())->render($logs, $total);
    }

    private function render(Collection $logs, int $total): string
    {
        $this->startPage(false, $total, $logs->count());

        $columns = [
            ['Log ID', 42],
            ['Date and time', 108],
            ['User', 110],
            ['Role', 72],
            ['Action', 110],
            ['Module', 78],
            ['Status', 52],
            ['Record', 164],
        ];

        $this->headerRow($columns);

        if ($logs->isEmpty()) {
            $this->text($this->margin, $this->cursor + 14, 9, 'No audit logs match this filter.', [0.35, 0.42, 0.48]);
        }

        foreach ($logs as $log) {
            if (!$log instanceof AuditLog) {
                continue;
            }
            $this->need(18, $total, $logs->count(), $columns);
            $values = [
                (string) $log->id,
                AuditTrail::localTime($log->created_at),
                $log->actor_name,
                AuditTrail::roleLabel($log->actor_role),
                $log->action,
                $log->module,
                $log->status === 'failed' ? 'Failed' : 'Success',
                (string) ($log->subject_label ?? ''),
            ];
            $x = $this->margin;
            foreach ($columns as $index => [$label, $width]) {
                $this->text($x, $this->cursor + 12, 8, $this->fit($values[$index], $width - 8, 8), [0.12, 0.16, 0.22]);
                $x += $width;
            }
            $this->cursor += 16;
            $this->line($this->margin, $this->cursor, $this->pageW - $this->margin, $this->cursor, [0.90, 0.92, 0.94]);
        }

        $this->finishPage();

        return $this->document();
    }

    private function startPage(bool $continued, int $total, int $shown): void
    {
        $this->pageNumber++;
        $this->ops = '';
        $this->rect(0, 0, $this->pageW, 42, [0.000, 0.592, 0.839]);
        $this->text(28, 18, 14, 'VolunteerHub', [1, 1, 1], true);
        $this->text(150, 18, 9, 'Audit logs', [0.88, 0.96, 1]);
        $note = $continued ? 'continued' : ($shown < $total ? 'First '.$shown.' of '.$total.' records' : $total.' records');
        $this->text($this->pageW - 28 - $this->width($note, 9), 18, 9, $note, [1, 1, 1]);
        $this->cursor = 56;
        if (!$continued) {
            $this->text($this->margin, $this->cursor, 8, 'Times are Philippine time. Entries are recorded automatically and cannot be edited here.', [0.35, 0.42, 0.48]);
            $this->cursor += 16;
        }
    }

    private function headerRow(array $columns): void
    {
        $this->rect($this->margin, $this->cursor, $this->pageW - ($this->margin * 2), 16, [0.94, 0.97, 0.99]);
        $x = $this->margin;
        foreach ($columns as [$label, $width]) {
            $this->text($x + 2, $this->cursor + 11, 7, strtoupper($label), [0.20, 0.32, 0.42], true);
            $x += $width;
        }
        $this->cursor += 16;
    }

    private function need(float $height, int $total, int $shown, array $columns): void
    {
        if ($this->cursor + $height <= $this->pageH - 36) {
            return;
        }
        $this->finishPage();
        $this->startPage(true, $total, $shown);
        $this->headerRow($columns);
    }

    private function finishPage(): void
    {
        $label = 'Page '.$this->pageNumber;
        $this->text($this->pageW - $this->margin - $this->width($label, 8), $this->pageH - 16, 8, $label, [0.45, 0.50, 0.55]);
        $this->pages[] = $this->ops;
    }

    private function rect(float $x, float $top, float $width, float $height, array $color): void
    {
        $this->ops .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n", $color[0], $color[1], $color[2], $x, $this->pdfY($top + $height), $width, $height);
    }

    private function line(float $x1, float $top1, float $x2, float $top2, array $color): void
    {
        $this->ops .= sprintf("%.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S\n", $color[0], $color[1], $color[2], $x1, $this->pdfY($top1), $x2, $this->pdfY($top2));
    }

    private function text(float $x, float $baselineFromTop, float $size, string $text, array $color, bool $bold = false): void
    {
        $text = $this->pdfString($text);
        if ($text === '') {
            return;
        }
        $font = $bold ? 'F2' : 'F1';
        $this->ops .= sprintf("BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $font, $size, $color[0], $color[1], $color[2], $x, $this->pdfY($baselineFromTop), $text);
    }

    private function pdfY(float $fromTop): float
    {
        return $this->pageH - $fromTop;
    }

    private function plain(string $text): string
    {
        $text = str_replace(["–", "—", "‘", "’", "“", "”", "…", "·"], ['-', '-', "'", "'", '"', '"', '...', '-'], $text);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $text) : $converted;
    }

    private function pdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->plain($text));
    }

    private function fit(string $text, float $width, float $size): string
    {
        $text = $this->plain($text);
        $max = max(1, (int) floor($width / ($size * 0.5)));
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, max(1, $max - 1)).'.';
    }

    private function width(string $text, float $size): float
    {
        return strlen($this->plain($text)) * $size * 0.5;
    }

    private function document(): string
    {
        $next = 5;
        $contentObjects = [];
        $pageObjects = [];
        $pageNumbers = [];

        foreach ($this->pages as $stream) {
            $contentNumber = $next++;
            $pageNumber = $next++;
            $pageNumbers[] = $pageNumber;
            $contentObjects[$contentNumber] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream";
            $pageObjects[$pageNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$this->pageW.' '.$this->pageH.'] /Contents '.$contentNumber.' 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>';
        }

        $kids = implode(' ', array_map(fn (int $number) => $number.' 0 R', $pageNumbers));
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Count '.count($this->pages).' /Kids ['.$kids.'] >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ] + $contentObjects + $pageObjects;
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 ".$size."\n0000000000 65535 f \n";
        for ($number = 1; $number < $size; $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
