<?php

namespace App\Filament\Widgets;

use App\Services\GrowthReport;
use Filament\Widgets\ChartWidget;

/**
 * Which cuisines the published catalogue actually covers.
 */
class CuisineBreakdown extends ChartWidget
{
    protected ?string $heading = 'Cuisines in the catalogue';

    protected ?string $description = 'The eight largest, by published recipes.';

    protected ?string $maxHeight = '320px';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $report = app(GrowthReport::class)->cuisineBreakdown();

        return [
            'labels' => $report['labels'],
            'datasets' => [
                [
                    'label' => 'Recipes',
                    'data' => $report['counts'],
                    'backgroundColor' => ['#FC7100', '#F45F67', '#9B85FF', '#2AD3B1', '#FFC42E', '#6DB544', '#E8743B', '#7C6BD6'],
                    'borderWidth' => 0,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'right']]];
    }
}
