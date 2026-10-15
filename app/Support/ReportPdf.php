<?php

namespace App\Support;

class ReportPdf
{
    private int $pageW = 792;

    private int $pageH = 612;

    private float $margin = 28;

    private float $cursor = 0;

    private string $ops = '';

    private array $pages = [];

    private int $pageNumber = 0;

    private ActivityReport $report;

    public static function make(ActivityReport $report): string
    {
        return (new self())->render($report);
    }

    private function render(ActivityReport $report): string
    {
        $this->report = $report;
        $this->startPage(false);
        $this->cards($report->cards);
        $this->charts($report);

        foreach ($report->sections as $section) {
            $this->table(
                (string) $section['title'],
                $section['headers'],
                $section['rows'],
                (string) $section['empty']
            );
        }

        $this->finishPage();

        return $this->document();
    }

    private function startPage(bool $continued): void
    {
        $report = $this->report;
        $this->pageNumber++;
        $this->ops = '';
        $this->rect(0, 0, $this->pageW, 42, [0.000, 0.592, 0.839]);
        $audience = $report->subjectTitle !== ''
            ? $report->purposeLabel.' | '.$report->subjectTitle
            : match ($report->audience) {
                'organization' => 'Organization report',
                'volunteer' => 'Personal report',
                default => 'Platform report | '.($report->categories[$report->category] ?? 'Report'),
            };
        $this->text(28, 18, 14, 'VolunteerHub', [1, 1, 1], true);
        $this->text(150, 18, 9, $this->fit($audience, 340, 9), [0.88, 0.96, 1]);
        $period = $this->plain($report->rangeLabel).($continued ? '  |  continued' : '');
        $this->text($this->pageW - 28 - $this->width($period, 9), 18, 9, $period, [1, 1, 1]);
        $this->cursor = 56;

        if (!$continued) {
            $this->text($this->margin, $this->cursor, 8, 'Completed events and tasks use the status saved in VolunteerHub. A past date does not complete an event, and assigning a task does not complete it.', [0.35, 0.42, 0.48]);
            $this->cursor += 18;
        }
    }

    private function finishPage(): void
    {
        $this->line($this->margin, $this->pageH - 24, $this->pageW - $this->margin, $this->pageH - 24, [0.86, 0.89, 0.92]);
        $this->text($this->margin, $this->pageH - 14, 8, 'Figures and graphs are calculated from VolunteerHub records for this period.', [0.45, 0.50, 0.55]);
        $label = 'Page '.$this->pageNumber;
        $this->text($this->pageW - $this->margin - $this->width($label, 8), $this->pageH - 14, 8, $label, [0.45, 0.50, 0.55]);
        $this->pages[] = $this->ops;
    }

    private function need(float $height): void
    {
        if ($this->cursor + $height <= $this->pageH - 36) {
            return;
        }

        $this->finishPage();
        $this->startPage(true);
    }

    private function cards(array $cards): void
    {
        if ($cards === []) {
            return;
        }

        $columns = 4;
        $gap = 8;
        $width = ($this->contentWidth() - ($gap * ($columns - 1))) / $columns;
        $height = 52;

        foreach (array_chunk($cards, $columns) as $row) {
            $this->need($height + 8);
            foreach ($row as $index => $card) {
                $x = $this->margin + ($index * ($width + $gap));
                $this->roundedLook($x, $this->cursor, $width, $height);
                $this->text($x + 8, $this->cursor + 14, 7, strtoupper($this->plain((string) $card['label'])), [0.40, 0.48, 0.55]);
                $value = is_int($card['value']) ? number_format($card['value']) : (string) $card['value'];
                $this->text($x + 8, $this->cursor + 32, 16, $this->fit($value, $width - 16, 16), [0.07, 0.12, 0.18], true);
                $this->text($x + 8, $this->cursor + 44, 7, $this->fit($this->plain((string) $card['hint']), $width - 16, 7), [0.45, 0.52, 0.58]);
            }
            $this->cursor += $height + 8;
        }
    }

