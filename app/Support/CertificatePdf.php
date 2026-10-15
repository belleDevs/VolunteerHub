<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Certificate;

class CertificatePdf
{
    private int $pageW = 612;

    private int $pageH = 792;

    private string $ops = '';

    public static function make(Certificate $certificate): string
    {
        return (new self())->render($certificate);
    }

    private function render(Certificate $certificate): string
    {
        $blue = [0.000, 0.592, 0.839];
        $dark = [0.000, 0.451, 0.659];
        $gold = [0.961, 0.651, 0.137];
        $ink = [0.07, 0.11, 0.16];
        $muted = [0.39, 0.45, 0.51];

        $this->rect(0, 0, $this->pageW, $this->pageH, [1, 1, 1]);
        $this->stroke(28, 28, $this->pageW - 56, $this->pageH - 56, $dark, 3);
        $this->stroke(40, 40, $this->pageW - 80, $this->pageH - 80, $dark, 0.8);
        $this->corners(52, $gold);

        $organization = $certificate->event?->organization?->name ?: 'Organization';
        $event = $certificate->event->title ?? 'Event';
        $name = $certificate->user->name ?? 'Volunteer';
        $when = ScheduleClock::at($certificate->event?->start_time)?->format('F d, Y');
        $place = trim((string) ($certificate->event->location ?? ''));
        $held = $when ? 'Held on '.$when.($place !== '' ? ' at '.$place : '') : '';
        $tasks = $this->taskTitles($certificate);
        $hours = $certificate->hours_rendered
            ? rtrim(rtrim(number_format((float) $certificate->hours_rendered, 2), '0'), '.').' hours rendered'
            : '';

        $this->center(118, 9, 'VOLUNTEERHUB', $muted, true);
        $this->center(136, 11, strtoupper($organization), $dark, true);
        $this->center(190, 26, 'CERTIFICATE OF APPRECIATION', $blue, true);
        $this->line(206, 208, 406, 208, $gold);
        $this->center(232, 11, 'This credential is proudly awarded to', $muted);

        $this->center(290, 22, strtoupper($name), $ink, true);
        $this->line(120, 304, 492, 304, [0.80, 0.84, 0.88]);
        $this->center(324, 10, 'Active Hub Volunteer ID: JCI-VOL-'.sprintf('%05d', (int) $certificate->user->id), $muted);

        $this->centerWrapped(360, 11, 'In grateful recognition and appreciation of outstanding volunteer services and active community involvement rendered during the socio-civic action:', $muted, 420);
        $this->center(430, 13, $event, $dark, true);
        if ($held !== '') {
            $this->center(450, 10, $held, $muted);
        }
        if ($tasks !== []) {
            $this->center(470, 10, 'Tasks completed: '.implode(', ', $tasks), $muted);
        }
        if ($hours !== '') {
            $this->center(488, 10, $hours, $muted);
        }

        $this->circle(306, 560, 28, [1.00, 0.96, 0.90]);
        $this->strokeCircle(306, 560, 28, $gold, 2);
        $this->strokeCircle(306, 560, 22, $gold, 0.6);
        $this->center(564, 8, 'SEAL', $gold, true);

        $this->line(78, 620, 210, 620, [0.75, 0.78, 0.82]);
        $this->centerAt(144, 636, 9, $organization, $ink, true);
        $this->centerAt(144, 650, 8, 'ISSUING ORGANIZATION', $muted);

        $this->line(402, 620, 534, 620, [0.75, 0.78, 0.82]);
        $this->centerAt(468, 636, 9, 'Chapter President', $ink, true);
        $this->centerAt(468, 650, 8, 'CHAPTER SEAL HOLDER', $muted);

        $this->line(70, 700, 542, 700, [0.90, 0.91, 0.93]);
        $this->text(70, 718, 8, 'VERIFICATION BLOCK: '.($certificate->certificate_code ?? ''), $muted);
        $this->text(360, 718, 8, 'Issued by VolunteerHub', $muted);

        return $this->document();
    }

