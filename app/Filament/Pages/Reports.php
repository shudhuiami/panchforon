<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CuisineBreakdown;
use App\Filament\Widgets\GrowthChart;
use App\Filament\Widgets\ReportSummary;
use App\Filament\Widgets\TopRecipes;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * How the site is doing: activity over time, what the catalogue covers, and
 * what people rate highest.
 *
 * Every figure is computed on page load. There is no queue on the target
 * hosting, so nothing here is precomputed or scheduled.
 */
class Reports extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static string $routePath = '/reports';

    /**
     * Filament lets any authenticated panel user reach a custom page by
     * default, so the admin check is repeated here rather than relying on the
     * panel middleware alone.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->is_admin && ! $user->isSuspended();
    }

    /**
     * @return array<int, class-string>
     */
    public function getWidgets(): array
    {
        return [
            ReportSummary::class,
            GrowthChart::class,
            CuisineBreakdown::class,
            TopRecipes::class,
        ];
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getColumns(): int|array
    {
        return 2;
    }
}