    private function charts(ActivityReport $report): void
    {
        $blocks = $this->chartBlocks($report);
        if ($blocks === []) {
            return;
        }

        $gap = 12;
        $width = ($this->contentWidth() - $gap) / 2;
        $height = 196;

        foreach (array_chunk($blocks, 2) as $row) {
            $this->need($height + 10);
            foreach ($row as $index => $block) {
                $x = $this->margin + ($index * ($width + $gap));
                $this->roundedLook($x, $this->cursor, count($row) === 1 ? $this->contentWidth() : $width, $height);
                $plotWidth = count($row) === 1 ? $this->contentWidth() : $width;
                $this->text($x + 10, $this->cursor + 16, 10, $block['title'], [0.07, 0.12, 0.18], true);
                $this->text($x + 10, $this->cursor + 28, 7, $this->fit($block['subtitle'], $plotWidth - 20, 7), [0.45, 0.52, 0.58]);

                if ($block['type'] === 'pie') {
                    $this->pie($x + 10, $this->cursor + 40, $plotWidth - 20, $height - 50, $block['slices']);
                } elseif ($block['type'] === 'hbar') {
                    $this->horizontalBars($x + 10, $this->cursor + 40, $plotWidth - 20, $height - 52, $block['items']);
                } elseif ($block['type'] === 'line') {
                    $this->seriesChart($x + 10, $this->cursor + 38, $plotWidth - 20, $height - 48, $block['labels'], $block['series'], true);
                } else {
                    $this->seriesChart($x + 10, $this->cursor + 38, $plotWidth - 20, $height - 48, $block['labels'], $block['series'], false);
                }
            }
            $this->cursor += $height + 10;
        }
    }

    private function chartBlocks(ActivityReport $report): array
    {
        $blocks = [];
        $eventTotal = array_sum(array_column($report->eventStatus, 'value'));
        $taskTotal = collect($report->taskStatus)->where('key', '!=', 'overdue')->sum('value');

        if ($report->showSignupChart && array_sum($report->monthlySignups) > 0 && $report->category === 'overview') {
            $blocks[] = [
                'type' => 'line',
                'title' => 'Volunteer registrations',
                'subtitle' => 'Accounts created during the selected period',
                'labels' => $report->monthlyLabels,
                'series' => [['label' => 'Registrations', 'color' => '#0097d6', 'values' => $report->monthlySignups]],
            ];
        }

        if ((array_sum($report->monthlyEvents) + array_sum($report->monthlyAttendance)) > 0 && in_array($report->category, ['overview', 'events'], true)) {
            $blocks[] = [
                'type' => 'bar',
                'title' => 'Participation trend',
                'subtitle' => 'Events that started each month, and volunteers on those events',
                'labels' => $report->monthlyLabels,
                'series' => [
                    ['label' => 'Events', 'color' => '#0097d6', 'values' => $report->monthlyEvents],
                    ['label' => 'Volunteers', 'color' => '#7dd3fc', 'values' => $report->monthlyAttendance],
                ],
            ];
        }

        if ($eventTotal > 0 && in_array($report->category, ['overview', 'events'], true)) {
            $blocks[] = [
                'type' => 'pie',
                'title' => 'Event status distribution',
                'subtitle' => 'Counted from each event status saved in VolunteerHub',
                'slices' => $report->eventStatus,
            ];
        }

        if ($taskTotal > 0 && in_array($report->category, ['overview', 'tasks'], true)) {
            $blocks[] = [
                'type' => 'hbar',
                'title' => 'Task status distribution',
                'subtitle' => 'Overdue is a deadline flag. Those tasks stay in their saved status.',
                'items' => $report->taskStatus,
            ];
        }

        return $blocks;
    }

    private function table(string $title, array $headers, array $rows, string $empty): void
    {
        $this->need(36);
        $this->text($this->margin, $this->cursor + 12, 11, $title, [0.07, 0.12, 0.18], true);
        $this->cursor += 20;

        if ($rows === []) {
            $this->text($this->margin, $this->cursor + 10, 8, $empty, [0.45, 0.52, 0.58]);
            $this->cursor += 22;

            return;
        }

        $widths = $this->columnWidths($headers, $rows);
        $this->headerRow($headers, $widths);

        foreach ($rows as $index => $row) {
            if ($this->cursor + 16 > $this->pageH - 36) {
                $this->finishPage();
                $this->startPage(true);
                $this->text($this->margin, $this->cursor + 12, 11, $title.' (continued)', [0.07, 0.12, 0.18], true);
                $this->cursor += 20;
                $this->headerRow($headers, $widths);
            }

            if ($index % 2 === 0) {
                $this->rect($this->margin, $this->cursor, $this->contentWidth(), 15, [0.96, 0.98, 0.99]);
            }

            $x = $this->margin;
            foreach ($headers as $column => $header) {
                $this->text($x + 4, $this->cursor + 11, 7.5, $this->fit((string) ($row[$column] ?? ''), $widths[$column] - 8, 7.5), [0.12, 0.16, 0.22]);
                $x += $widths[$column];
            }
            $this->cursor += 15;
        }

        $this->cursor += 12;
    }