    private function taskTitles(Certificate $certificate): array
    {
        return Assignment::where('user_id', $certificate->user_id)
            ->where('event_id', $certificate->event_id)
            ->where('status', 'completed')
            ->with('task')
            ->get()
            ->map(fn (Assignment $assignment) => $assignment->task?->title)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function corners(float $inset, array $color): void
    {
        $size = 18;
        $spots = [
            [$inset, $inset, 1, 1],
            [$this->pageW - $inset, $inset, -1, 1],
            [$inset, $this->pageH - $inset, 1, -1],
            [$this->pageW - $inset, $this->pageH - $inset, -1, -1],
        ];
        foreach ($spots as [$x, $top, $dx, $dy]) {
            $this->line($x, $top, $x + ($size * $dx), $top, $color);
            $this->line($x, $top, $x, $top + ($size * $dy), $color);
        }
    }

    private function centerWrapped(float $top, float $size, string $text, array $color, float $maxWidth): void
    {
        $words = preg_split('/\s+/', $this->plain($text)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $next = trim($line.' '.$word);
            if ($this->width($next, $size) > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $next;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        foreach ($lines as $index => $row) {
            $this->center($top + ($index * ($size + 4)), $size, $row, $color);
        }
    }

    private function center(float $baseline, float $size, string $text, array $color, bool $bold = false): void
    {
        $text = $this->fit($text, $this->pageW - 120, $size);
        $this->text(($this->pageW - $this->width($text, $size)) / 2, $baseline, $size, $text, $color, $bold);
    }

    private function centerAt(float $x, float $baseline, float $size, string $text, array $color, bool $bold = false): void
    {
        $text = $this->fit($text, 180, $size);
        $this->text($x - ($this->width($text, $size) / 2), $baseline, $size, $text, $color, $bold);
    }

    private function rect(float $x, float $top, float $width, float $height, array $color): void
    {
        $this->ops .= sprintf(
            "%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re\nf\n",
            $color[0], $color[1], $color[2],
            $x, $this->pageH - $top - $height, $width, $height
        );
    }

    private function stroke(float $x, float $top, float $width, float $height, array $color, float $weight): void
    {
        $this->ops .= sprintf(
            "%.3f %.3f %.3f RG\n%.2f w\n%.2f %.2f %.2f %.2f re\nS\n",
            $color[0], $color[1], $color[2], $weight,
            $x, $this->pageH - $top - $height, $width, $height
        );
    }

    private function line(float $x1, float $top1, float $x2, float $top2, array $color): void
    {
        $this->ops .= sprintf(
            "%.3f %.3f %.3f RG\n1 w\n%.2f %.2f m\n%.2f %.2f l\nS\n",
            $color[0], $color[1], $color[2],
            $x1, $this->pageH - $top1, $x2, $this->pageH - $top2
        );
    }

    private function circle(float $cx, float $top, float $radius, array $color): void
    {
        $this->ops .= sprintf("%.3f %.3f %.3f rg\n", $color[0], $color[1], $color[2]);
        $this->ops .= $this->circlePath($cx, $top, $radius)."f\n";
    }

    private function strokeCircle(float $cx, float $top, float $radius, array $color, float $weight): void
    {
        $this->ops .= sprintf("%.3f %.3f %.3f RG\n%.2f w\n", $color[0], $color[1], $color[2], $weight);
        $this->ops .= $this->circlePath($cx, $top, $radius)."S\n";
    }

    private function circlePath(float $cx, float $top, float $radius): string
    {
        $cy = $this->pageH - $top;
        $k = 0.5522847498 * $radius;

        return sprintf("%.2f %.2f m\n", $cx + $radius, $cy)
            .sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $radius, $cy + $k, $cx + $k, $cy + $radius, $cx, $cy + $radius)
            .sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $k, $cy + $radius, $cx - $radius, $cy + $k, $cx - $radius, $cy)
            .sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $radius, $cy - $k, $cx - $k, $cy - $radius, $cx, $cy - $radius)
            .sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $k, $cy - $radius, $cx + $radius, $cy - $k, $cx + $radius, $cy);
    }

    private function text(float $x, float $baselineFromTop, float $size, string $text, array $color, bool $bold = false): void
    {
        $text = $this->pdfString($text);
        if ($text === '') {
            return;
        }

        $this->ops .= sprintf(
            "BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $bold ? 'F2' : 'F1',
            $size,
            $color[0], $color[1], $color[2],
            $x,
            $this->pageH - $baselineFromTop,
            $text
        );
    }

    private function document(): string
    {
        $stream = $this->ops;
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Count 1 /Kids [6 0 R] >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            5 => "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream",
            6 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$this->pageW.' '.$this->pageH.'] /Contents 5 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";
        for ($number = 1; $number <= 6; $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function plain(string $text): string
    {
        $text = str_replace(
            ["–", "—", "‘", "’", "“", "”", "…", "·"],
            ['-', '-', "'", "'", '"', '"', '...', '-'],
            $text
        );
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $converted === false ? (string) preg_replace('/[^\x20-\x7E]/', '', $text) : $converted;
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
}
