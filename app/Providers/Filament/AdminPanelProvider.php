<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Widgets\PlatformOverview;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('Panchforon')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            /**
             * Warm orange accents on a warm-neutral (Stone) base. Filament generates
             * the full shade ramp from each hex, so the panel stays desaturated
             * everywhere except primary actions and status badges.
             */
            ->colors([
                'primary' => Color::hex('#FC7100'),
                'gray' => Color::Stone,
                'danger' => Color::hex('#F45F67'),
                'success' => Color::hex('#5CA135'),
                'warning' => Color::hex('#FB8818'),
            ])
            /**
             * Both faces ship in the storefront bundle the theme imports, so
             * these are declared through Filament's own API: it writes the
             * font variables inline, which would otherwise win over anything
             * the stylesheet sets.
             */
            ->font('Instrument Sans Variable', provider: LocalFontProvider::class)
            ->serifFont('Fraunces Variable', provider: LocalFontProvider::class)
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            /**
             * Widgets are registered explicitly rather than discovered: the
             * report widgets belong to the Reports page, and discovery would
             * also drop them onto the dashboard.
             */
            ->widgets([
                PlatformOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