    private function headerRow(array $headers, array $widths): void
    {
        $this->rect($this->margin, $this->cursor, array_sum($widths), 16, [0.000, 0.592, 0.839]);
        $x = $this->margin;
        foreach ($headers as $column => $header) {
            $this->text($x + 4, $this->cursor + 11, 7, $this->fit(strtoupper((string) $header), $widths[$column] - 8, 7), [1, 1, 1], true);
            $x += $widths[$column];
        }
        $this->cursor += 16;
    }

    private function columnWidths(array $headers, array $rows): array
    {
        $weights = [];
        foreach ($headers as $index => $header) {
            $weights[$index] = max(4, min(22, strlen((string) $header)));
        }
        foreach ($rows as $row) {
            foreach ($headers as $index => $header) {
                $weights[$index] = max($weights[$index], min(26, strlen($this->plain((string) ($row[$index] ?? '')))));
            }
        }
        $total = array_sum($weights) ?: 1;
        $widths = [];
        $used = 0;
        $last = array_key_last($headers);
        foreach ($headers as $index => $header) {
            if ($index === $last) {
                $widths[$index] = $this->contentWidth() - $used;
            } else {
                $widths[$index] = round($this->contentWidth() * ($weights[$index] / $total), 2);
                $used += $widths[$index];
            }
        }

        return $widths;
    }

    private function seriesChart(float $x, float $top, float $width, float $height, array $labels, array $series, bool $line): void
    {
        $max = 1;
        foreach ($series as $set) {
            $max = max($max, max(array_map('floatval', $set['values'] ?: [0])));
        }

        $plotX = $x + 28;
        $plotY = $top + 8;
        $plotW = $width - 36;
        $plotH = $height - 36;
        $baseline = $plotY + $plotH;

        $this->line($plotX, $baseline, $plotX + $plotW, $baseline, [0.80, 0.84, 0.88]);
        $this->line($plotX, $plotY, $plotX, $baseline, [0.80, 0.84, 0.88]);
        $this->text($x, $plotY + 4, 7, (string) (int) ceil($max), [0.45, 0.52, 0.58]);
        $this->text($x, $baseline, 7, '0', [0.45, 0.52, 0.58]);

        $count = max(1, count($labels));
        $slot = $plotW / $count;

        if ($line) {
            $values = array_map('floatval', $series[0]['values'] ?? []);
            $color = $this->rgb($series[0]['color'] ?? '#0097d6');
            $points = [];
            foreach ($values as $index => $value) {
                $px = $plotX + ($slot * $index) + ($slot / 2);
                $py = $baseline - ($plotH * ($value / $max));
                $points[] = [$px, $this->pdfY($py)];
            }
            if (count($points) >= 2) {
                $this->ops .= sprintf("%.3f %.3f %.3f RG 1.6 w\n", $color[0], $color[1], $color[2]);
                $this->ops .= sprintf("%.2f %.2f m\n", $points[0][0], $points[0][1]);
                foreach (array_slice($points, 1) as $point) {
                    $this->ops .= sprintf("%.2f %.2f l\n", $point[0], $point[1]);
                }
                $this->ops .= "S\n";
            }
            foreach ($points as $point) {
                $this->rect($point[0] - 2.2, $this->fromPdfY($point[1]) - 2.2, 4.4, 4.4, $color);
            }
        } else {
            $sets = count($series);
            $barWidth = min(18, ($slot * 0.72) / max(1, $sets));
            foreach ($series as $setIndex => $set) {
                foreach ($set['values'] as $index => $value) {
                    $barHeight = $plotH * ((float) $value / $max);
                    if ($barHeight <= 0) {
                        continue;
                    }
                    $groupX = $plotX + ($slot * $index) + (($slot - ($barWidth * $sets)) / 2);
                    $color = $this->rgb($set['colors'][$index] ?? $set['color'] ?? '#0097d6');
                    $this->rect($groupX + ($setIndex * $barWidth), $baseline - $barHeight, $barWidth - 1.5, $barHeight, $color);
                }
            }
        }

        foreach ($labels as $index => $label) {
            $short = $this->shortLabel((string) $label);
            $lx = $plotX + ($slot * $index) + ($slot / 2) - ($this->width($short, 6.5) / 2);
            $this->text($lx, $baseline + 12, 6.5, $short, [0.35, 0.42, 0.48]);
        }

        $legendX = $x;
        foreach ($series as $set) {
            if ($sets = isset($set['colors'])) {
                continue;
            }
            $color = $this->rgb($set['color'] ?? '#0097d6');
            $this->rect($legendX, $top + $height - 10, 8, 6, $color);
            $this->text($legendX + 11, $top + $height - 5, 7, (string) $set['label'], [0.25, 0.32, 0.38]);
            $legendX += 12 + $this->width((string) $set['label'], 7) + 10;
        }
    }

