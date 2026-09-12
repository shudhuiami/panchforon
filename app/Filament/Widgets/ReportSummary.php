<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContentFlags\ContentFlagResource;
use App\Services\GrowthReport;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The last thirty days at a glance, plus the one quality figure worth
 * watching: how much of the catalogue nobody has rated yet.
 */
class ReportSummary extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $report = app(GrowthReport::class)->lastThirtyDays();

        return [
            Stat::make('New cooks', number_format($report['new_users']))
                ->description('Last thirty days')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->descriptionColor('gray')
                ->color('gray'),

            Stat::make('New recipes', number_format($report['new_recipes']))
                ->description('Last thirty days')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->descriptionColor('gray')
                ->color('gray'),

            Stat::make('New ratings', number_format($report['new_ratings']))
                ->description('Last thirty days')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->descriptionColor('gray')
                ->color('gray'),

            Stat::make('Unrated', $report['unrated_share'].'%')
                ->description('Of the published catalogue')
                ->descriptionIcon(Heroicon::OutlinedQuestionMarkCircle)
                ->descriptionColor($report['unrated_share'] > 50 ? 'warning' : 'gray')
                ->color($report['unrated_share'] > 50 ? 'warning' : 'gray'),

            Stat::make('Open reports', number_format($report['open_flags']))
                ->description($report['open_flags'] > 0 ? 'Waiting on a decision' : 'Nothing reported')
                ->descriptionIcon($report['open_flags'] > 0 ? Heroicon::OutlinedFlag : Heroicon::OutlinedCheckCircle)
                ->descriptionColor($report['open_flags'] > 0 ? 'danger' : 'success')
                ->color($report['open_flags'] > 0 ? 'danger' : 'gray')
                ->url(ContentFlagResource::getUrl('index')),
        ];
    }
}
