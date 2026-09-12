<?php

namespace App\Filament\Widgets;

use App\Services\GrowthReport;
use Filament\Widgets\ChartWidget;

/**
 * Signups, new recipes and new ratings, week by week.
 */
class GrowthChart extends ChartWidget
{
    protected ?string $heading = 'Activity by week';

    protected ?string $description = 'New accounts, recipes and ratings over the last twelve weeks.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $report = app(GrowthReport::class)->weekly();

        return [
            'labels' => $report['labels'],
            'datasets' => [
                [
                    'label' => 'Recipes',
                    'data' => $report['recipes'],
                    'borderColor' => '#FC7100',
                    'backgroundColor' => 'rgba(252, 113, 0, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Ratings',
                    'data' => $report['ratings'],
                    'borderColor' => '#2AD3B1',
                    'backgroundColor' => 'rgba(42, 211, 177, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'New cooks',
                    'data' => $report['users'],
                    'borderColor' => '#9B85FF',
                    'backgroundColor' => 'rgba(155, 133, 255, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