    private function horizontalBars(float $x, float $top, float $width, float $height, array $items): void
    {
        $max = max(1, max(array_map(fn (array $item) => (int) $item['value'], $items)));
        $labelWidth = 78;
        $rowHeight = $height / max(1, count($items));

        foreach ($items as $index => $item) {
            $y = $top + ($index * $rowHeight);
            $this->text($x, $y + 11, 8, $this->fit((string) $item['label'], $labelWidth - 6, 8), [0.20, 0.27, 0.33]);
            $barWidth = ($width - $labelWidth - 24) * ((int) $item['value'] / $max);
            if ($barWidth > 0) {
                $this->rect($x + $labelWidth, $y + 2, $barWidth, min(11, $rowHeight - 4), $this->rgb($item['color'] ?? '#0097d6'));
            }
            $this->text($x + $labelWidth + $barWidth + 4, $y + 11, 8, (string) (int) $item['value'], [0.12, 0.16, 0.22], true);
        }
    }

    private function pie(float $x, float $top, float $width, float $height, array $slices): void
    {
        $slices = array_values(array_filter($slices, fn (array $slice) => (int) $slice['value'] > 0));
        $total = array_sum(array_column($slices, 'value'));
        if ($total <= 0) {
            return;
        }

        $radius = min(48, ($height / 2) - 4);
        $cx = $x + $radius + 8;
        $cy = $this->pdfY($top + ($height / 2));
        $angle = M_PI / 2;

        foreach ($slices as $slice) {
            $sweep = ((int) $slice['value'] / $total) * (M_PI * 2);
            $this->slice($cx, $cy, $radius, $angle, $angle - $sweep, $this->rgb($slice['color'] ?? '#0097d6'));
            $angle -= $sweep;
        }

        $this->circle($cx, $cy, $radius * 0.58, [1, 1, 1]);
        $this->text($cx - ($this->width((string) $total, 11) / 2), $this->fromPdfY($cy) + 3, 11, (string) $total, [0.07, 0.12, 0.18], true);

        $legendY = $top + 8;
        foreach ($slices as $slice) {
            $this->rect($x + ($radius * 2) + 28, $legendY, 8, 8, $this->rgb($slice['color'] ?? '#0097d6'));
            $label = $slice['label'].'  '.$slice['value'];
            $this->text($x + ($radius * 2) + 40, $legendY + 7, 8, $this->fit($label, $width - (($radius * 2) + 48), 8), [0.12, 0.16, 0.22]);
            $legendY += 14;
        }
    }

    private function slice(float $cx, float $cy, float $radius, float $start, float $end, array $color): void
    {
        $this->ops .= sprintf("%.3f %.3f %.3f rg\n", $color[0], $color[1], $color[2]);
        $this->ops .= sprintf("%.2f %.2f m\n", $cx, $cy);
        $this->ops .= sprintf("%.2f %.2f l\n", $cx + ($radius * cos($start)), $cy + ($radius * sin($start)));

        $angle = $start;
        while ($angle - $end > 0.001) {
            $next = max($end, $angle - (M_PI / 2));
            [$c1x, $c1y, $c2x, $c2y, $ex, $ey] = $this->arc($cx, $cy, $radius, $angle, $next);
            $this->ops .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $c1x, $c1y, $c2x, $c2y, $ex, $ey);
            $angle = $next;
        }

        $this->ops .= "h\nf\n";
    }

    private function arc(float $cx, float $cy, float $radius, float $from, float $to): array
    {
        $delta = $to - $from;
        $kappa = (4 / 3) * tan($delta / 4);
        $x1 = $cx + ($radius * cos($from));
        $y1 = $cy + ($radius * sin($from));
        $x2 = $cx + ($radius * cos($to));
        $y2 = $cy + ($radius * sin($to));

        return [
            $x1 - ($radius * $kappa * sin($from)),
            $y1 + ($radius * $kappa * cos($from)),
            $x2 + ($radius * $kappa * sin($to)),
            $y2 - ($radius * $kappa * cos($to)),
            $x2,
            $y2,
        ];
    }

    private function circle(float $cx, float $cy, float $radius, array $color): void
    {
        $this->slice($cx, $cy, $radius, M_PI / 2, M_PI / 2 - (M_PI * 2), $color);
    }

    private function roundedLook(float $x, float $top, float $width, float $height): void
    {
        $this->rect($x, $top, $width, $height, [0.93, 0.95, 0.97]);
        $this->rect($x + 0.6, $top + 0.6, $width - 1.2, $height - 1.2, [1, 1, 1]);
    }

    private function rect(float $x, float $top, float $width, float $height, array $color): void
    {
        $this->ops .= sprintf(
            "%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re\nf\n",
            $color[0],
            $color[1],
            $color[2],
            $x,
            $this->pageH - $top - $height,
            $width,
            $height
        );
    }

    private function line(float $x1, float $top1, float $x2, float $top2, array $color): void
    {
        $this->ops .= sprintf(
            "%.3f %.3f %.3f RG\n0.6 w\n%.2f %.2f m\n%.2f %.2f l\nS\n",
            $color[0],
            $color[1],
            $color[2],
            $x1,
            $this->pdfY($top1),
            $x2,
            $this->pdfY($top2)
        );
    }

    private function text(float $x, float $baselineFromTop, float $size, string $text, array $color, bool $bold = false): void
    {
        $text = $this->pdfString($text);
        if ($text === '') {
            return;
        }

        $font = $bold ? 'F2' : 'F1';
        $this->ops .= sprintf(
            "BT /%s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $this->pdfY($baselineFromTop),
            $text
        );
    }

    private function document(): string
    {
        $fontRegular = 3;
        $fontBold = 4;
        $next = 5;
        $contentObjects = [];
        $pageObjects = [];
        $pageNumbers = [];

        foreach ($this->pages as $stream) {
            $contentNumber = $next++;
            $pageNumber = $next++;
            $pageNumbers[] = $pageNumber;
            $contentObjects[$contentNumber] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream";
            $pageObjects[$pageNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$this->pageW.' '.$this->pageH.'] /Contents '.$contentNumber.' 0 R /Resources << /Font << /F1 '.$fontRegular.' 0 R /F2 '.$fontBold.' 0 R >> >> >>';
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

    private function contentWidth(): float
    {
        return $this->pageW - ($this->margin * 2);
    }

    private function pdfY(float $fromTop): float
    {
        return $this->pageH - $fromTop;
    }

    private function fromPdfY(float $pdfY): float
    {
        return $this->pageH - $pdfY;
    }

    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0.000, 0.592, 0.839];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private function plain(string $text): string
    {
        $text = str_replace(
            ["–", "—", "‘", "’", "“", "”", "…", "·"],
            ['-', '-', "'", "'", '"', '"', '...', '-'],
            $text
        );
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

    private function shortLabel(string $label): string
    {
        $label = $this->plain($label);
        if (preg_match('/^([A-Za-z]{3}) \d{4}$/', $label, $match)) {
            return $match[1];
        }

        return strlen($label) > 12 ? substr($label, 0, 11).'.' : $label;
    }

}
